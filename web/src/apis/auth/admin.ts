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
    create_time?: string | null
    update_time?: string | null
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
    return getData<AdminListRes>('auth.Admin/list', params)
}

/**
 * fetchRead 详情
 */
export const fetchRead = async (id: number) => {
    return getData<AdminRow>('auth.Admin/read', { id })
}

/**
 * saveAdmin 新建（password 为 RSA 密文；需 headers X-RSA-Serial）
 */
export const saveAdmin = async (
    payload: { username: string; nickname?: string; role_ids: number[]; status: number; password: string },
    options: HttpRequestOptions & { headers: { 'X-RSA-Serial': string } },
) => {
    return postData<{ id: number }>('auth.Admin/save', payload, options)
}

/**
 * updateAdmin 更新（password 可选，有则 RSA 密文）
 */
export const updateAdmin = async (
    id: number,
    payload: { nickname?: string; role_ids: number[]; status: number; password?: string },
    options?: HttpRequestOptions,
) => {
    return putData<null>(
        'auth.Admin/update?id=' + id,
        payload,
        options,
    )
}

/**
 * deleteAdmin 删除
 */
export const deleteAdmin = async (id: number) => {
    return delData<null>('auth.Admin/delete', { id })
}
