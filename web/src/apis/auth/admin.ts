import { delData, getData, postData, putData } from '@/apis/http'

/** AdminRow 管理员列表行 */
export type AdminRow = {
    id: number
    username: string
    nickname?: string | null
    avatar?: string | null
    status: number
    role_id?: number | null
    /** 预加载角色，与 with role 字段 id,name 一致 */
    role?: { id: number; name: string } | null
    create_time?: string | null
    update_time?: string | null
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
export const fetchList = async (params: { page?: number; limit?: number; keyword?: string }) => {
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
    payload: { username: string; nickname?: string; role_id: number; status: number; password: string },
    headers: { 'X-RSA-Serial': string },
) => {
    return postData<{ id: number }>('auth.Admin/save', payload, { headers })
}

/**
 * updateAdmin 更新（password 可选，有则 RSA 密文）
 */
export const updateAdmin = async (
    id: number,
    payload: { nickname?: string; role_id: number; status: number; password?: string },
    headers?: { 'X-RSA-Serial': string },
) => {
    return putData<null>(
        'auth.Admin/update?id=' + id,
        payload,
        headers ? { headers } : undefined,
    )
}

/**
 * deleteAdmin 删除
 */
export const deleteAdmin = async (id: number) => {
    return delData<null>('auth.Admin/delete', { id })
}
