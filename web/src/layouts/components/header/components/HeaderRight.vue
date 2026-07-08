<script setup lang="ts">
import type { AdminRoleBrief } from '@/apis/account'
import * as accountApi from '@/apis/account'
import { useAccountNotifications } from '@/pinia/account/notifications'
import { useAdmin } from '@/pinia/admin'
import { useMainViewRefresh } from '@/pinia/layout/mainViewRefresh'
import { useLayoutSetting } from '@/pinia/layout/setting.ts'
import { resolveStorageUrl } from '@/utils/storageUrl'
import { Refresh, Tools } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const setting = useLayoutSetting()
const admin = useAdmin()
const route = useRoute()
const router = useRouter()
const mainRefresh = useMainViewRefresh()
const notifyStore = useAccountNotifications()
const activeName = ref<'system' | 'roles'>('system')
const popoverVisible = ref(false)

const roleOptions = ref<AdminRoleBrief[]>([])
const selectedRoleIds = ref<number[]>([])
const rolesLoading = ref(false)
const rolesSaving = ref(false)

const headerAvatarSrc = computed(() => resolveStorageUrl(admin.data?.avatar))

const onlyOneRole = computed(() => roleOptions.value.length <= 1)

const refreshPage = () => {
    mainRefresh.bumpForPath(route.fullPath)
}

watch(activeName, () => {
    if (!popoverVisible.value) {
        return
    }
    if (activeName.value === 'roles') {
        void loadRoles()
    } else {
        void notifyStore.fetchHeaderList('system')
    }
})

const onPopoverShow = () => {
    if (activeName.value === 'roles') {
        void loadRoles()
    } else {
        void notifyStore.fetchHeaderList('system')
    }
}

/** loadRoles 拉取当前用户可切换的角色列表 */
const loadRoles = async () => {
    rolesLoading.value = true
    try {
        const profile = await accountApi.fetchProfile()
        roleOptions.value = profile.roles ?? []
        selectedRoleIds.value = profile.roles?.filter((r) => r.enabled === 1).map((r) => r.id) ?? profile.role_ids ?? []
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '加载角色失败')
    } finally {
        rolesLoading.value = false
    }
}

onMounted(() => {
    void notifyStore.refreshUnreadBadge()
})

const goNotificationsPage = async () => {
    popoverVisible.value = false
    await router.push('/account/notifications')
}

/** applyRoles 应用启用的角色并刷新菜单 */
const applyRoles = async () => {
    if (selectedRoleIds.value.length === 0) {
        ElMessage.warning('请至少启用一个角色')
        return
    }
    rolesSaving.value = true
    try {
        const res = await accountApi.switchRoles(selectedRoleIds.value)
        if (admin.data) {
            admin.data.role_ids = res.role_ids
            admin.data.roles = res.roles
        }
        popoverVisible.value = false
        ElMessage.success('角色已切换，正在刷新…')
        window.location.assign('/')
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '切换失败')
    } finally {
        rolesSaving.value = false
    }
}

/**
 * logout 退出登录：清空 token 与用户信息并跳转登录页
 */
const logout = async () => {
    try {
        await ElMessageBox.confirm('确定退出当前账号？', '退出登录', { type: 'warning' })
    } catch {
        return
    }
    admin.clear()
    await router.replace({ name: 'login' })
}
</script>

<template>
    <div class="navs">
        <el-tooltip content="刷新当前页" placement="bottom">
            <el-icon size="20" @click="refreshPage">
                <Refresh />
            </el-icon>
        </el-tooltip>
        <div class="flex items-center" style="padding-top: 6px">
            <el-popover
                v-model:visible="popoverVisible"
                placement="bottom-end"
                width="320"
                trigger="click"
                @show="onPopoverShow"
            >
                <template #reference>
                    <span class="inline-flex cursor-pointer items-center">
                        <el-badge :value="notifyStore.unreadTotal" :hidden="notifyStore.unreadTotal <= 0" :max="99">
                            <el-avatar class="mr-1 mb-0" :src="headerAvatarSrc || undefined" />
                        </el-badge>
                    </span>
                </template>
                <template #default>
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <el-text>{{ admin.data?.nickname || admin.data?.username || '账号' }}</el-text>
                        <el-button type="danger" link size="small" @click="logout">退出登录</el-button>
                    </div>

                    <el-tabs v-model="activeName">
                        <el-tab-pane label="系统消息" name="system">
                            <el-scrollbar max-height="220px">
                                <div v-if="notifyStore.loadingBrief" class="py-4 text-center text-xs text-gray-400">
                                    加载中…
                                </div>
                                <div
                                    v-else-if="!notifyStore.briefSystem.length"
                                    class="py-4 text-center text-xs text-gray-400"
                                >
                                    暂无系统消息
                                </div>
                                <ul v-else class="m-0 list-none space-y-2 p-0">
                                    <li
                                        v-for="n in notifyStore.briefSystem"
                                        :key="n.id"
                                        class="rounded border border-gray-100 px-2 py-1.5 text-xs"
                                    >
                                        <div class="font-medium text-gray-800">{{ n.title }}</div>
                                        <div class="mt-0.5 line-clamp-2 text-gray-500">{{ n.content }}</div>
                                        <div class="mt-1 text-[11px] text-gray-400">{{ n.create_time }}</div>
                                    </li>
                                </ul>
                            </el-scrollbar>
                            <div class="mt-2 flex justify-between gap-2">
                                <el-button type="primary" link size="small" @click="goNotificationsPage">查看全部</el-button>
                            </div>
                        </el-tab-pane>
                        <el-tab-pane label="角色" name="roles">
                            <div v-loading="rolesLoading" class="min-h-[120px]">
                                <div v-if="!rolesLoading && roleOptions.length === 0" class="py-4 text-center text-xs text-gray-400">
                                    未分配角色
                                </div>
                                <el-checkbox-group v-else v-model="selectedRoleIds" class="flex flex-col gap-2">
                                    <el-checkbox
                                        v-for="role in roleOptions"
                                        :key="role.id"
                                        :value="role.id"
                                        :disabled="onlyOneRole && selectedRoleIds.includes(role.id) && selectedRoleIds.length === 1"
                                    >
                                        {{ role.name }}
                                    </el-checkbox>
                                </el-checkbox-group>
                                <div
                                    v-if="roleOptions.length > 0"
                                    class="mt-3 flex items-center justify-between gap-3 border-t border-gray-100 pt-3"
                                >
                                    <p class="mb-0 flex-1 text-[11px] leading-snug text-gray-400">
                                        仅启用的角色参与菜单与权限；<br>应用后将刷新页面。
                                    </p>
                                    <el-button
                                        type="primary"
                                        size="small"
                                        :loading="rolesSaving"
                                        :disabled="selectedRoleIds.length === 0"
                                        @click="applyRoles"
                                    >
                                        应用
                                    </el-button>
                                </div>
                            </div>
                        </el-tab-pane>
                    </el-tabs>
              
                </template>
            </el-popover>
        </div>
        <el-icon size="20" @click="setting.showSetting()">
            <Tools />
        </el-icon>
    </div>
</template>

<style scoped>
.navs {
    display: flex;
    align-items: center;

    > * {
        padding: 0 5px;
        height: 100%;
        min-width: 40px;
        cursor: pointer;

        &:hover {
            background: #f0f2f5;
        }
    }
}
</style>

