<script setup lang="ts">
import type { RuleRow } from '@/apis/auth/rule'
import * as ruleApi from '@/apis/auth/rule'
import { ElMessage } from 'element-plus'
import { nextTick, ref, watch } from 'vue'

/** TreeNode 后端规则树节点（menu 禁用勾选，仅 api 可参与 bindApis） */
type TreeNode = RuleRow & { disabled?: boolean }

const visible = defineModel<boolean>({ required: true })

const props = defineProps<{
    /** frontend_rule_id，弹窗打开时必传 */
    frontendRuleId?: number
    /** 用于标题展示 */
    titleName?: string
}>()

const emit = defineEmits<{
    success: []
}>()

const loading = ref(false)
const saving = ref(false)
const treeData = ref<TreeNode[]>([])
/** defaultExpandedKeys 有绑定时与 backend_rule_ids 一致（树内有效 api id）；无绑定时配合 expandAllDefault 走 default-expand-all */
const defaultExpandedKeys = ref<number[]>([])
/** expandAllDefault backend_rule_ids 为空时默认展开整棵树 */
const expandAllDefault = ref(false)
const treeRef = ref()
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

const load = async () => {
    const id = props.frontendRuleId
    if (!id || !visible.value) return
    loading.value = true
    try {
        const [tree, detail] = await Promise.all([ruleApi.fetchTree('backend'), ruleApi.fetchRead(id)])
        apiIdSet = collectApiIds(tree)
        const raw = (detail.backend_rule_ids ?? []).filter((x) => apiIdSet.has(x))
        expandAllDefault.value = raw.length === 0
        defaultExpandedKeys.value = raw
        treeData.value = toTreeNodes(tree)
        await nextTick()
        treeRef.value?.setCheckedKeys(raw, false)
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '加载失败')
        visible.value = false
    } finally {
        loading.value = false
    }
}

watch(visible, (v) => {
    if (v && props.frontendRuleId) void load()
})

watch(
    () => props.frontendRuleId,
    () => {
        if (visible.value && props.frontendRuleId) void load()
    },
)

const submit = async () => {
    const id = props.frontendRuleId
    if (!id) return
    const tree = treeRef.value
    if (!tree) return
    const checked = (tree.getCheckedKeys(false) as number[]) ?? []
    const apiIds = checked.filter((k) => apiIdSet.has(k))
    saving.value = true
    try {
        await ruleApi.bindApis(id, apiIds)
        ElMessage.success('已保存关联')
        emit('success')
        visible.value = false
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '保存失败')
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <el-dialog v-model="visible" :title="titleName ? `关联后端 API · ${titleName}` : '关联后端 API'" width="720px" destroy-on-close>
        <div v-loading="loading">
            <p class="mb-2 text-sm text-gray-500">按目录分组勾选接口；目录不可选，仅「api」类型会提交到后端。</p>
            <el-scrollbar max-height="420px">
                <el-tree
                    v-if="treeData.length"
                    ref="treeRef"
                    :key="`${frontendRuleId ?? 0}-${expandAllDefault ? 1 : 0}-${defaultExpandedKeys.join(',')}`"
                    :data="treeData"
                    node-key="id"
                    show-checkbox
                    check-strictly
                    :default-expand-all="expandAllDefault"
                    :default-expanded-keys="expandAllDefault ? [] : defaultExpandedKeys"
                    :props="{ children: 'children', label: 'name', disabled: 'disabled' }"
                >
                    <template #default="{ data }">
                        <span class="flex flex-wrap items-center gap-2">
                            <el-tag v-if="data.type === 'api'"  type="warning">api</el-tag>
                            <el-tag v-else  type="info">{{ data.type }}</el-tag>
                            <span>{{ data.name }}</span>
                            <span v-if="data.type === 'api' && (data.path || data.method)" class="text-xs text-gray-500">
                                {{ data.method ?? '*' }} {{ data.path ?? '' }}
                            </span>
                        </span>
                    </template>
                </el-tree>
                <el-empty v-else description="暂无后端规则树" />
            </el-scrollbar>
        </div>
        <template #footer>
            <el-button @click="visible = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
        </template>
    </el-dialog>
</template>
