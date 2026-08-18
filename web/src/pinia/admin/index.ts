import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import type { ApiEnvelope } from '@/apis/envelope'
import { unwrap } from '@/apis/envelope'
import { useAdminTokenLocal, useAdminTokenSession } from '@/pinia/admin/token'
import { useSystemConfig } from '@/pinia/system/config'

/** AdminUser 登录后的后台管理员信息 */
export type Admin = {
    id: number
    username: string
    nickname: string
    /** 头像相对路径（public/storage） */
    avatar?: string | null
    /** 绑定的角色 auth_role.id 列表（当前生效） */
    role_ids?: number[]
    /** 绑定的角色 auth_role.id 列表（当前生效） */
    role_codes?: string[]
    /** 已分配角色（含 enabled） */
    roles?: { id: number; code: string; name: string; enabled: number }[]
}

export type AdminLoginMethod = 'password' | 'feishu'
/** 空字符串表示尚未选择登录方式偏好 */
export type AdminLoginMethodPreference = AdminLoginMethod | ''

/**
 * useAdmin 登录态唯一入口。
 * remember/user 由本 store 持久化（默认 localStorage）；
 * token 拆分到 admin_token_session / admin_token_local，由 remember 决定使用哪一侧。
 */
export const useAdmin = defineStore('admin', () => {
    const tokenSession = useAdminTokenSession()
    const tokenLocal = useAdminTokenLocal()

    const data = ref<Admin | null>(null)
    const remember = ref(false)
    const loginMethod = ref<AdminLoginMethodPreference>('')
    const referer = ref("")

    /** activeTokenStore 由 remember 选定的 token 容器 */
    const activeTokenStore = computed(() => (remember.value ? tokenLocal : tokenSession))

    const token = computed(() => activeTokenStore.value.token)
    const refreshToken = computed(() => activeTokenStore.value.refreshToken)
    const expireAt = computed(() => activeTokenStore.value.expireAt)
    const refreshExpireAt = computed(() => activeTokenStore.value.refreshExpireAt)
    const tokenValid = computed(() => activeTokenStore.value.valid)
    const loggedIn = computed(() => !!data.value && tokenValid.value)

    /**
     * init 登录成功：写入 remember / user，并把 access、refresh 写入 remember 对应侧、清掉另一侧
     * @param accessToken 访问令牌（opaque）
     * @param refreshToken 刷新令牌
     * @param admin 用户信息
     * @param expireAt access 过期秒级时间戳
     * @param refreshExpireAt refresh 过期秒级时间戳
     * @param rememberLogin 是否勾选「记住我」
     */
    const init = (
        accessToken: string,
        refreshToken: string,
        admin: Admin,
        expireAt: number,
        refreshExpireAt: number,
        rememberLogin: boolean,
    ) => {
        const inactive = rememberLogin ? tokenSession : tokenLocal
        const target = rememberLogin ? tokenLocal : tokenSession
        inactive.clearToken()
        target.setTokens(accessToken, refreshToken, expireAt, refreshExpireAt)
        data.value = admin
        data.value.role_codes = admin.roles?.filter(item => item.enabled == 1)?.map(item => item.code)
        data.value.role_ids = admin.roles?.filter(item => item.enabled == 1)?.map(item => item.id)
        remember.value = rememberLogin
    }

    /** clear 退出登录：清两侧 token、user、remember、运行时配置缓存 */
    const clear = () => {
        tokenSession.clearToken()
        tokenLocal.clearToken()
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

    /**
     * tryRefreshAccessToken 用 refresh_token 换发新令牌（裸 fetch，避免与 useFetch 循环依赖）
     */
    const tryRefreshAccessToken = async (): Promise<boolean> => {
        const store = activeTokenStore.value
        const rt = store.refreshToken
        if (!rt) return false
        const now = Math.floor(Date.now() / 1000)
        if (store.refreshExpireAt > 0 && store.refreshExpireAt <= now) {
            return false
        }
        const base = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')
        const url = `${base}/account.login.Refresh/token`
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ refresh_token: rt }),
            })
            const json = (await res.json()) as ApiEnvelope<{
                access_token: string
                refresh_token: string
                expire_at: number
                refresh_expire_at: number
                user?: Admin
            }>
            const payload = unwrap(json)
            if (!payload.access_token || !payload.refresh_token || !payload.expire_at || !payload.refresh_expire_at) {
                return false
            }
            store.setTokens(
                payload.access_token,
                payload.refresh_token,
                payload.expire_at,
                payload.refresh_expire_at,
            )
            if (payload.user) {
                data.value = payload.user
            }
            return true
        } catch {
            return false
        }
    }

    /**
     * tryRefreshAccessTokenIfNeeded access 将过期或即将过期时尝试刷新。
     * @returns true 会话可继续；false 需重新登录（失败时会 clear）
     */
    const tryRefreshAccessTokenIfNeeded = async (): Promise<boolean> => {
        const store = activeTokenStore.value
        const now = Math.floor(Date.now() / 1000)

        // access 仍充足，无需刷新
        if (store.token && store.expireAt > now + 900) {
            return true
        }

        const accessStillOk = !!(store.token && store.expireAt > now)
        const refreshUsable =
            !!store.refreshToken &&
            (store.refreshExpireAt <= 0 || store.refreshExpireAt > now)

        // 无可用 refresh：仅当 access 尚未过期时继续
        if (!refreshUsable) {
            if (!accessStillOk) {
                clear()
            }
            return accessStillOk
        }

        const ok = await tryRefreshAccessToken()
        if (!ok) {
            // 服务端拒绝或网络失败：清登录态，避免 canRefresh 仍为 true 而停留在页面
            clear()
            return false
        }
        return true
    }

    return {
        token,
        refreshToken,
        expireAt,
        refreshExpireAt,
        tokenValid,
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
        tryRefreshAccessToken,
        tryRefreshAccessTokenIfNeeded,
    }
}, {
    persist: {
        pick: ['data', 'remember', 'loginMethod'],
    },
})
