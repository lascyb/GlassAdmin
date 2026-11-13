import type { SystemConfigMap } from '@/apis/system/config'
import * as systemConfigApi from '@/apis/system/config'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

/**
 * useSystemConfig 登录后 Layout 拉取的运行时配置缓存
 */
export const useSystemConfig = defineStore('system_config', () => {
    /** values 启用中的配置 key => typed value */
    const values = ref<SystemConfigMap>({})
    const loaded = ref(false)
    let pending: Promise<void> | null = null

    /** getString 取 string 配置，缺省返回 fallback */
    const getString = (key: string, fallback = ''): string => {
        const v = values.value[key]
        if (typeof v === 'string' && v !== '') {
            return v
        }
        if (v != null && v !== '' && typeof v !== 'object') {
            return String(v)
        }
        return fallback
    }

    /** getBool 取 boolean 配置 */
    const getBool = (key: string, fallback = false): boolean => {
        const v = values.value[key]
        return typeof v === 'boolean' ? v : fallback
    }

    /** getNumber 取 number 配置 */
    const getNumber = (key: string, fallback = 0): number => {
        const v = values.value[key]
        return typeof v === 'number' && Number.isFinite(v) ? v : fallback
    }

    /** getStringArray 取 string[] 配置（如 multi_select） */
    const getStringArray = (key: string, fallback: string[] = []): string[] => {
        const v = values.value[key]
        return Array.isArray(v) ? v.map((item) => String(item)) : fallback
    }

    /** get 兼容旧调用，等同 getString */
    const get = (key: string, fallback = ''): string => getString(key, fallback)

    /** siteTitle 站点标题（site_title） */
    const siteTitle = computed(() => getString('site_title', 'GlassAdmin'))

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
        getString,
        getBool,
        getNumber,
        getStringArray,
        load,
        clear,
    }
})
