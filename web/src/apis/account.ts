import { getData, postFormDataData, putData } from '@/apis/http'

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

/**
 * fetchProfile 当前用户资料
 */
export const fetchProfile = async () => {
    return getData<ProfileRow>('account.Profile/read')
}

/**
 * updateProfile 更新昵称、可选密码（password 为 RSA 密文时需 headers）
 */
export const updateProfile = async (
    payload: { nickname?: string; password?: string },
    headers?: { 'X-RSA-Serial': string },
) => {
    return putData<null>('account.Profile/update', payload, headers ? { headers } : undefined)
}

/**
 * uploadProfileAvatar 上传当前用户头像（字段名 file）
 */
export const uploadProfileAvatar = async (formData: FormData) => {
    return postFormDataData<{ avatar: string }>('account.Profile/uploadAvatar', formData)
}

/**
 * fetchOperationLogs 操作日志分页
 */
export const fetchOperationLogs = async (params: { page?: number; limit?: number }) => {
    return getData<OperationLogsListRes>('account.OperationLog/index', params)
}

/**
 * fetchNotifications 通知分页；category 为 system|friend
 */
export const fetchNotifications = async (params: {
    page?: number
    limit?: number
    category?: string
}) => {
    return getData<NotificationsListRes>('account.Notification/index', params)
}

/**
 * markNotificationsRead 标记已读：ids 或 all
 */
export const markNotificationsRead = async (payload: { ids?: number[]; all?: boolean }) => {
    return putData<null>('account.Notification/markRead', payload)
}
