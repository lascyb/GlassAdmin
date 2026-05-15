import { del, get, post, put } from '@/utils/request'
import type { ApiEnvelope } from '@/apis'

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

function unwrap<T>(res: ApiEnvelope<T>): T {
    if (res.code !== 0) {
        throw new Error(res.msg || '请求失败')
    }
    return res.data
}

/**
 * fetchList 分页列表
 */
export const fetchList = async (params: { page?: number; limit?: number; keyword?: string }) => {
    const res = await get<ApiEnvelope<AdminListRes>>('auth/admin/list', params)
    return unwrap(res)
}

/**
 * fetchRead 详情
 */
export const fetchRead = async (id: number) => {
    const res = await get<ApiEnvelope<AdminRow>>('auth/admin/read', { id })
    return unwrap(res)
}

/**
 * saveAdmin 新建（password 为 RSA 密文；需 headers X-RSA-Serial）
 */
export const saveAdmin = async (
    payload: { username: string; nickname?: string; role_id: number; status: number; password: string },
    headers: { 'X-RSA-Serial': string },
) => {
    const res = await post<ApiEnvelope<{ id: number }>>('auth/admin/save', payload, { headers })
    return unwrap(res)
}

/**
 * updateAdmin 更新（password 可选，有则 RSA 密文）
 */
export const updateAdmin = async (
    id: number,
    payload: { nickname?: string; role_id: number; status: number; password?: string },
    headers?: { 'X-RSA-Serial': string },
) => {
    const res = await put<ApiEnvelope<null>>(
        'auth/admin/update?id=' + id,
        payload,
        headers ? { headers } : undefined,
    )
    return unwrap(res)
}

/**
 * deleteAdmin 删除
 */
export const deleteAdmin = async (id: number) => {
    const res = await del<ApiEnvelope<null>>('auth/admin/delete', { id })
    return unwrap(res)
}
