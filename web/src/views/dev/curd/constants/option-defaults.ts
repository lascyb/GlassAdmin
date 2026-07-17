import type { CrudOptionItem } from '../types/config'

/** DEFAULT_STATUS_OPTIONS 状态类默认可跑 options */
export const DEFAULT_STATUS_OPTIONS: CrudOptionItem[] = [
    { value: 1, label: '启用', type: 'success' },
    { value: 0, label: '禁用', type: 'info' },
]

/** DEFAULT_SELECT_OPTIONS 下拉枚举默认可跑 options */
export const DEFAULT_SELECT_OPTIONS: CrudOptionItem[] = [
    { value: 1, label: '选项一', type: 'info' },
    { value: 2, label: '选项二', type: 'warning' },
    { value: 3, label: '选项三', type: 'danger' },
]

/** DEFAULT_OPTIONS 静态 options 默认可跑项 */
export const DEFAULT_OPTIONS: CrudOptionItem[] = [
    { value: 'a', label: '选项 A' },
    { value: 'b', label: '选项 B' },
    { value: 'c', label: '选项 C' },
]
