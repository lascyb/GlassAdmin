<script setup lang="ts">
import { showError } from '@/apis/request'
import type { RuleRow } from '@/apis/system/auth/rule'
import * as ruleApi from '@/apis/system/auth/rule'
import { ruleTypeLabel, ruleTypeTagType } from '@/views/system/auth/rule/constants'
import GaIcon from '@components/ga-icon/index.vue'
import { ElMessage } from 'element-plus'
import { nextTick, ref } from 'vue'

/** BINDABLE 可绑定后端 api 的前端规则类型 */
const BINDABLE = new Set(['view', 'route', 'btn'])

/** TreeNode 非 view/route/btn 禁用勾选 */
type TreeNode = RuleRow & { disabled?: boolean }

const visible = ref(false)
const loading = ref(false)
const saving = ref(false)
const ruleId = ref(0)
const ruleName = ref('')
const frontTree = ref<TreeNode[]>([])
const bindableIds = new Set<number>()
const treeRef = ref()
const treeKey = ref(0)

/**
 * toTreeNodes 复制树并为不可绑定节点禁用勾选，同时收集可绑定 id
 */
const toTreeNodes = (rows: RuleRow[] | undefined): TreeNode[] => {
    if (!rows?.length) return []
    return rows.map((r) => {
        const ok = BINDABLE.has(r.type)
        if (ok) bindableIds.add(r.id)
        return {
            ...r,
            disabled: !ok,
            children: r.children?.length ? toTreeNodes(r.children) : undefined,
        }
    })
}

const open = async (row: RuleRow) => {
    ruleId.value = row.id
    ruleName.value = row.name
    visible.value = true
    loading.value = true
    frontTree.value = []
    bindableIds.clear()
    try {
        const data = await ruleApi.fetchFrontends(row.id)
        frontTree.value = toTreeNodes(data.rules)
        treeKey.value += 1
        await nextTick()
        treeRef.value?.setCheckedKeys(data.frontend_rule_ids.filter((id) => bindableIds.has(id)))
    } catch (e: unknown) {
        showError(e, '加载失败')
        visible.value = false
    } finally {
        loading.value = false
    }
}

const submit = async () => {
    const raw = (treeRef.value?.getCheckedKeys(false) ?? []) as number[]
    const ids = raw.filter((id) => bindableIds.has(id))
    saving.value = true
    try {
        await ruleApi.assignFrontends(ruleId.value, ids)
        ElMessage.success('已分配')
        visible.value = false
    } catch (e: unknown) {
        showError(e, '分配失败')
    } finally {
        saving.value = false
    }
}

defineExpose({ open })
</script>

<template>
    <el-dialog
        v-model="visible"
        draggable
        :title="`分配前端 - ${ruleName}`"
        width="520px"
        destroy-on-close
        lock-scroll
        top="8vh"
        :close-on-click-modal="false"
    >
        <p class="mb-3 text-xs leading-5 text-ga-text-secondary">
            仅覆盖当前接口与所选前端规则的绑定，不影响这些规则上的其它接口。仅页面 / 路由 / 按钮可勾选，不自动包含子节点。
        </p>
        <div v-loading="loading" class="min-h-40">
            <el-tree
                :key="treeKey"
                ref="treeRef"
                :data="frontTree"
                node-key="id"
                show-checkbox
                check-strictly
                default-expand-all
                :props="{ children: 'children', label: 'name', disabled: 'disabled' }"
            >
                <template #default="{ data }">
                    <span class="flex flex-1 flex-wrap items-center gap-2 py-1 pr-2">
                        <el-tag size="small" :type="ruleTypeTagType(data.type)">{{ ruleTypeLabel(data.type) }}</el-tag>
                        <GaIcon v-if="data.icon" :icon="data.icon" class="shrink-0" />
                        <span>{{ data.name }}</span>
                        <span v-if="data.path" class="text-xs text-ga-text-secondary">{{ data.path }}</span>
                    </span>
                </template>
            </el-tree>
        </div>
        <template #footer>
            <el-button @click="visible = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="submit">确定</el-button>
        </template>
    </el-dialog>
</template>
