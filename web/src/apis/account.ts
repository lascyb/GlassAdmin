import { getData, postFormDataData, putData, type HttpRequestOptions } from '@/apis/http'

/** AdminRoleBrief 用户绑定的角色 */
export type AdminRoleBrief = {
    id: number
    code: string
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
    create_time?: number | null
    update_time?: number | null
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
    summary: string
    ip?: string | null
    create_time?: string | null
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
    return getData<ProfileRow>('admin/account.Profile/read')
}

/**
 * fetchProfile 当前用户资料
 */
export const fetchRoles = async () => {
    return getData<ProfileRow>('admin/account.Profile/roles')
}
/**
 * switchRoles 切换当前用户启用的角色
 */
export const switchRoles = async (role_ids: number[]) => {
    return putData<SwitchRolesRes>('admin/account.Profile/switchRoles', { role_ids })
}

/**
 * updateProfile 更新昵称
 */
export const updateProfile = async (payload: { nickname?: string }) => {
    return putData<null>('admin/account.Profile/update', payload)
}

/**
 * changePassword 修改当前用户密码（明文 password，经 encryptFields 自动 RSA；需角色鉴权）
 */
export const changePassword = async (
    payload: { password: string },
    options?: HttpRequestOptions,
) => {
    return putData<null>('admin/account.Profile/changePassword', payload, {
        ...options,
        encryptFields: options?.encryptFields ?? ['password'],
    })
}

/**
 * uploadProfileAvatar 上传当前用户头像（字段名 file）
 */
export const uploadProfileAvatar = async (formData: FormData) => {
    return postFormDataData<{ avatar: string }>('admin/account.Profile/uploadAvatar', formData)
}

/**
 * fetchMyOperationLogs 当前用户操作日志分页
 */
export const fetchMyOperationLogs = async (params: { page?: number; limit?: number }) => {
    return getData<OperationLogsListRes>('admin/account.Profile/operationLogs', params)
}
