import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

/** AdminTokenSetup token 容器 setup，session/local 共用同一份实现 */
function createAdminTokenSetup() {
    /** access_token（opaque），请求头 Authorization 使用 */
    const token = ref('')
    /** refresh_token（opaque），仅用于换发 access */
    const refreshToken = ref('')
    /** expireAt access 过期秒级时间戳；为 0 视为无效 */
    const expireAt = ref(0)

    /** setTokens 写入 access、refresh 与 access 过期时间戳（秒） */
    const setTokens = (access: string, refresh: string, expireAtValue: number) => {
        token.value = access
        refreshToken.value = refresh
        expireAt.value = expireAtValue
    }

    /** clearToken 清空双令牌与过期时间戳 */
    const clearToken = () => {
        token.value = ''
        refreshToken.value = ''
        expireAt.value = 0
    }

    /** valid access 存在且未过期 */
    const valid = computed(() => {
        if (!token.value) return false
        if (expireAt.value <= 0) return false
        return Math.floor(Date.now() / 1000) < expireAt.value
    })

    return {
        token,
        refreshToken,
        expireAt,
        valid,
        setTokens,
        clearToken,
    }
}

/** useAdminTokenSession 当前标签会话 token 容器（sessionStorage） */
export const useAdminTokenSession = defineStore('token_session', createAdminTokenSetup, {
    persist: {
        storage: sessionStorage,
        pick: ['token', 'refreshToken', 'expireAt'],
    },
})

/** useAdminTokenLocal 跨标签持久 token 容器（localStorage，对应「记住我」） */
export const useAdminTokenLocal = defineStore('token_local', createAdminTokenSetup, {
    persist: {
        storage: localStorage,
        pick: ['token', 'refreshToken', 'expireAt'],
    },
})
