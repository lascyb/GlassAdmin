import { THEME_COLOR_DEFAULT } from './color'

export type ThemeColorPreset = {
    id: string
    label: string
    color: string
}

/** 个人偏好主题色色块（与默认蓝对齐 EP） */
export const THEME_COLOR_PRESETS: readonly ThemeColorPreset[] = [
    { id: 'default', label: '默认蓝', color: THEME_COLOR_DEFAULT },
    { id: 'violet', label: '雅紫', color: '#7c3aed' },
    { id: 'sky', label: '天蓝', color: '#1d84ff' },
    { id: 'green', label: '清新绿', color: '#16a34a' },
    { id: 'orange', label: '活力橙', color: '#f59e0b' },
    { id: 'rose', label: '玫红', color: '#e11d48' },
]
