<script setup lang="ts">
import SwitchRolesDialog from '@/layouts/components/header/components/SwitchRolesDialog.vue'
import { useAdmin } from '@/pinia/admin'
import { useMainViewRefresh } from '@/pinia/layout/mainViewRefresh'
import { resolveKeepAliveBaseKey } from '@/utils/pageKey'
import {
    normalizeColorMode,
    useLayoutSetting,
} from '@/pinia/layout/setting.ts'
import { resolveStorageUrl } from '@/utils/storageUrl'
import {
    ArrowDown,
    HomeFilled,
    Monitor,
    Moon,
    Refresh,
    Sunny,
    Switch,
    SwitchButton,
    Tools,
    User,
} from '@element-plus/icons-vue'
import { ElMessageBox } from 'element-plus'
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const setting = useLayoutSetting()
const admin = useAdmin()
const route = useRoute()
const router = useRouter()
const mainRefresh = useMainViewRefresh()
const rolesDialogVisible = ref(false)
const refreshSpinning = ref(false)
const accountMenuOpen = ref(false)

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

const colorMode = computed(() => normalizeColorMode(setting.layout.colorMode))

const colorModeLabel = computed(() => {
    switch (colorMode.value) {
        case 'light':
            return '浅色'
        case 'dark':
            return '深色'
        case 'system':
            return '跟随系统'
    }
})

const themeTooltip = computed(() => `当前：${colorModeLabel.value}，点击切换`)

const refreshPage = () => {
    refreshSpinning.value = true
    mainRefresh.bumpForPath(resolveKeepAliveBaseKey(route))
    window.setTimeout(() => {
        refreshSpinning.value = false
    }, 1000)
}

const cycleColorMode = () => {
    const current = colorMode.value
    if (current === 'light') {
        setting.layout.colorMode = 'dark'
    } else if (current === 'dark') {
        setting.layout.colorMode = 'system'
    } else {
        setting.layout.colorMode = 'light'
    }
}

const onAccountMenuVisibleChange = (visible: boolean) => {
    accountMenuOpen.value = visible
}

type AccountCommand = 'workbench' | 'profile' | 'roles' | 'logout'

/** onAccountCommand 账号下拉：工作台 / 个人中心 / 切换角色 / 退出 */
const onAccountCommand = (command: string | number | object) => {
    switch (command as AccountCommand) {
        case 'workbench':
            void router.push({ name: 'root-default' })
            break
        case 'profile':
            void router.push({ name: 'account-profile' })
            break
        case 'roles':
            rolesDialogVisible.value = true
            break
        case 'logout':
            void logout()
            break
        default:
            break
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
    <div class="header-right">
        <div class="header-tools">
 
            <el-tooltip content="刷新当前页" placement="bottom">
                <button
                    type="button"
                    class="header-tool-btn"
                    aria-label="刷新当前页"
                    @click="refreshPage"
                >
                    <el-icon :size="18" :class="{ 'is-spinning': refreshSpinning }">
                        <Refresh />
                    </el-icon>
                </button>
            </el-tooltip>
            <el-tooltip :content="themeTooltip" placement="bottom">
                <button
                    type="button"
                    class="header-tool-btn"
                    :aria-label="themeTooltip"
                    @click="cycleColorMode"
                >
                    <el-icon :size="18">
                        <Sunny v-if="colorMode === 'light'" />
                        <Moon v-else-if="colorMode === 'dark'" />
                        <Monitor v-else />
                    </el-icon>
                </button>
            </el-tooltip>
            <el-tooltip content="个人偏好" placement="bottom">
                <button
                    type="button"
                    class="header-tool-btn"
                    aria-label="个人偏好"
                    @click="setting.showSetting()"
                >
                    <el-icon :size="18">
                        <Tools />
                    </el-icon>
                </button>
            </el-tooltip>
        </div>

        <div class="header-divider" role="separator" aria-hidden="true" />

        <el-dropdown
            trigger="click"
            placement="bottom-end"
            popper-class="ga-account-dropdown"
            @command="onAccountCommand"
            @visible-change="onAccountMenuVisibleChange"
        >
            <button type="button" class="account-trigger" aria-label="账号菜单">
                <el-avatar :size="32" :src="headerAvatarSrc || undefined">
                    {{ accountInitial }}
                </el-avatar>
                <span class="account-trigger-name">{{ accountName }}</span>
                <el-icon
                    :size="14"
                    class="account-trigger-arrow"
                    :class="{ 'is-open': accountMenuOpen }"
                >
                    <ArrowDown />
                </el-icon>
            </button>
            <template #dropdown>
                <el-dropdown-menu>
                    <el-dropdown-item disabled class="account-identity-item">
                        <div class="account-identity">
                            <el-avatar :size="36" :src="headerAvatarSrc || undefined">
                                {{ accountInitial }}
                            </el-avatar>
                            <div class="account-identity-meta">
                                <span class="account-name">{{ accountName }}</span>
                                <span v-if="accountUsername" class="account-username">{{
                                    accountUsername
                                }}</span>
                            </div>
                        </div>
                    </el-dropdown-item>
                    <el-dropdown-item :icon="HomeFilled" command="workbench" divided>
                        工作台
                    </el-dropdown-item>
                    <el-dropdown-item :icon="User" command="profile">个人中心</el-dropdown-item>
                    <el-dropdown-item v-if="hasRoles" :icon="Switch" command="roles">
                        切换角色
                    </el-dropdown-item>
                    <el-dropdown-item
                        :icon="SwitchButton"
                        command="logout"
                        divided
                        class="is-danger"
                    >
                        退出登录
                    </el-dropdown-item>
                </el-dropdown-menu>
            </template>
        </el-dropdown>
    </div>
    <switch-roles-dialog v-model="rolesDialogVisible" />
</template>

<style scoped>
.header-right {
    display: flex;
    align-items: center;
    height: 100%;
    gap: var(--ga-space-2);
    padding: 0 var(--ga-space-2);
}

.header-tools {
    display: flex;
    align-items: center;
    gap: var(--ga-space-1);
}

.header-tools :deep(.el-tooltip__trigger) {
    display: inline-flex;
}

.header-tool-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: var(--ga-radius-md);
    background: transparent;
    color: var(--ga-color-text-regular);
    cursor: pointer;
    transition:
        background-color var(--ga-duration) var(--ga-ease),
        color var(--ga-duration) var(--ga-ease);

    &:hover {
        background: color-mix(in srgb, var(--ga-color-fill) 72%, transparent);
        color: var(--ga-color-text);
    }

    &:focus-visible {
        outline: 2px solid color-mix(in srgb, var(--ga-color-primary) 36%, transparent);
        outline-offset: 1px;
    }
}

.header-tool-btn :deep(.is-spinning) {
    animation: header-refresh-spin 1s linear;
}

@keyframes header-refresh-spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

.header-divider {
    flex-shrink: 0;
    width: 1px;
    height: 24px;
    margin: 0 var(--ga-space-1);
    background: var(--ga-color-border-lighter);
}

.header-right :deep(.el-dropdown) {
    display: inline-flex;
    height: 100%;
    align-items: center;
}

.account-trigger {
    display: inline-flex;
    align-items: center;
    gap: var(--ga-space-2);
    box-sizing: border-box;
    margin: 0;
    padding: var(--ga-space-1) var(--ga-space-2);
    max-width: 220px;
    height: auto;
    min-height: 40px;
    border: 0;
    border-radius: var(--ga-radius-md);
    background: transparent;
    color: var(--ga-color-text);
    cursor: pointer;
    transition: background-color var(--ga-duration) var(--ga-ease);

    &:hover {
        background: color-mix(in srgb, var(--ga-color-fill) 72%, transparent);
    }

    &:focus-visible {
        outline: 2px solid color-mix(in srgb, var(--ga-color-primary) 36%, transparent);
        outline-offset: 1px;
    }
}

.account-trigger :deep(.el-avatar) {
    flex-shrink: 0;
    font-size: 13px;
    background: var(--ga-color-fill);
    color: var(--ga-color-text-regular);
    box-shadow: 0 0 0 1px var(--ga-color-border-lighter);
    transition: box-shadow var(--ga-duration) var(--ga-ease);
}

.account-trigger:hover :deep(.el-avatar),
.account-trigger:focus-visible :deep(.el-avatar) {
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--ga-color-primary) 36%, transparent);
}

