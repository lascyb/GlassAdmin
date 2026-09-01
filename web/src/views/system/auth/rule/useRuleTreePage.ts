import type { RuleRow } from '@/apis/system/auth/rule'
import * as ruleApi from '@/apis/system/auth/rule'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { useTreeCrud } from '@/composables/useTreeCrud'
import { ElMessage, type ElTree } from 'element-plus'
import { nextTick, ref, useTemplateRef } from 'vue'

/** TreeDropType el-tree allow-drop 的放置位置 */
type TreeDropType = 'prev' | 'inner' | 'next'

/** NodeDropType el-tree node-drop 的落点类型 */
type NodeDropType = 'before' | 'after' | 'inner'

/** TreeNode 拖放回调里用到的节点数据 */
type TreeNode = { data: RuleRow }

/** SortItem 排序写入项；改层级时带 pid */
type SortItem = { id: number; sort: number; pid?: number }

/**
 * containsId 节点（含子树）是否包含指定 id，用于禁止拖到自己的子孙下
 */
const containsId = (node: RuleRow, id: number): boolean => {
    if (node.id === id) return true
    return (node.children ?? []).some((c) => containsId(c, id))
}

/**
 * findChildrenList 按父 id 取当前树里的子列表（0 为根）
 */
const findChildrenList = (nodes: RuleRow[], parentId: number): RuleRow[] | null => {
    if (parentId === 0) return nodes
    const walk = (list: RuleRow[]): RuleRow[] | null => {
        for (const n of list) {
            if (n.id === parentId) return n.children ?? []
            if (n.children?.length) {
                const found = walk(n.children)
                if (found) return found
            }
        }
        return null
    }
    return walk(nodes)
}

/**
 * collectDropItems 按落点后的子列表生成 sort（改父级时带 pid）
 */
const collectDropItems = (
    siblings: RuleRow[],
    newPid: number,
    movedId: number,
    oldPid: number,
): SortItem[] => {
    const items: SortItem[] = []
    siblings.forEach((n, i) => {
        n.pid = newPid
        const sort = i * 10
        const pidChanged = n.id === movedId && oldPid !== newPid
        if (n.sort !== sort || pidChanged) {
            const item: SortItem = { id: n.id, sort }
            if (pidChanged) item.pid = newPid
            items.push(item)
        }
    })
    return items
}

/**
 * applyLocalSorts 把已保存的 sort/pid 写回树节点，避免下次拖动误判
 */
const applyLocalSorts = (nodes: RuleRow[], items: SortItem[]) => {
    const map = new Map(items.map((it) => [it.id, it]))
    const walk = (list: RuleRow[]) => {
        for (const n of list) {
            const it = map.get(n.id)
            if (it) {
                n.sort = it.sort
                if (it.pid !== undefined) n.pid = it.pid
            }
            if (n.children?.length) walk(n.children)
        }
    }
    walk(nodes)
}

/**
 * useRuleTreeBase 加载规则树、单删、批量删除与拖动排序（前后端页面共用）
 */
