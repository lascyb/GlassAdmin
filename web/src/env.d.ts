/// <reference types="vite/client" />

interface ImportMetaEnv {
    /** 站点入口，不含 /admin（开发如 http://192.168.22.129:8080，生产如 /backend） */
    readonly VITE_API_BASE_URL?: string
    /** Pinia persist 全局 key 前缀（与 createPersistedState 的 key 对应，默认 __GA__） */
    readonly VITE_PERSIST_STORAGE_PREFIX?: string
}
