/** 主题色默认值，与 style.css :root --ga-color-primary 一致 */
export const THEME_COLOR_DEFAULT = '#409eff'

/** 主题配置 schema 版本；破坏性变更时递增，hydrate 会回落默认主色 */
export const THEME_SETTINGS_VERSION = 1

/** 主色上的浅色字 */
export const ON_PRIMARY_LIGHT = '#ffffff'

/** 主色上的深色字，对齐 --ga-color-text */
export const ON_PRIMARY_DARK = '#303133'

export type Rgb = { r: number; g: number; b: number }

/** parseHexColor 解析 #rgb / #rrggbb / #rrggbbaa，非法返回 null */
export const parseHexColor = (v: unknown): Rgb | null => {
    if (typeof v !== 'string') {
        return null
    }
    const raw = v.trim().toLowerCase()
    const m = /^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/.exec(raw)
    const captured = m?.[1]
    if (!captured) {
        return null
    }
    let hex = captured
    if (hex.length === 3) {
        hex = hex
            .split('')
            .map((c) => c + c)
            .join('')
    }
    return {
        r: parseInt(hex.slice(0, 2), 16),
        g: parseInt(hex.slice(2, 4), 16),
        b: parseInt(hex.slice(4, 6), 16),
    }
}

/** rgbToHex 规范化为 #rrggbb */
export const rgbToHex = (rgb: Rgb): string => {
    const part = (n: number) =>
        Math.min(255, Math.max(0, Math.round(n)))
            .toString(16)
            .padStart(2, '0')
    return `#${part(rgb.r)}${part(rgb.g)}${part(rgb.b)}`
}

/** normalizeThemeColor 非法或缺失时回落默认主色，统一小写 #rrggbb */
export const normalizeThemeColor = (v: unknown): string => {
    const rgb = parseHexColor(v)
    if (!rgb) {
        return THEME_COLOR_DEFAULT
    }
    return rgbToHex(rgb)
}

/** srgbChannelToLinear WCAG 相对亮度通道 */
const srgbChannelToLinear = (c: number): number => {
    const s = c / 255
    return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4
}

/** relativeLuminance WCAG 相对亮度 0–1 */
export const relativeLuminance = (rgb: Rgb): number => {
    const r = srgbChannelToLinear(rgb.r)
    const g = srgbChannelToLinear(rgb.g)
    const b = srgbChannelToLinear(rgb.b)
    return 0.2126 * r + 0.7152 * g + 0.0722 * b
}

/** contrastRatio 两亮度的对比度 */
export const contrastRatio = (l1: number, l2: number): number => {
    const [a, b] = l1 > l2 ? [l1, l2] : [l2, l1]
    return (a + 0.05) / (b + 0.05)
}

/** 高于此相对亮度时主色上用深字（浅黄 / 浅绿等） */
const ON_PRIMARY_LUMINANCE_THRESHOLD = 0.45

/** onPrimaryOf 按相对亮度选择主色上的字色 */
export const onPrimaryOf = (color: string): string => {
    const rgb = parseHexColor(color) ?? parseHexColor(THEME_COLOR_DEFAULT)!
    return relativeLuminance(rgb) > ON_PRIMARY_LUMINANCE_THRESHOLD
        ? ON_PRIMARY_DARK
        : ON_PRIMARY_LIGHT
}

/** normalizeThemeVersion 非当前版本则视为不兼容 */
export const isCurrentThemeVersion = (v: unknown): boolean => v === THEME_SETTINGS_VERSION
