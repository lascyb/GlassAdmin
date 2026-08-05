import * as systemConfigApi from '@/apis/system/config'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

/**
 * useSystemConfig 登录后 Layout 拉取的运行时配置缓存
 */
export const useSystemConfig = defineStore('system_config', () => {
    /** values 启用中的配置 key => value */
    const values = ref<Record<string, string>>({})
    const loaded = ref(false)
    let pending: Promise<void> | null = null

    /** get 取单个配置，缺省返回 fallback */
    const get = (key: string, fallback = ''): string => {
        const v = values.value[key]
        return v != null && v !== '' ? v : fallback
    }

    /** siteTitle 站点标题（site_title） */
    const siteTitle = computed(() => get('site_title', 'GlassAdmin'))

    /** load Layout 挂载时拉取一次；已加载或请求中则跳过 */
    const load = async (): Promise<void> => {
        if (loaded.value) {
            return
        }
        if (pending) {
            await pending
            return
        }
        pending = (async () => {
            try {
                values.value = await systemConfigApi.fetchRuntime()
                loaded.value = true
            } catch {
                // 保留已有缓存；首次失败时仍可用 fallback
            } finally {
                pending = null
            }
        })()
        await pending
    }

    /** clear 清空缓存（退出登录等） */
    const clear = () => {
        values.value = {}
        loaded.value = false
        pending = null
    }

    return {
        values,
        loaded,
        siteTitle,
        get,
        load,
        clear,
    }
})
