import { defineStore } from 'pinia'
import { reactive } from 'vue'

import { persistStorageKeyPrefix } from '@/config/persistStorage'
import { useSystemConfig } from '@/pinia/system/config'

type LayoutContainer = 'Default' | 'Double' | 'Module'

type Layout = {
    showSetting: boolean
    container: LayoutContainer
    /** 是否展示全部可发现菜单（含未授权） */
    showAllMenus: boolean
    /** 是否已用系统配置做过首次默认填充 */
    seededFromSystem: boolean
}

const CONTAINERS: LayoutContainer[] = ['Default', 'Double', 'Module']

const isContainer = (v: string): v is LayoutContainer =>
    (CONTAINERS as string[]).includes(v)

/** persistKey 与 pinia-plugin-persistedstate 全局 key 规则一致 */
const persistKey = () => `${persistStorageKeyPrefix}layout_setting`

export const useLayoutSetting = defineStore(
    'layout_setting',
    () => {
        const layout = reactive<Layout>({
            showSetting: false,
            container: 'Default',
            showAllMenus: false,
            seededFromSystem: false,
        })
        const showSetting = () => {
            layout.showSetting = true
        }

        /**
         * applySystemDefaults 用系统配置填充个人偏好初始值。
         * 仅在本地尚无 layout_setting 持久化时应用；已有个人偏好则只打标不覆盖。
         */
        const applySystemDefaults = () => {
            if (layout.seededFromSystem) {
                return
            }
            let hasPersisted = false
            try {
                hasPersisted = !!localStorage.getItem(persistKey())
            } catch {
                hasPersisted = false
            }
            if (hasPersisted) {
                layout.seededFromSystem = true
                return
            }
            const cfg = useSystemConfig()
            const container = cfg.get('layout_container', 'Default')
            if (isContainer(container)) {
                layout.container = container
            }
            layout.showAllMenus = cfg.get('layout_show_all_menus', '0') === '1'
            layout.seededFromSystem = true
        }

        return {
            layout,
            showSetting,
            applySystemDefaults,
        }
    },
    {
        persist: {
            pick: ['layout'],
        },
    },
)
