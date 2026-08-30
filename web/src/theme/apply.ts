import { normalizeThemeColor, onPrimaryOf } from './color'

/**
 * applyDocumentTheme 唯一写入根节点主题色的入口。
 * 只写 --ga-color-primary / --ga-color-on-primary；EP 阶梯色由 style.css color-mix 派生。
 */
export const applyDocumentTheme = (themeColor: unknown): string => {
    const color = normalizeThemeColor(themeColor)
    if (typeof document === 'undefined') {
        return color
    }
    const root = document.documentElement
    root.style.setProperty('--ga-color-primary', color)
    root.style.setProperty('--ga-color-on-primary', onPrimaryOf(color))
    return color
}
