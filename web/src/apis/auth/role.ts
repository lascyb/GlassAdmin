import { del, get, post, put } from '@/utils/request'
import type { ApiEnvelope } from '@/apis'

/** RoleRow 角色树节点 */
export type RoleRow = {
    id: number
    pid: number
    name: string
    desc?: string | null
    sort: number
    status: number
    children?: RoleRow[]
}

/** RoleDetail 角色详情（含扁平 rule_ids） */
export type RoleDetail = RoleRow & {
    rule_ids: number[]
}

function unwrap<T>(res: ApiEnvelope<T>): T {
    if (res.code !== 0) {
        throw new Error(res.msg || '请求失败')
    }
    return res.data
}

/**
 * fetchTree 角色树
 */
export const fetchTree = async () => {
    const res = await get<ApiEnvelope<{ list: RoleRow[] }>>('auth.Role/tree')
    return unwrap(res).list
}

/**
 * fetchRead 角色详情
 */
export const fetchRead = async (id: number) => {
    const res = await get<ApiEnvelope<RoleDetail>>('auth.Role/read', { id })
    return unwrap(res)
}

/**
 * saveRole 新建角色
 */
export const saveRole = async (payload: { pid?: number; name: string; desc?: string; sort?: number; status?: number }) => {
    const res = await post<ApiEnvelope<{ id: number }>>('auth.Role/save', payload)
    return unwrap(res)
}

/**
 * updateRole 更新角色
 */
export const updateRole = async (
    id: number,
    payload: { pid?: number; name: string; desc?: string; sort?: number; status?: number },
) => {
    const res = await put<ApiEnvelope<null>>('auth.Role/update?id=' + id, payload)
    return unwrap(res)
}

/**
 * deleteRole 删除角色
 */
export const deleteRole = async (id: number) => {
    const res = await del<ApiEnvelope<null>>('auth.Role/delete', { id })
    return unwrap(res)
}

/**
 * assignRules 扁平分配前端规则（无父子继承）
 */
export const assignRules = async (id: number, rule_ids: number[]) => {
    const res = await put<ApiEnvelope<null>>('auth.Role/assignRules?id=' + id, { rule_ids })
    return unwrap(res)
}
