import { delData, getData, postData, putData, type HttpRequestOptions } from '@/apis/http'

/** AdminRoleBrief 管理员绑定的角色摘要 */
export type AdminRoleBrief = {
    id: number
    name: string
}

/** AdminRow 管理员列表行 */
export type AdminRow = {
    id: number
    username: string
    nickname?: string | null
    avatar?: string | null
    status: number
    role_ids?: number[]
    /** 预加载角色，与 with roles 字段 id,name 一致 */
    roles?: AdminRoleBrief[]
    create_time?: number | null
    update_time?: number | null
    last_login_time?: number | null
}

/** AdminListRes 分页列表 */
export type AdminListRes = {
    list: AdminRow[]
    total: number
    page: number
    limit: number
}

/**
 * fetchList 分页列表
 */
export const fetchList = async (params: {
    page?: number
    limit?: number
    keyword?: string
    username?: string
    nickname?: string
    status?: number
}) => {
    return getData<AdminListRes>('admin/auth.Admin/list', params)
}

/**
 * fetchRead 详情
 */
export const fetchRead = async (id: number) => {
    return getData<AdminRow>('admin/auth.Admin/read', { id })
}

/**
 * saveAdmin 新建（明文 password，经 encryptFields 自动 RSA）
 */
export const saveAdmin = async (
    payload: { username: string; nickname?: string; role_ids: number[]; status: number; password: string },
    options?: HttpRequestOptions,
) => {
    return postData<{ id: number }>('admin/auth.Admin/save', payload, {
        ...options,
        encryptFields: options?.encryptFields ?? ['password'],
    })
}

/**
 * updateAdmin 更新（password 可选明文，有则经 encryptFields 自动 RSA）
 */
export const updateAdmin = async (
    id: number,
    payload: { nickname?: string; role_ids: number[]; status: number; password?: string },
    options?: HttpRequestOptions,
) => {
    const encryptFields =
        options?.encryptFields ??
        (payload.password ? ['password'] : undefined)
    return putData<null>('admin/auth.Admin/update?id=' + id, payload, {
        ...options,
        encryptFields,
    })
}

/**
 * deleteAdmin 删除
 */
export const deleteAdmin = async (id: number) => {
    return delData<null>('admin/auth.Admin/delete', { id })
}
