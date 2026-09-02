import type { RoleRow } from '@/apis/system/auth/role'
import { del, get, post, put } from '@/apis/request'

/** RuleRow 规则行（树节点） */
export type RuleRow = {
    id: number
    scope: string
    type: string
    pid: number
    name: string
    path?: string | null
    method?: string | null
    component?: string | null
    /** el:组件名、url:图片地址、local:stem */
    icon?: string | null
    sort: number
    status: number
    /** {0:侧栏可见,1:展示全部时可发现,2:不入侧栏（目录）} */
    visibility?: number
    /** {1:页面缓存,0:离开即销毁} 仅 view/route */
    keep_alive?: number
    description?: string | null
    children?: RuleRow[]
}

/**
 * fetchTree 按 scope 取规则树
 */
export const fetchTree = async (scope: 'backend' | 'frontend') => {
    const data = await get<{ list: RuleRow[] }>('admin/system.auth.Rule/tree', { params: { scope } })
    return data.list
}

/**
 * fetchRead 规则详情（view/btn 含 backend_rule_ids）
 */
export const fetchRead = async (id: number) => {
    return get<RuleRow & { backend_rule_ids?: number[] }>('admin/system.auth.Rule/read', { params: { id } })
}

/** RulePayload 规则写入载荷 */
export type RulePayload = {
    scope: string
    type: string
    pid: number
    name: string
    path?: string | null
    method?: string | null
    component?: string | null
    icon?: string | null
    sort: number
    status: number
    /** {0:侧栏可见,1:展示全部时可发现,2:不入侧栏（目录）} */
    visibility?: number
    /** {1:页面缓存,0:离开即销毁} 仅 view/route */
    keep_alive?: number
    description?: string | null
    /** backend_rule_ids 前端 view/route/btn 关联的后端 api 规则 id */
    backend_rule_ids?: number[]
}

/**
 * saveRule 新建规则（view/btn 可附带 backend_rule_ids）
 */
export const saveRule = async (payload: RulePayload) => {
    return post<{ id: number }>('admin/system.auth.Rule/save', payload)
}

/**
 * updateRule 更新规则（view/btn 可附带 backend_rule_ids）
 */
export const updateRule = async (id: number, payload: RulePayload) => {
    return put<null>('admin/system.auth.Rule/update?id=' + id, payload)
}

/**
 * sortRules 批量更新规则排序（可附带 pid 改层级）
 */
export const sortRules = async (items: { id: number; sort: number; pid?: number }[]) => {
    return put<null>('admin/system.auth.Rule/sort', { items })
}

/**
 * deleteRule 删除规则（单条）
 */
export const deleteRule = async (id: number) => {
    return del<{ deleted?: number }>('admin/system.auth.Rule/delete', { params: { id } })
}

/**
 * deleteRules 批量删除规则
 */
export const deleteRules = async (ids: number[]) => {
    return del<{ deleted: number }>('admin/system.auth.Rule/delete', { params: { ids: ids.join(',') } })
}

/**
 * bindApis 前端 view/btn 绑定后端 api
 */
export const bindApis = async (frontend_rule_id: number, backend_rule_ids: number[]) => {
    return post<null>('admin/system.auth.Rule/bindApis', {
        frontend_rule_id,
        backend_rule_ids,
    })
}

/** RuleRolesRes 前端规则已分配角色 + 角色树 */
export type RuleRolesRes = {
    role_ids: number[]
    roles: RoleRow[]
}

/**
 * fetchRoles 某条前端规则已分配的角色
 */
export const fetchRoles = async (id: number) => {
    return get<RuleRolesRes>('admin/system.auth.Rule/roles', { params: { id } })
}

/**
 * assignRoles 全量覆盖某条前端规则与角色的关联（无父子继承）
 */
export const assignRoles = async (id: number, role_ids: number[]) => {
    return put<null>('admin/system.auth.Rule/assignRoles?id=' + id, { role_ids })
}

/** RuleFrontendsRes 后端 api 已绑定的前端规则 + 前端规则树 */
export type RuleFrontendsRes = {
    frontend_rule_ids: number[]
    rules: RuleRow[]
}

/**
 * fetchFrontends 某条后端 api 已绑定的前端规则
 */
export const fetchFrontends = async (id: number) => {
    return get<RuleFrontendsRes>('admin/system.auth.Rule/frontends', { params: { id } })
}

/**
 * assignFrontends 全量覆盖某条后端 api 与前端 view/route/btn 的绑定
 */
export const assignFrontends = async (id: number, frontend_rule_ids: number[]) => {
    return put<null>('admin/system.auth.Rule/assignFrontends?id=' + id, { frontend_rule_ids })
}
