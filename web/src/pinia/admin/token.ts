import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

/** AdminTokenSetup token 容器 setup，session/local 共用同一份实现 */
function createAdminTokenSetup() {
    const token = ref('')
    /** expireAt 秒级时间戳；为 0 视为无效（防止前端使用未初始化数据） */
    const expireAt = ref(0)

    /** setToken 写入 token 与过期时间戳（秒） */
    const setToken = (value: string, expireAtValue: number) => {
        token.value = value
        expireAt.value = expireAtValue
    }

    /** clearToken 清空 token 与过期时间戳 */
    const clearToken = () => {
        token.value = ''
        expireAt.value = 0
    }

    /** valid token 存在且未过期 */
    const valid = computed(() => {
        if (!token.value) return false
        if (expireAt.value <= 0) return false
        return Math.floor(Date.now() / 1000) < expireAt.value
    })

    return {
        token,
        expireAt,
        valid,
        setToken,
        clearToken,
    }
}

/** useAdminTokenSession 当前标签会话 token 容器（sessionStorage） */
export const useAdminTokenSession = defineStore('token_session', createAdminTokenSetup, {
    persist: {
        storage: sessionStorage,
        pick: ['token', 'expireAt'],
    },
})

/** useAdminTokenLocal 跨标签持久 token 容器（localStorage，对应「记住我」） */
export const useAdminTokenLocal = defineStore('token_local', createAdminTokenSetup, {
    persist: {
        storage: localStorage,
        pick: ['token', 'expireAt'],
    },
})
