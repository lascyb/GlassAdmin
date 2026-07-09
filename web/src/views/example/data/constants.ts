import type { StatusMap } from '@/composables/types'

/** STATUS_MAP 状态 {1:启用, 0:禁用} */
export const STATUS_MAP: StatusMap = {
    1: { label: '启用', type: 'success' },
    0: { label: '禁用', type: 'info' },
}

/** LEVEL_MAP 等级 {1:普通, 2:重要, 3:紧急} */
export const LEVEL_MAP: StatusMap = {
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
