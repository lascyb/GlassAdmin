<script setup lang="ts">
import type { AdminRoleBrief } from '@/apis/account'
import * as accountApi from '@/apis/account'
import { useAdmin } from '@/pinia/admin'
import { ElMessage } from 'element-plus'
import { computed, ref, watch } from 'vue'

const visible = defineModel<boolean>({ default: false })

const admin = useAdmin()
const roleOptions = ref<AdminRoleBrief[]>([])
const selectedRoleIds = ref<number[]>([])
const rolesLoading = ref(false)
const rolesSaving = ref(false)

const onlyOneRole = computed(() => roleOptions.value.length <= 1)

/** loadRoles 拉取当前用户可切换的角色列表 */
const loadRoles = async () => {
    rolesLoading.value = true
    try {
        const profile = await accountApi.fetchRoles()
        roleOptions.value = profile.roles ?? []
        selectedRoleIds.value = profile.roles?.filter((r) => r.enabled === 1).map((r) => r.id) ?? profile.role_ids ?? []
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '加载角色失败')
    } finally {
        rolesLoading.value = false
    }
}

/** applyRoles 应用启用的角色并刷新页面 */
const applyRoles = async () => {
    if (selectedRoleIds.value.length === 0) {
        ElMessage.warning('请至少启用一个角色')
        return
    }
    rolesSaving.value = true
    try {
        const res = await accountApi.switchRoles(selectedRoleIds.value)
        if (admin.data) {
            admin.data.roles = res.roles
            admin.data.role_codes = admin.data.roles?.filter(item=>item.enabled==1)?.map(item=>item.code)
            admin.data.role_ids = admin.data.roles?.filter(item=>item.enabled==1)?.map(item=>item.id)
        }
        visible.value = false
        ElMessage.success('角色已切换，正在刷新…')
        window.location.assign('/')
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '切换失败')
    } finally {
        rolesSaving.value = false
    }
}

watch(visible, (open) => {
    if (open) void loadRoles()
})
</script>

<template>
    <el-dialog
        v-model="visible"
        title="切换角色"
        width="420px"
        append-to-body
        destroy-on-close
        :close-on-click-modal="false"
        draggable
    >
        <div v-loading="rolesLoading" class="min-h-30">
            <div
                v-if="!rolesLoading && roleOptions.length === 0"
                class="py-6 text-center text-sm text-ga-text-secondary"
            >
                未分配角色
            </div>
            <template v-else>
                <p class="mb-3 text-xs text-ga-text-secondary">仅启用的角色参与菜单与权限；应用后将刷新页面。</p>
                <el-checkbox-group v-model="selectedRoleIds" class="flex flex-col gap-2">
                    <el-checkbox
                        v-for="role in roleOptions"
                        :key="role.id"
                        :value="role.id"
                        :disabled="
                            onlyOneRole &&
                            selectedRoleIds.includes(role.id) &&
                            selectedRoleIds.length === 1
                        "
                    >
                        {{ role.name }}
                    </el-checkbox>
                </el-checkbox-group>
            </template>
        </div>
        <template #footer>
            <el-button @click="visible = false">取消</el-button>
            <el-button
                type="primary"
                :loading="rolesSaving"
                :disabled="selectedRoleIds.length === 0 || roleOptions.length === 0"
                @click="applyRoles"
            >
                应用
            </el-button>
        </template>
    </el-dialog>
</template>