export function useRuleTreeBase(scope: 'backend' | 'frontend') {
    const treeRef = useTemplateRef<InstanceType<typeof ElTree>>('treeRef')
    const batchMode = ref(false)
    const sortMode = ref(false)
    /** persistLock 串行提交拖动结果，避免连续拖动互相覆盖 */
    let persistLock: Promise<void> = Promise.resolve()

    const { treeData, loading, load, remove, topIds } = useTreeCrud<RuleRow>(
        () => ruleApi.fetchTree(scope),
        (id) => ruleApi.deleteRule(id),
        {
            getDeleteMessage: (row) => `确定删除「${row.name}」？`,
            deleteSuccessMsg: '已删除',
        },
    )

    const { confirm } = useConfirmAction({ successMsg: '已删除' })

    /** canHaveChildren 是否允许作为父节点（与「子节点」按钮一致） */
    const canHaveChildren = (type: string) => {
        if (scope === 'frontend') {
            return type !== 'link' && type !== 'iframe' && type !== 'btn'
        }
        return true
    }

    /** onNodeExpand 手动展开时，下级仅 1 项则连续展开 */
    const onNodeExpand = (data: RuleRow) => {
        nextTick(() => {
            const tree = treeRef.value
            if (!tree) return
            let cur: RuleRow | undefined = data
            while (cur) {
                const children: RuleRow[] = cur.children ?? []
                if (children.length !== 1) break
                const next = children[0]
                if (!next) break
                cur = next
                tree.getNode(cur.id)?.expand()
            }
        })
    }

    /** walkTree 遍历规则树 */
    const walkTree = (nodes: RuleRow[], fn: (row: RuleRow) => void) => {
        for (const n of nodes) {
            fn(n)
            if (n.children?.length) walkTree(n.children, fn)
        }
    }

    /** expandAll 展开全部节点 */
    const expandAll = () => {
        nextTick(() => {
            const tree = treeRef.value
            if (!tree) return
            walkTree(treeData.value, (row) => {
                if (row.children?.length) {
                    tree.getNode(row.id)?.expand()
                }
            })
        })
    }

    /** collapseAll 收起全部节点 */
    const collapseAll = () => {
        nextTick(() => {
            const tree = treeRef.value
            if (!tree) return
            walkTree(treeData.value, (row) => {
                if (row.children?.length) {
                    tree.getNode(row.id)?.collapse()
                }
            })
        })
    }

    /** startBatchDelete 进入批量删除模式 */
    const startBatchDelete = () => {
        if (sortMode.value) return
        batchMode.value = true
        treeRef.value?.setCheckedKeys([])
    }

    /** cancelBatchDelete 取消批量删除 */
    const cancelBatchDelete = () => {
        batchMode.value = false
        treeRef.value?.setCheckedKeys([])
    }

    /** confirmBatchDelete 确认批量删除 */
    const confirmBatchDelete = async () => {
        const ids = (treeRef.value?.getCheckedKeys(false) as number[]) ?? []
        if (ids.length === 0) {
            ElMessage.warning('请选择要删除的节点')
            return
        }
        await confirm(`确定删除选中的 ${ids.length} 项？`, () => ruleApi.deleteRules(ids), {
            onSuccess: () => {
                cancelBatchDelete()
                void load()
            },
        })
    }

    /** startSort 进入拖动排序模式 */
    const startSort = () => {
        if (batchMode.value) return
        sortMode.value = true
    }

    /** stopSort 退出拖动排序模式（落点已即时保存，无需还原） */
    const stopSort = () => {
        sortMode.value = false
    }

    /** persistDrop 保存落点后的父级与同级顺序 */
    const persistDrop = async (movedId: number, oldPid: number, newPid: number) => {
        const siblings = findChildrenList(treeData.value, newPid)
        if (!siblings || !siblings.some((n) => n.id === movedId)) {
            ElMessage.error('无法定位落点，已刷新')
            await load()
            return
        }
        const items = collectDropItems(siblings, newPid, movedId, oldPid)
        if (items.length === 0) return
        try {
            const res = await ruleApi.sortRules(items)
            if (res.code === 0) {
                applyLocalSorts(treeData.value, items)
                ElMessage.success(res.msg || '排序已保存')
            } else {
                ElMessage.error(res.msg || '排序保存失败')
                await load()
            }
        } catch (e: unknown) {
            ElMessage.error(e instanceof Error ? e.message : '排序保存失败')
            await load()
        }
    }

    /** onSortDrop 拖动结束后即时保存父级与同级顺序 */
    const onSortDrop = (dragging: TreeNode, drop: TreeNode, dropType: NodeDropType) => {
        const oldPid = dragging.data.pid
        const newPid = dropType === 'inner' ? drop.data.id : drop.data.pid
        persistLock = persistLock.then(() => persistDrop(dragging.data.id, oldPid, newPid)).catch(() => {})
    }

    /** allowSortDrop 允许同级调整与挂到可收子节点的节点下；禁止拖进自己的子孙 */
    const allowSortDrop = (dragging: TreeNode, drop: TreeNode, type: TreeDropType) => {
        if (drop.data.id === dragging.data.id) return false
        if (containsId(dragging.data, drop.data.id)) return false
        if (type === 'inner') return canHaveChildren(drop.data.type)
        return true
    }

    return {
        treeRef,
        treeData,
        loading,
        load,
        remove,
        top_ids: topIds,
        batchMode,
        startBatchDelete,
        cancelBatchDelete,
        confirmBatchDelete,
        sortMode,
        startSort,
        stopSort,
        allowSortDrop,
        onSortDrop,
        onNodeExpand,
        expandAll,
        collapseAll,
    }
}
