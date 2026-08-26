import { delData, getData, postData, putData } from '@/apis/http'

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
    const data = await getData<{ list: RoleRow[] }>('admin/auth.Role/tree')
    return data.list
}

/**
 * fetchRead 角色详情
 */
export const fetchRead = async (id: number) => {
    return getData<RoleDetail>('admin/auth.Role/read', { id })
}

/**
 * saveRole 新建角色
 */
export const saveRole = async (payload: { pid?: number; code: string; name: string; desc?: string; sort?: number; status?: number }) => {
    return postData<{ id: number }>('admin/auth.Role/save', payload)
}

/**
 * updateRole 更新角色
 */
export const updateRole = async (
    id: number,
    payload: { pid?: number; code: string; name: string; desc?: string; sort?: number; status?: number },
) => {
    return putData<null>('admin/auth.Role/update?id=' + id, payload)
}

/**
 * deleteRole 删除角色
 */
export const deleteRole = async (id: number) => {
    return delData<null>('admin/auth.Role/delete', { id })
}

/**
 * assignRules 扁平分配前端规则（无父子继承）
 */
export const assignRules = async (id: number, rule_ids: number[]) => {
    return putData<null>('admin/auth.Role/assignRules?id=' + id, { rule_ids })
}
