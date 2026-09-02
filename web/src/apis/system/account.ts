import { get, post, put, type AxiosRequestConfig } from '@/apis/request'
import type { Menu } from '@/layouts/components/menus/types'

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
    return get<ProfileRow>('admin/system.account.Profile/read')
}

/**
 * fetchProfile 当前用户资料
 */
export const fetchRoles = async () => {
    return get<ProfileRow>('admin/system.account.Profile/roles')
}
/**
 * switchRoles 切换当前用户启用的角色
 */
export const switchRoles = async (role_ids: number[]) => {
    return put<SwitchRolesRes>('admin/system.account.Profile/switchRoles', { role_ids })
}

/**
 * updateProfile 更新昵称
 */
export const updateProfile = async (payload: { nickname?: string }) => {
    return put<null>('admin/system.account.Profile/update', payload)
}

/**
 * changePassword 修改当前用户密码（明文 password，经 encryptFields 自动 RSA；需角色鉴权）
 */
export const changePassword = async (
    payload: { password: string },
    options?: AxiosRequestConfig,
) => {
    return put<null>('admin/system.account.Profile/changePassword', payload, {
        ...options,
        encryptFields: options?.encryptFields ?? ['password'],
    })
}

/**
 * uploadProfileAvatar 上传当前用户头像（字段名 file）
 */
export const uploadProfileAvatar = async (formData: FormData) => {
    return post<{ avatar: string }>('admin/system.account.Profile/uploadAvatar', formData)
}

/**
 * fetchMyOperationLogs 当前用户操作日志分页
 */
export const fetchMyOperationLogs = async (params: { page?: number; limit?: number }) => {
    return get<OperationLogsListRes>('admin/system.account.Profile/operationLogs', { params })
}

/** MenuDescribe 无权限页描述 */
export type MenuDescribe = {
    id: number
    name: string
    type: string
    path: string
    icon?: string | null
    description?: string | null
}

/**
 * fetchMenus 拉取当前用户动态菜单（需登录 token）
 *
 * @param catalog 是否包含可发现未授权菜单
 */
export const fetchMenus = async (catalog = false): Promise<{ menus: Menu[] }> => {
    return get<{ menus: Menu[] }>('admin/system.account.Menu/menus', {
        params: { catalog: catalog ? 1 : 0 },
    })
}

/**
 * fetchMenuDescribe 按规则 id 拉取描述（授权或可发现）
 */
export const fetchMenuDescribe = async (id: number) => {
    return get<MenuDescribe>('admin/system.account.Menu/describe', { params: { id } })
}
