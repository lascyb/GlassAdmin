/// <reference types="vite/client" />

interface ImportMetaEnv {
    /** Pinia persist 全局 key 前缀（与 createPersistedState 的 key 对应，默认 __GA__） */
    readonly VITE_PERSIST_STORAGE_PREFIX?: string
}
