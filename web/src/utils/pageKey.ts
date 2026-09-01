import type { RouteLocationNormalized } from 'vue-router'

/** keep-alive 实例基础 key（不含 refresh nonce） */
export const resolveKeepAliveBaseKey = (
    route: Pick<RouteLocationNormalized, 'fullPath' | 'meta'>,
): string => {
    const k = route.meta.keepAliveKey
    if (typeof k === 'string' && k.trim() !== '') {
        return k.trim()
    }
    return route.fullPath
}

/** keep-alive component :key */
export const resolvePageKey = (route: RouteLocationNormalized, nonce: number): string =>
    `${resolveKeepAliveBaseKey(route)}:${nonce}`
