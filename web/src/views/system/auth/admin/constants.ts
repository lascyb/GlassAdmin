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

/** LOGIN_METHOD_OPTIONS 登录方式选项 */
export const LOGIN_METHOD_OPTIONS: { value: string; label: string }[] = [
    { value: 'password', label: '密码登录' },
    { value: 'feishu', label: '飞书登录' },
]

/** loginMethodLabel 登录方式展示文案 */
export const loginMethodLabel = (code: string): string => {
    const hit = LOGIN_METHOD_OPTIONS.find((item) => item.value === code)
    return hit?.label ?? code
}
