import type { RuleRow } from '@/apis/system/auth/rule'
import * as ruleApi from '@/apis/system/auth/rule'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { useTreeCrud } from '@/composables/useTreeCrud'
import { ElMessage, type ElTree } from 'element-plus'
import { nextTick, ref, useTemplateRef } from 'vue'

/**
 * useRuleTreeBase 加载规则树、单删与批量删除（前后端页面共用）
 */
export function useRuleTreeBase(scope: 'backend' | 'frontend') {
    const treeRef = useTemplateRef<InstanceType<typeof ElTree>>('treeRef')
    const batchMode = ref(false)

    const { treeData, loading, load, remove, topIds } = useTreeCrud<RuleRow>(
        () => ruleApi.fetchTree(scope),
        (id) => ruleApi.deleteRule(id),
        {
            getDeleteMessage: (row) => `确定删除「${row.name}」？`,
            deleteSuccessMsg: '已删除',
        },
    )

    const { confirm } = useConfirmAction({ successMsg: '已删除' })

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

    /** startBatchDelete 进入批量删除模式 */
    const startBatchDelete = () => {
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
        onNodeExpand,
    }
}
