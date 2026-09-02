<script setup lang="ts">
import { showError } from '@/apis/request'
import type { RoleRow } from '@/apis/system/auth/role'
import type { RuleRow } from '@/apis/system/auth/rule'
import * as ruleApi from '@/apis/system/auth/rule'
import { ElMessage } from 'element-plus'
import { nextTick, ref } from 'vue'

const visible = ref(false)
const loading = ref(false)
const saving = ref(false)
const ruleId = ref(0)
const ruleName = ref('')
const roleTree = ref<RoleRow[]>([])
const treeRef = ref()
const treeKey = ref(0)

const open = async (row: RuleRow) => {
    ruleId.value = row.id
    ruleName.value = row.name
    visible.value = true
    loading.value = true
    roleTree.value = []
    try {
        const data = await ruleApi.fetchRoles(row.id)
        roleTree.value = data.roles
        treeKey.value += 1
        await nextTick()
        treeRef.value?.setCheckedKeys(data.role_ids)
    } catch (e: unknown) {
        showError(e, '加载失败')
        visible.value = false
    } finally {
        loading.value = false
    }
}

const submit = async () => {
    const roleIds = (treeRef.value?.getCheckedKeys(false) ?? []) as number[]
    saving.value = true
    try {
        await ruleApi.assignRoles(ruleId.value, roleIds)
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
        :title="`分配角色 - ${ruleName}`"
        width="480px"
        destroy-on-close
        lock-scroll
        top="8vh"
        :close-on-click-modal="false"
    >
        <p class="mb-3 text-xs leading-5 text-ga-text-secondary">
            仅覆盖当前规则与所选角色的关联，不自动勾选父级规则或子角色。超级管理员未勾选也视为拥有全部前端规则。
        </p>
        <div v-loading="loading" class="min-h-40">
            <el-tree
                :key="treeKey"
                ref="treeRef"
                :data="roleTree"
                node-key="id"
                show-checkbox
                check-strictly
                default-expand-all
                :props="{ children: 'children', label: 'name' }"
            >
                <template #default="{ data }">
                    <span class="flex flex-1 flex-wrap items-center gap-2 py-1 pr-2">
                        <span>{{ data.name }}</span>
                        <span v-if="data.code" class="text-xs text-ga-text-secondary">{{ data.code }}</span>
                        <el-tag v-if="data.id === 1" size="small" type="warning" effect="plain">全量</el-tag>
                        <el-tag v-if="data.status === 0" size="small" type="info" effect="plain">禁用</el-tag>
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
