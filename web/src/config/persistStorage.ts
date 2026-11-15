/**
 * persistStorageKeyPrefix Pinia 持久化 key 前缀，全仓唯一真源。
 * main.ts 的 createPersistedState 由此派生；index.html 的首屏防闪脚本不能 import TS，
 * 保留等价的 %VITE_PERSIST_STORAGE_PREFIX% 占位符 + 同名 __GA__ fallback。
 */
export const persistStorageKeyPrefix: string =
    import.meta.env.VITE_PERSIST_STORAGE_PREFIX || '__GA__'