.account-trigger-name {
    overflow: hidden;
    min-width: 0;
    max-width: 8rem;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.4;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.account-trigger-arrow {
    flex-shrink: 0;
    color: var(--ga-color-text-secondary);
    transition: transform var(--ga-duration) var(--ga-ease);
}

.account-trigger-arrow.is-open {
    transform: rotate(180deg);
}

@media (max-width: 767px) {
    .account-trigger-name {
        display: none;
    }
}
</style>

<style>
/* 下拉传送到 body：实色面板，不用玻璃 */
.ga-account-dropdown.el-popper,
.ga-account-dropdown .el-dropdown-menu {
    background: var(--ga-color-overlay);
}

.ga-account-dropdown {
    min-width: 220px;
}

.ga-account-dropdown .account-identity-item {
    height: auto;
    line-height: inherit;
    opacity: 1;
    cursor: default;
    color: inherit;
}

.ga-account-dropdown .account-identity-item:hover,
.ga-account-dropdown .account-identity-item:focus,
.ga-account-dropdown .account-identity-item:not(.is-disabled):hover,
.ga-account-dropdown .el-dropdown-menu__item.account-identity-item.is-disabled,
.ga-account-dropdown .el-dropdown-menu__item.account-identity-item.is-disabled:hover {
    background: transparent;
    color: inherit;
    cursor: default;
    opacity: 1;
}

.ga-account-dropdown .account-identity {
    display: flex;
    align-items: center;
    gap: var(--ga-space-3);
    padding: var(--ga-space-1) 0;
    min-width: 0;
}

.ga-account-dropdown .account-identity .el-avatar {
    flex-shrink: 0;
    font-size: 14px;
    background: var(--ga-color-fill);
    color: var(--ga-color-text-regular);
}

.ga-account-dropdown .account-identity-meta {
    display: flex;
    flex: 1;
    min-width: 0;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
}

.ga-account-dropdown .account-name {
    overflow: hidden;
    max-width: 100%;
    font-weight: 500;
    line-height: 1.4;
    color: var(--ga-color-text);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ga-account-dropdown .account-username {
    overflow: hidden;
    max-width: 100%;
    font-size: 12px;
    line-height: 1.4;
    color: var(--ga-color-text-secondary);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ga-account-dropdown .el-dropdown-menu__item.is-danger {
    color: var(--el-color-danger);
}

.ga-account-dropdown .el-dropdown-menu__item.is-danger:not(.is-disabled):hover,
.ga-account-dropdown .el-dropdown-menu__item.is-danger:not(.is-disabled):focus {
    color: var(--el-color-danger);
    background: var(--el-color-danger-light-9);
}
</style>
