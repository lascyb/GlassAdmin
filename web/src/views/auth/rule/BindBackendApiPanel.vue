<script setup lang="ts">
import type { RuleRow } from '@/apis/auth/rule'
import * as ruleApi from '@/apis/auth/rule'
import { ElMessage } from 'element-plus'
import { nextTick, onMounted, ref } from 'vue'

/** TreeNode 后端规则树节点（menu 禁用勾选，仅 api 可参与绑定） */
type TreeNode = RuleRow & { disabled?: boolean }

const model = defineModel<number[]>({ default: () => [] })

const loading = ref(false)
const treeData = ref<TreeNode[]>([])
/** defaultExpandedKeys 有绑定时展开已选 api 所在分支 */
const defaultExpandedKeys = ref<number[]>([])
/** expandAllDefault 无绑定时默认展开整棵树 */
const expandAllDefault = ref(false)
const treeRef = ref()
const treeKey = ref(0)
let apiIdSet = new Set<number>()

/**
 * toTreeNodes 复制树并为非 api 节点禁用勾选
 */
const toTreeNodes = (rows: RuleRow[] | undefined): TreeNode[] => {
    if (!rows?.length) return []
    return rows.map((r) => ({
        ...r,
        disabled: r.type !== 'api',
        children: r.children?.length ? toTreeNodes(r.children) : undefined,
    }))
}

/**
 * collectApiIds 收集树中全部 api 的 id
 */
const collectApiIds = (rows: RuleRow[] | undefined): Set<number> => {
    const s = new Set<number>()
    const walk = (arr?: RuleRow[]) => {
        if (!arr) return
        for (const n of arr) {
            if (n.type === 'api') s.add(n.id)
            walk(n.children)
        }
    }
    walk(rows)
    return s
}

/**
 * syncCheckedFromModel 按 v-model 回填勾选
 */
const syncCheckedFromModel = async () => {
    const raw = (model.value ?? []).filter((x) => apiIdSet.has(x))
    expandAllDefault.value = raw.length === 0
    defaultExpandedKeys.value = raw
    treeKey.value++
    await nextTick()
    treeRef.value?.setCheckedKeys(raw, false)
}

/**
 * onCheck 勾选变化时同步 v-model
 */
const onCheck = () => {
    const checked = (treeRef.value?.getCheckedKeys(false) as number[]) ?? []
    model.value = checked.filter((k) => apiIdSet.has(k))
}

/**
 * load 加载后端规则树
 */
const load = async () => {
    loading.value = true
    try {
        const tree = await ruleApi.fetchTree('backend')
        apiIdSet = collectApiIds(tree)
        treeData.value = toTreeNodes(tree)
        await syncCheckedFromModel()
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '加载后端规则树失败')
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    void load()
})
</script>

<template>
    <div v-loading="loading" class="w-full">
        <p class="mt-2 mb-2 text-sm text-ga-text-secondary">按目录分组勾选接口；目录不可选，仅「api」类型会提交到后端。</p>
        <el-scrollbar max-height="320px">
            <el-tree
                v-if="treeData.length"
                ref="treeRef"
                :key="treeKey"
                :data="treeData"
                node-key="id"
                show-checkbox
                check-strictly
                :default-expand-all="expandAllDefault"
                :default-expanded-keys="expandAllDefault ? [] : defaultExpandedKeys"
                :props="{ children: 'children', label: 'name', disabled: 'disabled' }"
                @check="onCheck"
            >
                <template #default="{ data }">
                    <span class="flex flex-wrap items-center gap-2">
                        <el-tag v-if="data.type === 'api'" type="warning">api</el-tag>
                        <el-tag v-else type="info">{{ data.type }}</el-tag>
                        <span>{{ data.name }}</span>
                        <span v-if="data.type === 'api' && (data.path || data.method)" class="text-xs text-ga-text-secondary">
                            {{ data.method ?? '*' }} {{ data.path ?? '' }}
                        </span>
                    </span>
                </template>
            </el-tree>
            <el-empty v-else description="暂无后端规则树" />
        </el-scrollbar>
    </div>
</template>
