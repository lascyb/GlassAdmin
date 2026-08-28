import { normalizeColorMode, useLayoutSetting, type ColorMode } from '@/pinia/layout/setting'
import { usePreferredDark } from '@vueuse/core'
import { computed, watch } from 'vue'

/** applyColorModeClass 按偏好给 html 打 dark class */
export const applyColorModeClass = (mode: ColorMode, prefersDark: boolean) => {
    const dark = mode === 'dark' || (mode === 'system' && prefersDark)
    document.documentElement.classList.toggle('dark', dark)
}

/** useResolvedDark 当前是否应使用暗色（含跟随系统） */
export const useResolvedDark = () => {
    const setting = useLayoutSetting()
    const prefersDark = usePreferredDark()
    return computed(() => {
        const mode = normalizeColorMode(setting.layout.colorMode)
        return mode === 'dark' || (mode === 'system' && prefersDark.value)
    })
}

/**
 * useAppColorMode 把个人偏好同步到 html.dark。
 * 仅在 App 入口调用一次；跟随系统时响应 prefers-color-scheme。
 */
export const useAppColorMode = () => {
    const setting = useLayoutSetting()
    const prefersDark = usePreferredDark()
    const colorMode = computed({
        get: (): ColorMode => normalizeColorMode(setting.layout.colorMode),
        set: (v: ColorMode) => {
            setting.layout.colorMode = v
        },
    })
    const isDark = computed(
        () => colorMode.value === 'dark' || (colorMode.value === 'system' && prefersDark.value),
    )

    watch(
        [colorMode, prefersDark],
        ([mode, pref]) => {
            applyColorModeClass(mode, pref)
            document.documentElement.style.colorScheme =
                mode === 'dark' || (mode === 'system' && pref) ? 'dark' : 'light'
        },
        { immediate: true },
    )

    return { colorMode, isDark, prefersDark }
}

/** readThemeColor 读取根节点 CSS 变量（图表轴色等无法直接用 var() 时） */
export const readThemeColor = (name: string, fallback: string): string => {
    if (typeof document === 'undefined') {
        return fallback
    }
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim()
    return value || fallback
}

/** chartChromeColors ECharts 坐标轴 / 提示框随主题变化 */
export const chartChromeColors = () => ({
    text: readThemeColor('--ga-color-text-secondary', '#909399'),
    line: readThemeColor('--ga-color-border', '#dcdfe6'),
    split: readThemeColor('--ga-color-border-lighter', '#ebeef5'),
    bg: readThemeColor('--ga-color-bg', '#ffffff'),
    fg: readThemeColor('--ga-color-text', '#303133'),
})
