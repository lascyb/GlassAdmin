import { del, get, post, put } from '@/utils/request'
import type { ApiEnvelope } from '@/apis'

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
    /** ep:组件名、url:图片地址 */
    icon?: string | null
    sort: number
    status: number
    children?: RuleRow[]
}

/**
 * unwrap 解析 code===0 的 data
 */
function unwrap<T>(res: ApiEnvelope<T>): T {
    if (res.code !== 0) {
        throw new Error(res.msg || '请求失败')
    }
    return res.data
}

/**
 * fetchTree 按 scope 取规则树
 */
export const fetchTree = async (scope: 'backend' | 'frontend') => {
    const res = await get<ApiEnvelope<{ list: RuleRow[] }>>('auth/rule/tree', { scope })
    return unwrap(res).list
}

/**
 * fetchRead 规则详情（view/btn 含 api_ids）
 */
export const fetchRead = async (id: number) => {
    const res = await get<ApiEnvelope<RuleRow & { api_ids?: number[] }>>('auth/rule/read', { id })
    return unwrap(res)
}

/**
 * saveRule 新建规则
 */
export const saveRule = async (payload: Record<string, unknown>) => {
    const res = await post<ApiEnvelope<{ id: number }>>('auth/rule/save', payload)
    return unwrap(res)
}

/**
 * updateRule 更新规则
 */
export const updateRule = async (id: number, payload: Record<string, unknown>) => {
    const res = await put<ApiEnvelope<null>>('auth/rule/update?id=' + id, payload)
    return unwrap(res)
}

/**
 * deleteRule 删除规则
 */
export const deleteRule = async (id: number) => {
    const res = await del<ApiEnvelope<null>>('auth/rule/delete', { id })
    return unwrap(res)
}

/**
 * bindApis 前端 view/btn 绑定后端 api
 */
export const bindApis = async (frontend_rule_id: number, api_rule_ids: number[]) => {
    const res = await post<ApiEnvelope<null>>('auth/rule/bindApis', {
        frontend_rule_id,
        api_rule_ids,
    })
    return unwrap(res)
}