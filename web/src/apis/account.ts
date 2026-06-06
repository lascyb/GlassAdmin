import type { ApiEnvelope } from '@/apis'
import { get, postFormData, put } from '@/utils/request'

/** ProfileRow 当前用户资料 */
export type ProfileRow = {
    id: number
    username: string
    nickname?: string | null
    avatar?: string | null
    role_id?: number | null
    status: number
    role?: { id: number; name: string } | null
    create_time?: string | null
    update_time?: string | null
}

/** OperationLogRow 操作日志行 */
export type OperationLogRow = {
    id: number
    admin_id: number
    action: string
    summary: string
    ip?: string | null
    create_time?: string | null
}

/** NotificationRow 通知行 */
export type NotificationRow = {
    id: number
    admin_id: number
    category: string
    title: string
    content?: string | null
    read_at?: string | null
    create_time?: string | null
}

/** NotificationsListRes 通知列表 */
export type NotificationsListRes = {
    list: NotificationRow[]
    total: number
    page: number
    limit: number
    unread_count: number
}

/** OperationLogsListRes 操作日志分页 */
export type OperationLogsListRes = {
    list: OperationLogRow[]
    total: number
    page: number
    limit: number
}

function unwrap<T>(res: ApiEnvelope<T>): T {
    if (res.code !== 0) {
        throw new Error(res.msg || '请求失败')
    }
    return res.data
}

/**
 * fetchProfile 当前用户资料
 */
export const fetchProfile = async () => {
    const res = await get<ApiEnvelope<ProfileRow>>('account.Profile/read')
    return unwrap(res)
}

/**
 * updateProfile 更新昵称、可选密码（password 为 RSA 密文时需 headers）
 */
export const updateProfile = async (
    payload: { nickname?: string; password?: string },
    headers?: { 'X-RSA-Serial': string },
) => {
    const res = await put<ApiEnvelope<null>>('account.Profile/update', payload, headers ? { headers } : undefined)
    return unwrap(res)
}

/**
 * uploadProfileAvatar 上传当前用户头像（字段名 file）
 */
export const uploadProfileAvatar = async (formData: FormData) => {
    const res = await postFormData<ApiEnvelope<{ avatar: string }>>('account.Profile/uploadAvatar', formData)
    return unwrap(res)
}

/**
 * fetchOperationLogs 操作日志分页
 */
export const fetchOperationLogs = async (params: { page?: number; limit?: number }) => {
    const res = await get<ApiEnvelope<OperationLogsListRes>>('account.OperationLog/index', params)
    return unwrap(res)
}

/**
 * fetchNotifications 通知分页；category 为 system|friend
 */
export const fetchNotifications = async (params: {
    page?: number
    limit?: number
    category?: string
}) => {
    const res = await get<ApiEnvelope<NotificationsListRes>>('account.Notification/index', params)
    return unwrap(res)
}

/**
 * markNotificationsRead 标记已读：ids 或 all
 */
export const markNotificationsRead = async (payload: { ids?: number[]; all?: boolean }) => {
    const res = await put<ApiEnvelope<null>>('account.Notification/markRead', payload)
    return unwrap(res)
}
