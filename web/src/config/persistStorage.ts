/** persistStorageKeyPrefix Pinia 持久化 key 前缀，读取 VITE_PERSIST_STORAGE_PREFIX，缺省为 __GA__ */
export const persistStorageKeyPrefix: string = import.meta.env.VITE_PERSIST_STORAGE_PREFIX ?? '__GA__'