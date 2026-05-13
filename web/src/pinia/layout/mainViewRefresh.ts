import { defineStore } from 'pinia'
import { reactive } from 'vue'

/**
 * useMainViewRefresh 仅刷新当前路由对应页面：按 fullPath 单独递增 nonce，
 * 避免全局计数导致其它 keep-alive 缓存页 key 连带变化而被误卸载。
 */
export const useMainViewRefresh = defineStore('main_view_refresh', () => {
    const pathNonce = reactive<Record<string, number>>({})

    const bumpForPath = (fullPath: string) => {
        pathNonce[fullPath] = (pathNonce[fullPath] ?? 0) + 1
    }

    const nonceFor = (fullPath: string) => pathNonce[fullPath] ?? 0

    return { pathNonce, bumpForPath, nonceFor }
},{
    persist: true,
})
