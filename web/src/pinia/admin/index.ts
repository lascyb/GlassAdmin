import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import type { ApiEnvelope } from '@/apis/envelope'
import { unwrap } from '@/apis/envelope'
import { useAdminTokenLocal, useAdminTokenSession } from '@/pinia/admin/token'

/** AdminUser 登录后的后台管理员信息 */
export type Admin = {
    id: number
    username: string
    nickname: string
    /** 头像相对路径（public/storage） */
    avatar?: string | null
    /** 主角色 auth_role.id，未绑定为 null */
    role_id?: number | null
}

/**
 * useAdmin 登录态唯一入口。
 * remember/user 由本 store 持久化（默认 localStorage）；
 * token 拆分到 admin_token_session / admin_token_local，由 remember 决定使用哪一侧；
 * afterHydrate 校验 token 有效性，无 token 或已过期时清空 user/remember 与两侧 token。
 */
export const useAdmin = defineStore('admin', () => {
    const tokenSession = useAdminTokenSession()
    const tokenLocal = useAdminTokenLocal()

    const data = ref<Admin | null>(null)
    const remember = ref(false)
    const referer = ref("")

    /** activeTokenStore 由 remember 选定的 token 容器 */
    const activeTokenStore = computed(() => (remember.value ? tokenLocal : tokenSession))

    const token = computed(() => activeTokenStore.value.token)
    const refreshToken = computed(() => activeTokenStore.value.refreshToken)
    const expireAt = computed(() => activeTokenStore.value.expireAt)
    const tokenValid = computed(() => activeTokenStore.value.valid)
    const loggedIn = computed(() => !!data.value && tokenValid.value)

    /**
     * init 登录成功：写入 remember / user，并把 access、refresh 写入 remember 对应侧、清掉另一侧
     * @param accessToken 访问令牌（opaque）
     * @param refreshToken 刷新令牌
     * @param admin 用户信息
     * @param expireAt access 过期秒级时间戳
     * @param rememberLogin 是否勾选「记住我」
     */
    const init = (
        accessToken: string,
        refreshToken: string,
        admin: Admin,
        expireAt: number,
        rememberLogin: boolean,
    ) => {
        const inactive = rememberLogin ? tokenSession : tokenLocal
        const target = rememberLogin ? tokenLocal : tokenSession
        inactive.clearToken()
        target.setTokens(accessToken, refreshToken, expireAt)
        data.value = admin
        remember.value = rememberLogin
    }

    /** clear 退出登录：清两侧 token、user、remember */
    const clear = () => {
        tokenSession.clearToken()
        tokenLocal.clearToken()
        data.value = null
    }

    const setReferer = (href: string = "") => {
        if (referer.value == "") {
            referer.value = href
        }
    }
    const getReferer = (defaultPath="/") => {
        let r = referer.value
        referer.value = ""
        if (r==""){
            r = defaultPath
        }
        return r
    }

    /**
     * tryRefreshAccessToken 用 refresh_token 换发新令牌（裸 fetch，避免与 useFetch 循环依赖）
     */
    const tryRefreshAccessToken = async (): Promise<boolean> => {
        const store = activeTokenStore.value
        const rt = store.refreshToken
        if (!rt) return false
        const base = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')
        const url = `${base}/Login/refresh`
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
                user?: Admin
            }>
            const payload = unwrap(json)
            if (!payload.access_token || !payload.refresh_token || !payload.expire_at) {
                return false
            }
            store.setTokens(payload.access_token, payload.refresh_token, payload.expire_at)
            if (payload.user) {
                data.value = payload.user
            }
            return true
        } catch {
            return false
        }
    }

    /**
     * tryRefreshAccessTokenIfNeeded access 将过期或已过期且有 refresh 时尝试刷新
     */
    const tryRefreshAccessTokenIfNeeded = async (): Promise<void> => {
        const store = activeTokenStore.value
        if (!store.token || !store.refreshToken) return
        const now = Math.floor(Date.now() / 1000)
        if (store.expireAt > now + 900) return
        await tryRefreshAccessToken()
    }

    return {
        token,
        refreshToken,
        expireAt,
        tokenValid,
        data,
        remember,
        loggedIn,
        referer,
        init,
        clear,
        setReferer,
        getReferer,
        tryRefreshAccessToken,
        tryRefreshAccessTokenIfNeeded,
    }
}, {
    persist: {
        pick: ['data', 'remember'],
    },
})
