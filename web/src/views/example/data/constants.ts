import type { StatusMap } from '@/composables/types'

/** DATA_STATUS_MAP 状态 {1:启用, 0:禁用} */
export const DATA_STATUS_MAP: StatusMap = {
    1: { label: '启用', type: 'success' },
    0: { label: '禁用', type: 'info' },
}

/** DATA_LEVEL_MAP 等级 {1:普通, 2:重要, 3:紧急} */
export const DATA_LEVEL_MAP: StatusMap = {
    1: { label: '普通', type: 'info' },
    2: { label: '重要', type: 'warning' },
    3: { label: '紧急', type: 'danger' },
}

/** EXTRA_OPTIONS 扩展项静态选项（json multi_select） */
export const EXTRA_OPTIONS = [
    { value: 'notify', label: '通知' },
    { value: 'export', label: '导出' },
    { value: 'audit', label: '审计' },
]

/** STATUS_MAP_REGISTRY schema statusMapKey 注册表 */
export const STATUS_MAP_REGISTRY: Record<string, StatusMap> = {
    DATA_STATUS_MAP,
    DATA_LEVEL_MAP,
}

/** OPTIONS_REGISTRY schema optionsKey 本地静态选项注册表 */
export const OPTIONS_REGISTRY: Record<string, Array<{ value: string | number; label: string }>> = {
    extraOptions: EXTRA_OPTIONS,
    levels: [
        { value: 1, label: '普通' },
        { value: 2, label: '重要' },
        { value: 3, label: '紧急' },
    ],
}
