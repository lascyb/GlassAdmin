<script setup lang="ts">
import SwitchRolesDialog from '@/layouts/components/header/components/SwitchRolesDialog.vue'
import { useAdmin } from '@/pinia/admin'
import { useMainViewRefresh } from '@/pinia/layout/mainViewRefresh'
import { useLayoutSetting } from '@/pinia/layout/setting.ts'
import { resolveStorageUrl } from '@/utils/storageUrl'
import { ArrowDown, Refresh, Tools } from '@element-plus/icons-vue'
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

const accountUsername = computed(() => {
    const username = admin.data?.username?.trim()
    const nickname = admin.data?.nickname?.trim()
    if (!username || username === nickname) return ''
    return username
})

const accountInitial = computed(() => {
    const name = accountName.value.trim()
    return name ? name.slice(0, 1) : '?'
})

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
            <button type="button" class="navs-btn" aria-label="刷新当前页" @click="refreshPage">
                <el-icon :size="20">
                    <Refresh />
                </el-icon>
            </button>
        </el-tooltip>
        <el-popover
            v-model:visible="popoverVisible"
            placement="bottom-end"
            width="280"
            trigger="click"
        >
            <template #reference>
                <button
                    type="button"
                    class="navs-btn account-trigger"
                    :class="{ 'is-open': popoverVisible }"
                    aria-label="账号菜单"
                    :aria-expanded="popoverVisible"
                >
                    <el-avatar :size="28" :src="headerAvatarSrc || undefined">
                        {{ accountInitial }}
                    </el-avatar>
                    <el-icon class="account-caret" :size="12">
                        <ArrowDown />
                    </el-icon>
                </button>
            </template>
            <template #default>
                <div class="account-panel">
                    <div class="account-identity">
                        <el-avatar :size="36" :src="headerAvatarSrc || undefined">
                            {{ accountInitial }}
                        </el-avatar>
                        <div class="account-identity-meta">
                            <span class="account-name-text">{{ accountName }}</span>
                            <span v-if="accountUsername" class="account-username">{{
                                accountUsername
                            }}</span>
                        </div>
                    </div>
                    <div class="account-actions">
                        <button type="button" class="account-action" @click="goWorkbench">
                            工作台
                        </button>
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
        <el-tooltip content="个人偏好" placement="bottom">
            <button
                type="button"
                class="navs-btn"
                aria-label="个人偏好"
                @click="setting.showSetting()"
            >
                <el-icon :size="20">
                    <Tools />
                </el-icon>
            </button>
        </el-tooltip>

        <switch-roles-dialog v-model="rolesDialogVisible" />
    </div>
</template>

<style scoped>
.navs {
    display: flex;
    align-items: stretch;
    height: 100%;
}

.navs :deep(.el-tooltip__trigger) {
    display: inline-flex;
    height: 100%;
}

.navs-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    margin: 0;
    padding: 0 var(--ga-space-2);
    min-width: 40px;
    height: 100%;
    border: 0;
    background: transparent;
    color: var(--ga-color-text-regular);
    cursor: pointer;

    &:hover {
        background: color-mix(in srgb, var(--ga-color-fill) 72%, transparent);
    }
}

.account-trigger {
    gap: var(--ga-space-1);
    min-width: 48px;
}

.account-trigger :deep(.el-avatar) {
    flex-shrink: 0;
    font-size: 12px;
    background: var(--ga-color-fill);
    color: var(--ga-color-text-regular);
}

.account-caret {
    color: var(--ga-color-text-secondary);
    transition: transform var(--ga-duration) var(--ga-ease);
}

.account-trigger.is-open .account-caret {
    transform: rotate(180deg);
}

.account-panel {
    display: flex;
    flex-direction: column;
    margin: calc(var(--ga-space-1) * -1) calc(var(--ga-space-2) * -1);
}

.account-identity {
    display: flex;
    align-items: center;
    gap: var(--ga-space-3);
    padding: var(--ga-space-2) var(--ga-space-3) var(--ga-space-3);
    min-width: 0;
}

.account-identity :deep(.el-avatar) {
    flex-shrink: 0;
    font-size: 14px;
    background: var(--ga-color-fill);
    color: var(--ga-color-text-regular);
}

.account-identity-meta {
    display: flex;
    flex: 1;
    min-width: 0;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
}

.account-name-text {
    overflow: hidden;
    max-width: 100%;
    font-weight: 500;
    line-height: 1.4;
    color: var(--ga-color-text);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.account-username {
    overflow: hidden;
    font-size: 12px;
    line-height: 1.4;
    color: var(--ga-color-text-secondary);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.account-actions {
    display: flex;
    flex-direction: column;
    padding: var(--ga-space-1) 0;
    border-top: 1px solid var(--ga-color-border-lighter);
}

.account-footer {
    padding: var(--ga-space-1) 0 0;
    border-top: 1px solid var(--ga-color-border-lighter);
}

.account-action {
    display: block;
    width: 100%;
    margin: 0;
    padding: var(--ga-space-2) var(--ga-space-3);
    border: 0;
    background: transparent;
    color: var(--ga-color-text-regular);
    font-size: 13px;
    line-height: 1.4;
    text-align: left;
    cursor: pointer;

    &:hover {
        background: var(--ga-color-fill-light);
        color: var(--el-color-primary);
    }

    &.is-danger:hover {
        color: var(--el-color-danger);
        background: var(--el-color-danger-light-9);
    }
}
</style>
