import { delData, getData, postData, putData } from '@/apis/http'

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
    children?: RuleRow[]
}

/**
 * fetchTree 按 scope 取规则树
 */
export const fetchTree = async (scope: 'backend' | 'frontend') => {
    const data = await getData<{ list: RuleRow[] }>('auth.Rule/tree', { scope })
    return data.list
}

/**
 * fetchRead 规则详情（view/btn 含 backend_rule_ids）
 */
export const fetchRead = async (id: number) => {
    return getData<RuleRow & { backend_rule_ids?: number[] }>('auth.Rule/read', { id })
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
    /** backend_rule_ids 前端 view/btn 关联的后端 api 规则 id */
    backend_rule_ids?: number[]
}

/**
 * saveRule 新建规则（view/btn 可附带 backend_rule_ids）
 */
export const saveRule = async (payload: RulePayload) => {
    return postData<{ id: number }>('auth.Rule/save', payload)
}

/**
 * updateRule 更新规则（view/btn 可附带 backend_rule_ids）
 */
export const updateRule = async (id: number, payload: RulePayload) => {
    return putData<null>('auth.Rule/update?id=' + id, payload)
}

/**
 * deleteRule 删除规则
 */
export const deleteRule = async (id: number) => {
    return delData<null>('auth.Rule/delete', { id })
}

/**
 * bindApis 前端 view/btn 绑定后端 api
 */
export const bindApis = async (frontend_rule_id: number, backend_rule_ids: number[]) => {
    return postData<null>('auth.Rule/bindApis', {
        frontend_rule_id,
        backend_rule_ids,
    })
}
