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
    /** 左侧菜单栏展开宽度（px） */
    asideWidth: number
    /** 侧栏手风琴：同时只展开一个子菜单 */
    menuAccordion: boolean
    /** 是否已用系统配置做过首次默认填充 */
    seededFromSystem: boolean
}

const CONTAINERS: LayoutContainer[] = ['Default', 'Double', 'Module']

/** 侧栏宽度允许范围 */
export const ASIDE_WIDTH_MIN = 160
export const ASIDE_WIDTH_MAX = 480
export const ASIDE_WIDTH_DEFAULT = 200

const isContainer = (v: string): v is LayoutContainer =>
    (CONTAINERS as string[]).includes(v)

/** clampAsideWidth 将宽度限制在合法区间 */
export const clampAsideWidth = (n: number): number => {
    if (!Number.isFinite(n)) {
        return ASIDE_WIDTH_DEFAULT
    }
    return Math.min(ASIDE_WIDTH_MAX, Math.max(ASIDE_WIDTH_MIN, Math.round(n)))
}

/** persistKey 与 pinia-plugin-persistedstate 全局 key 规则一致 */
const persistKey = () => `${persistStorageKeyPrefix}layout_setting`

export const useLayoutSetting = defineStore(
    'layout_setting',
    () => {
        const layout = reactive<Layout>({
            showSetting: false,
            container: 'Default',
            showAllMenus: false,
            asideWidth: ASIDE_WIDTH_DEFAULT,
            menuAccordion: true,
            seededFromSystem: false,
        })
        const showSetting = () => {
            layout.showSetting = true
        }

        /** setAsideWidth 设置侧栏宽度并钳制范围 */
        const setAsideWidth = (n: number) => {
            layout.asideWidth = clampAsideWidth(n)
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
                // 旧本地偏好可能没有 asideWidth / menuAccordion，补默认并钳制
                layout.asideWidth = clampAsideWidth(layout.asideWidth ?? ASIDE_WIDTH_DEFAULT)
                layout.menuAccordion = !!layout.menuAccordion
                return
            }
            const cfg = useSystemConfig()
            layout.showAllMenus = cfg.getBool('layout_show_all_menus', false)
            layout.asideWidth = clampAsideWidth(
                cfg.getNumber('layout_aside_width', ASIDE_WIDTH_DEFAULT),
            )
            layout.menuAccordion = cfg.getBool('layout_menu_accordion', true)
            const container = cfg.getString('layout_container', 'Default')
            if (isContainer(container)) {
                layout.container = container
            }
            layout.seededFromSystem = true
        }

        return {
            layout,
            showSetting,
            setAsideWidth,
            applySystemDefaults,
        }
    },
    {
        persist: {
            pick: ['layout'],
        },
    },
)
