import { defineStore } from 'pinia'
import { reactive } from 'vue'

import { useSystemConfig } from '@/pinia/system/config'
import {
    THEME_COLOR_DEFAULT,
    THEME_SETTINGS_VERSION,
    isCurrentThemeVersion,
    normalizeThemeColor,
} from '@/theme'

export type LayoutContainer = 'Default' | 'Double' | 'Module'

/** ColorMode 外观：浅色 / 深色 / 跟随系统 */
export type ColorMode = 'light' | 'dark' | 'system'

type Layout = {
    showSetting: boolean
    container: LayoutContainer
    /** 是否展示全部可发现菜单（含未授权） */
    showAllMenus: boolean
    /** 左侧菜单栏展开宽度（px） */
    asideWidth: number
    /** 侧栏手风琴：同时只展开一个子菜单 */
    menuAccordion: boolean
    /** 外观：浅色 / 深色 / 跟随系统（未选时默认跟随） */
    colorMode: ColorMode
    /** 品牌主题色（#rrggbb） */
    themeColor: string
    /** 主题字段 schema 版本；不匹配时回落默认主色 */
    themeVersion: number
    /** 是否已用系统配置做过首次默认填充 */
    seededFromSystem: boolean
}

const CONTAINERS: LayoutContainer[] = ['Default', 'Double', 'Module']

/** 侧栏宽度允许范围 */
export const ASIDE_WIDTH_MIN = 160
export const ASIDE_WIDTH_MAX = 480
export const ASIDE_WIDTH_DEFAULT = 200
/** 左侧双层：模块栏固定宽度（px） */
export const ASIDE_MODULE_RAIL_WIDTH = 75
/** 左侧双层：展开态侧栏建议最小总宽，保证子菜单区约等于单层默认宽 */
export const ASIDE_WIDTH_DOUBLE_MIN = ASIDE_WIDTH_DEFAULT + ASIDE_MODULE_RAIL_WIDTH

const isContainer = (v: string): v is LayoutContainer =>
    (CONTAINERS as string[]).includes(v)

/** normalizeColorMode 非法或缺失时回退为跟随系统 */
export const normalizeColorMode = (v: unknown): ColorMode => {
    if (v === 'light' || v === 'dark' || v === 'system') {
        return v
    }
    return 'system'
}

/** clampAsideWidth 将宽度限制在合法区间 */
export const clampAsideWidth = (n: number): number => {
    if (!Number.isFinite(n)) {
        return ASIDE_WIDTH_DEFAULT
    }
    return Math.min(ASIDE_WIDTH_MAX, Math.max(ASIDE_WIDTH_MIN, Math.round(n)))
}

/** ensureAsideWidthForDouble 双层布局下保证总宽不低于建议最小值 */
export const ensureAsideWidthForDouble = (asideWidth: number): number => {
    return clampAsideWidth(Math.max(asideWidth, ASIDE_WIDTH_DOUBLE_MIN))
}

export const useLayoutSetting = defineStore(
    'layout_setting',
    () => {
        const layout = reactive<Layout>({
            showSetting: false,
            container: 'Default',
            showAllMenus: false,
            asideWidth: ASIDE_WIDTH_DEFAULT,
            menuAccordion: true,
            colorMode: 'system',
            themeColor: THEME_COLOR_DEFAULT,
            themeVersion: THEME_SETTINGS_VERSION,
            seededFromSystem: false,
        })
        const showSetting = () => {
            layout.showSetting = true
        }

        /** setThemeColor 设置品牌色并规范化 */
        const setThemeColor = (color: string) => {
            layout.themeColor = normalizeThemeColor(color)
            layout.themeVersion = THEME_SETTINGS_VERSION
        }

        /** setAsideWidth 设置侧栏宽度并钳制范围 */
        const setAsideWidth = (n: number) => {
            layout.asideWidth = clampAsideWidth(n)
        }

        /** setContainer 切换菜单样式；切到双层时自动保证侧栏总宽 */
        const setContainer = (container: LayoutContainer) => {
            const prev = layout.container
            layout.container = container
            if (container === 'Double' && prev !== 'Double') {
                layout.asideWidth = ensureAsideWidthForDouble(layout.asideWidth)
            }
        }

        /**
         * applySystemDefaults 用系统配置填充个人偏好初始值。
         * 只看 seededFromSystem：persist 的 afterHydrate 会提前写出 localStorage，不能用有没有 key 判断。
         */
        const applySystemDefaults = () => {
            if (layout.seededFromSystem) {
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
            if (layout.container === 'Double') {
                layout.asideWidth = ensureAsideWidthForDouble(layout.asideWidth)
            }
            layout.colorMode = normalizeColorMode(layout.colorMode)
            layout.themeColor = normalizeThemeColor(
                cfg.getString('layout_theme_color', THEME_COLOR_DEFAULT),
            )
            layout.themeVersion = THEME_SETTINGS_VERSION
            layout.seededFromSystem = true
        }

        return {
            layout,
            showSetting,
            setAsideWidth,
            setThemeColor,
            setContainer,
            applySystemDefaults,
        }
    },
    {
        persist: {
            pick: ['layout'],
            afterHydrate: (ctx) => {
                const { layout } = ctx.store
                layout.colorMode = normalizeColorMode(layout.colorMode)
                layout.asideWidth = clampAsideWidth(layout.asideWidth ?? ASIDE_WIDTH_DEFAULT)
                if (layout.container === 'Double') {
                    layout.asideWidth = ensureAsideWidthForDouble(layout.asideWidth)
                }
                if (!isCurrentThemeVersion(layout.themeVersion)) {
                    layout.themeColor = THEME_COLOR_DEFAULT
                    layout.themeVersion = THEME_SETTINGS_VERSION
                } else {
                    layout.themeColor = normalizeThemeColor(layout.themeColor)
                }
            },
        },
    },
)
