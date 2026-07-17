import { getData, postFormDataData, putData, type HttpRequestOptions } from '@/apis/http'

/** AdminRoleBrief 用户绑定的角色 */
export type AdminRoleBrief = {
    id: number
    name: string
    enabled: number
}

/** ProfileRow 当前用户资料 */
export type ProfileRow = {
    id: number
    username: string
    nickname?: string | null
    avatar?: string | null
    role_ids?: number[]
    status: number
    roles?: AdminRoleBrief[]
    create_time?: string | null
    update_time?: string | null
}

/** SwitchRolesRes 切换生效角色响应 */
export type SwitchRolesRes = {
    role_ids: number[]
    roles: AdminRoleBrief[]
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
 * switchRoles 切换当前用户启用的角色
 */
export const switchRoles = async (role_ids: number[]) => {
    return putData<SwitchRolesRes>('account.Profile/switchRoles', { role_ids })
}

/**
 * updateProfile 更新昵称、可选密码（password 为 RSA 密文时需 headers / rsaRebuild）
 */
export const updateProfile = async (
    payload: { nickname?: string; password?: string },
    options?: HttpRequestOptions,
) => {
    return putData<null>('account.Profile/update', payload, options)
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
