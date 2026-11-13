import { del, get, post, put } from '@/apis/request'

/** RoleRow 角色树节点 */
export type RoleRow = {
    id: number
    pid: number
    code: string
    name: string
    desc?: string | null
    sort: number
    status: number
    /** 绑定该角色的管理员数量 */
    admin_count?: number
    children?: RoleRow[]
}

/** RoleDetail 角色详情（含扁平 rule_ids） */
export type RoleDetail = RoleRow & {
    rule_ids: number[]
}

/**
 * fetchTree 角色树
 */
export const fetchTree = async () => {
    const data = await get<{ list: RoleRow[] }>('admin/system.auth.Role/tree')
    return data.list
}

/**
 * fetchRead 角色详情
 */
export const fetchRead = async (id: number) => {
    return get<RoleDetail>('admin/system.auth.Role/read', { params: { id } })
}

/**
 * saveRole 新建角色
 */
export const saveRole = async (payload: { pid?: number; code: string; name: string; desc?: string; sort?: number; status?: number }) => {
    return post<{ id: number }>('admin/system.auth.Role/save', payload)
}

/**
 * updateRole 更新角色
 */
export const updateRole = async (
    id: number,
    payload: { pid?: number; code: string; name: string; desc?: string; sort?: number; status?: number },
) => {
    return put<null>('admin/system.auth.Role/update?id=' + id, payload)
}

/**
 * deleteRole 删除角色
 */
export const deleteRole = async (id: number) => {
    return del<null>('admin/system.auth.Role/delete', { params: { id } })
}

/**
 * assignRules 扁平分配前端规则（无父子继承）
 */
export const assignRules = async (id: number, rule_ids: number[]) => {
    return put<null>('admin/system.auth.Role/assignRules?id=' + id, { rule_ids })
}

/** AssignAdminsResult 批量绑定的统计（skipped 为原本已拥有该角色、被跳过的数量） */
export type AssignAdminsResult = {
    added: number
    skipped: number
}

/**
 * assignAdmins 批量追加绑定管理员到角色（只新增关联，不解除已有角色）
 */
export const assignAdmins = async (id: number, admin_ids: number[]) => {
    return put<AssignAdminsResult>('admin/system.auth.Role/assignAdmins?id=' + id, { admin_ids })
}
