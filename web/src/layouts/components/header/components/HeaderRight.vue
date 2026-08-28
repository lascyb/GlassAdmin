<script setup lang="ts">
import SwitchRolesDialog from '@/layouts/components/header/components/SwitchRolesDialog.vue'
import { useAdmin } from '@/pinia/admin'
import { useMainViewRefresh } from '@/pinia/layout/mainViewRefresh'
import { useLayoutSetting } from '@/pinia/layout/setting.ts'
import { resolveStorageUrl } from '@/utils/storageUrl'
import { Refresh, Tools } from '@element-plus/icons-vue'
import { ElMessageBox } from 'element-plus'
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const setting = useLayoutSetting()
const admin = useAdmin()
const route = useRoute()
const router = useRouter()
const mainRefresh = useMainViewRefresh()
const popoverVisible = ref(false)
const rolesDialogVisible = ref(false)

const headerAvatarSrc = computed(() => resolveStorageUrl(admin.data?.avatar))

const accountName = computed(
    () => admin.data?.nickname || admin.data?.username || '账号',
)

const hasRoles = computed(() => (admin.data?.role_ids?.length ?? 0) > 0)

const refreshPage = () => {
    mainRefresh.bumpForPath(route.fullPath)
}

/** goWorkbench 进入工作台首页 */
const goWorkbench = () => {
    popoverVisible.value = false
    void router.push({ name: 'root-default' })
}

/** goProfile 进入个人中心 */
const goProfile = () => {
    popoverVisible.value = false
    void router.push({ name: 'account-profile' })
}

/** openRolesDialog 打开切换角色弹窗 */
const openRolesDialog = () => {
    popoverVisible.value = false
    rolesDialogVisible.value = true
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
                width="280"
                trigger="click"
            >
                <template #reference>
                    <span class="inline-flex cursor-pointer items-center">
                        <el-avatar class="mr-1 mb-0" :src="headerAvatarSrc || undefined" />
                    </span>
                </template>
                <template #default>
                    <div class="account-panel">
                        <div class="account-name">
                            <el-text truncated class="account-name-text">{{ accountName }}</el-text>
                            <button type="button" class="account-link" @click="goWorkbench">
                                工作台
                            </button>
                        </div>
                        <div class="account-actions">
                            <button type="button" class="account-action" @click="goProfile">
                                个人中心
                            </button>
                            <button
                                v-if="hasRoles"
                                type="button"
                                class="account-action"
                                @click="openRolesDialog"
                            >
                                切换角色
                            </button>
                        </div>
                        <div class="account-footer">
                            <button type="button" class="account-action is-danger" @click="logout">
                                退出登录
                            </button>
                        </div>
                    </div>
                </template>
            </el-popover>
        </div>
        <el-icon size="20" @click="setting.showSetting()">
            <Tools />
        </el-icon>

        <switch-roles-dialog v-model="rolesDialogVisible" />
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
            background: color-mix(in srgb, var(--ga-color-fill) 72%, transparent);
        }
    }
}

.account-panel {
    display: flex;
    flex-direction: column;
    margin: -4px -8px;
}

.account-name {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 4px 12px 8px;
    min-width: 0;
    font-weight: 500;
}

.account-name-text {
    flex: 1;
    min-width: 0;
}

.account-link {
    flex-shrink: 0;
    margin: 0;
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--el-color-primary);
    font-size: 13px;
    line-height: 1.4;
    cursor: pointer;

    &:hover {
        opacity: 0.8;
    }
}

.account-actions {
    display: flex;
    flex-direction: column;
    border-top: 1px solid var(--el-border-color-lighter);
    padding: 4px 0;
}

.account-footer {
    border-top: 1px solid var(--el-border-color-lighter);
    padding: 4px 0 0;
}

.account-action {
    display: block;
    width: 100%;
    margin: 0;
    padding: 8px 12px;
    border: 0;
    background: transparent;
    color: var(--el-text-color-regular);
    font-size: 13px;
    line-height: 1.4;
    text-align: left;
    cursor: pointer;

    &:hover {
        background: var(--el-fill-color-light);
        color: var(--el-color-primary);
    }

    &.is-danger:hover {
        color: var(--el-color-danger);
        background: var(--el-color-danger-light-9);
    }
}
</style>
