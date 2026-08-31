import type { StatusMap } from '@/composables/types'

/** ADMIN_STATUS_MAP 管理员状态 {1:正常, 0:禁用} */
export const ADMIN_STATUS_MAP: StatusMap = {
    1: { label: '正常', type: 'success' },
    0: { label: '禁用', type: 'info' },
}

/** OAUTH_PROVIDER_OPTIONS 三方登录筛选 */
export const OAUTH_PROVIDER_OPTIONS: { value: string; label: string }[] = [
    { value: 'feishu', label: '飞书' },
    { value: 'none', label: '未绑定' },
]
