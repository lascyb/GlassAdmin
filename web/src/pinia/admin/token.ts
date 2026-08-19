import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import type { ApiEnvelope } from '@/apis/envelope'
import { unwrap } from '@/apis/envelope'
import type { Admin } from '@/pinia/admin'

/** useToken 登录令牌（localStorage） */
export const useToken = defineStore(
    'token',
    () => {
        /** access_token（opaque），请求头 Authorization 使用 */
        const token = ref('')
        /** refresh_token（opaque），仅用于换发 access */
        const refreshToken = ref('')
        /** expireAt access 过期秒级时间戳；为 0 视为无效 */
        const expireAt = ref(0)
        /** refreshExpireAt refresh 过期秒级时间戳；为 0 视为不可续期 */
        const refreshExpireAt = ref(0)

        /** setTokens 写入 access、refresh 及各自过期时间戳（秒） */
        const setTokens = (
            access: string,
            refresh: string,
            expireAtValue: number,
            refreshExpireAtValue: number,
        ) => {
            token.value = access
            refreshToken.value = refresh
            expireAt.value = expireAtValue
            refreshExpireAt.value = refreshExpireAtValue
        }

        /** clearToken 清空双令牌与过期时间戳 */
        const clearToken = () => {
            token.value = ''
            refreshToken.value = ''
            expireAt.value = 0
            refreshExpireAt.value = 0
        }

        /** accessValid access 存在且未过期 */
        const accessValid = computed(() => {
            if (!token.value) return false
            if (expireAt.value <= 0) return false
            return Math.floor(Date.now() / 1000) < expireAt.value
        })

        /** canRefresh 存在未过期的 refresh，可换发新 access */
        const canRefresh = computed(() => {
            if (!refreshToken.value) return false
            if (refreshExpireAt.value <= 0) return false
            return Math.floor(Date.now() / 1000) < refreshExpireAt.value
        })

        /** valid access 有效，或 refresh 仍可续期 */
        const valid = computed(() => accessValid.value || canRefresh.value)

        /**
         * tryRefreshAccessToken 用 refresh_token 换发新令牌（裸 fetch，避免与 useFetch 循环依赖）
         */
        const tryRefreshAccessToken = async (): Promise<boolean> => {
            const rt = refreshToken.value
            if (!rt) return false
            const now = Math.floor(Date.now() / 1000)
            if (refreshExpireAt.value > 0 && refreshExpireAt.value <= now) {
                return false
            }
            const base = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')
            const url = `${base}/admin/account.login.Refresh/token`
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
                setTokens(
                    payload.access_token,
                    payload.refresh_token,
                    payload.expire_at,
                    payload.refresh_expire_at,
                )
                if (payload.user) {
                    const { useAdmin } = await import('@/pinia/admin')
                    useAdmin().data = payload.user
                }
                return true
            } catch {
                return false
            }
        }

        /**
         * tryRefreshAccessTokenIfNeeded access 将过期或即将过期时尝试刷新。
         * @returns true 会话可继续；false 需重新登录（失败时会 clearToken）
         */
        const tryRefreshAccessTokenIfNeeded = async (): Promise<boolean> => {
            const now = Math.floor(Date.now() / 1000)

            if (token.value && expireAt.value > now + 900) {
                return true
            }

            const accessStillOk = !!(token.value && expireAt.value > now)
            const refreshUsable =
                !!refreshToken.value && (refreshExpireAt.value <= 0 || refreshExpireAt.value > now)

            if (!refreshUsable) {
                if (!accessStillOk) {
                    clearToken()
                }
                return accessStillOk
            }

            const ok = await tryRefreshAccessToken()
            if (!ok) {
                clearToken()
                return false
            }
            return true
        }

        return {
            token,
            refreshToken,
            expireAt,
            refreshExpireAt,
            accessValid,
            canRefresh,
            valid,
            setTokens,
            clearToken,
            tryRefreshAccessToken,
            tryRefreshAccessTokenIfNeeded,
        }
    },
    {
        persist: {
            storage: localStorage,
            pick: ['token', 'refreshToken', 'expireAt', 'refreshExpireAt'],
        },
    },
)
