/// <reference types="vite/client" />

interface ImportMetaEnv {
    /** 站点入口，不含 /admin（开发如 http://192.168.22.129:8080，生产如 /backend） */
    readonly VITE_API_BASE_URL?: string
    /** Pinia persist 全局 key 前缀（与 createPersistedState 的 key 对应，默认 __GA__） */
    readonly VITE_PERSIST_STORAGE_PREFIX?: string
}

declare module 'vue' {
    interface ComponentCustomOptions {
        /** Vite 插件注入：相对 views/ 的页面 key，供 v-auth 反查 */
        __gaView?: string
    }
}

declare module 'vue-router' {
    interface RouteMeta {
        /** 浏览器 / 页面标题 */
        title?: string
        /** @deprecated 兼容旧菜单路由，请用 title */
        name?: string
        /** iframe / 外链目标 */
        url?: string
        /** iframe 鉴权策略 ID */
        method?: string
        /** 无权限页关联规则 ID */
        ruleId?: number
        /** 是否未授权可发现路由 */
        unauthorized?: boolean
    }
}

export {}
