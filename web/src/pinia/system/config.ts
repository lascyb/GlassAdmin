import * as systemConfigApi from '@/apis/system/config'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

/**
 * useSystemConfig 系统配置键值缓存（登录后布局等使用）
 */
export const useSystemConfig = defineStore('system_config', () => {
    /** values 启用中的配置 key => value */
    const values = ref<Record<string, string>>({})
    const loaded = ref(false)
    const loading = ref(false)
    let pending: Promise<void> | null = null

    /** get 取单个配置，缺省返回 fallback */
    const get = (key: string, fallback = ''): string => {
        const v = values.value[key]
        return v != null && v !== '' ? v : fallback
    }

    /** siteTitle 站点标题（site_title） */
    const siteTitle = computed(() => get('site_title', 'GlassAdmin'))

    /**
     * load 拉取配置；keys 为空则拉全部启用项
     */
    const load = async (keys?: string[]): Promise<void> => {
        if (pending) {
            await pending
            return
        }
        loading.value = true
        pending = (async () => {
            try {
                const map = await systemConfigApi.fetchValues(keys)
                if (keys?.length) {
                    values.value = { ...values.value, ...map }
                } else {
                    values.value = map
                }
                loaded.value = true
            } catch {
                // 保留已有缓存；首次失败时仍可用 fallback
            } finally {
                loading.value = false
                pending = null
            }
        })()
        await pending
    }

    /** ensureLoaded 未加载则拉取（默认含 site_title） */
    const ensureLoaded = async (keys: string[] = ['site_title']): Promise<void> => {
        if (loaded.value) return
        await load(keys)
    }

    /** clear 清空缓存（退出登录等） */
    const clear = () => {
        values.value = {}
        loaded.value = false
    }

    return {
        values,
        loaded,
        loading,
        siteTitle,
        get,
        load,
        ensureLoaded,
        clear,
    }
})
