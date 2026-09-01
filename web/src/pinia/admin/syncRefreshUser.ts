import type { Admin } from '@/pinia/admin/types'

type RefreshUserSink = (user: Admin) => void

let refreshUserSink: RefreshUserSink | null = null

/** bindAdminRefreshSink 由 useAdmin 注册，token 刷新后回写用户信息 */
export function bindAdminRefreshSink(fn: RefreshUserSink) {
    refreshUserSink = fn
}

/** syncAdminRefreshUser token 换发成功后同步 user（避免 token ↔ admin 循环 import） */
export function syncAdminRefreshUser(user: Admin) {
    refreshUserSink?.(user)
}
