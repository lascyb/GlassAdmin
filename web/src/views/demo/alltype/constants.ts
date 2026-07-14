import type { StatusMap } from '@/composables/types'

/** STATUS_MAP */
export const STATUS_MAP: StatusMap = {
    1: { label: '启用', type: 'success' },
    0: { label: '禁用', type: 'info' },
}

/** LEVEL_MAP */
export const LEVEL_MAP: StatusMap = {
    1: { label: '普通', type: 'info' },
    2: { label: '重要', type: 'warning' },
    3: { label: '紧急', type: 'danger' },
}

/** EXTRA_OPTIONS */
export const EXTRA_OPTIONS = [
    { value: 'notify', label: '通知' },
    { value: 'export', label: '导出' },
    { value: 'audit', label: '审计' },
]

