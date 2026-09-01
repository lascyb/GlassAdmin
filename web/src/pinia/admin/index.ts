import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { bindAdminRefreshSink } from '@/pinia/admin/syncRefreshUser'
import type { Admin, AdminLoginMethod, AdminLoginMethodPreference } from '@/pinia/admin/types'
import { useToken } from '@/pinia/admin/token'
import { useSystemConfig } from '@/pinia/system/config'

export type { Admin, AdminLoginMethod, AdminLoginMethodPreference } from '@/pinia/admin/types'

/** applyAdminUser 写入 user 并同步 enabled 角色 id/code */
const applyAdminUser = (admin: Admin) => {
    admin.role_codes = admin.roles?.filter((item) => item.enabled == 1)?.map((item) => item.code)
    admin.role_ids = admin.roles?.filter((item) => item.enabled == 1)?.map((item) => item.id)
    return admin
}

/**
 * useAdmin 登录用户态。令牌见 useToken。
 */
export const useAdmin = defineStore('admin', () => {
    const tokenStore = useToken()

    const data = ref<Admin | null>(null)
    const remember = ref(false)
    const loginMethod = ref<AdminLoginMethodPreference>('')
    const referer = ref("")

    const loggedIn = computed(() => !!data.value && tokenStore.valid)

    /**
     * init 登录成功：写入 user / remember，并把令牌交给 useToken
     */
    const init = (
        accessToken: string,
        refreshToken: string,
        admin: Admin,
        expireAt: number,
        refreshExpireAt: number,
        rememberLogin: boolean,
    ) => {
        tokenStore.setTokens(accessToken, refreshToken, expireAt, refreshExpireAt)
        data.value = applyAdminUser(admin)
        remember.value = rememberLogin
    }

    /** clear 退出登录：清 token、user、运行时配置缓存 */
    const clear = () => {
        tokenStore.clearToken()
        data.value = null
        useSystemConfig().clear()
    }

    /** normalizeRefererPath 将完整 URL 或路由路径规范为可 router.push 的 path */
    const normalizeRefererPath = (href: string): string => {
        const raw = href.trim()
        if (!raw) return ''
        try {
            if (raw.startsWith('http://') || raw.startsWith('https://')) {
                const u = new URL(raw)
                return `${u.pathname}${u.search}${u.hash}`
            }
        } catch {
            return ''
        }
        return raw.startsWith('/') ? raw : `/${raw}`
    }

    /** isLoginPath 是否登录页（不可作为回跳目标） */
    const isLoginPath = (path: string): boolean => {
        const p = path.split('?')[0]?.split('#')[0] ?? ''
        return p === '/login' || p.endsWith('/login')
    }

    /** setReferer 记录登录后回跳地址（仅首次，忽略空/登录页） */
    const setReferer = (href: string = '') => {
        if (referer.value !== '') return
        const path = normalizeRefererPath(href)
        if (!path || isLoginPath(path)) return
        referer.value = path
    }

    /** getReferer 取出并清空回跳地址，无有效值时返回 defaultPath */
    const getReferer = (defaultPath = '/') => {
        const r = referer.value
        referer.value = ''
        if (!r || isLoginPath(r)) return defaultPath
        return r
    }

    /** setLoginMethod 持久化最近使用的登录方式 */
    const setLoginMethod = (method: AdminLoginMethod) => {
        loginMethod.value = method
    }

    return {
        data,
        remember,
        loginMethod,
        loggedIn,
        referer,
        init,
        clear,
        setReferer,
        getReferer,
        setLoginMethod,
    }
}, {
    persist: {
        pick: ['data', 'remember', 'loginMethod'],
    },
})

bindAdminRefreshSink((user) => {
    useAdmin().data = applyAdminUser(user)
})
