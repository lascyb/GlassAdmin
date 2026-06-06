import type { ApiEnvelope } from '@/apis'
import {del, get, post, put} from '@/utils/request.ts'

/** SystemConfigRow 系统配置行 */
export type SystemConfigRow = {
    id: number
    key: string
    value: string
    group: string
    name: string
    description: string | null
    type: 'input' | 'textarea' | 'select' | 'multi_select' | 'switch' | 'number' | 'color'
    options: string | null
    sort: number
    status: number
    create_time: number
    update_time: number
}

/** SystemConfigListRes 配置列表分页结果 */
export type SystemConfigListRes = {
    list: SystemConfigRow[]
    total: number
    page: number
    limit: number
}

/** SystemConfigGroupsRes 配置分组结果 */
export type SystemConfigGroupsRes = string[]

function unwrap<T>(res: ApiEnvelope<T>): T {
    if (res.code !== 0) {
        throw new Error(res.msg || '请求失败')
    }
    return res.data
}

/**
 * fetchList 获取配置列表
 */
export const fetchList = async (params: {
    page?: number
    limit?: number
    group?: string
    keyword?: string
}) => {
    const res = await get<ApiEnvelope<SystemConfigListRes>>('system.Config/list', params)
    return unwrap(res)
}

/**
 * fetchRead 获取配置详情
 */
export const fetchRead = async (id: number) => {
    const res = await get<ApiEnvelope<SystemConfigRow>>('system.Config/read', { id })
    return unwrap(res)
}

/**
 * fetchGroups 获取所有配置分组
 */
export const fetchGroups = async () => {
    const res = await get<ApiEnvelope<SystemConfigGroupsRes>>('system.Config/groups')
    return unwrap(res)
}

/**
 * saveConfig 新建配置
 */
export const saveConfig = async (payload: Partial<SystemConfigRow>) => {
    const res = await post<ApiEnvelope<{ id: number }>>('system.Config/save', payload)
    return unwrap(res)
}

/**
 * updateConfig 更新配置
 */
export const updateConfig = async (id: number, payload: Partial<SystemConfigRow>) => {
    const res = await put<ApiEnvelope<null>>(`system.Config/update?id=${id}`, payload)
    return unwrap(res)
}

/**
 * deleteConfig 删除配置
 */
export const deleteConfig = async (id: number) => {
    const res = await del<ApiEnvelope<null>>('system.Config/delete', { id })
    return unwrap(res)
}

/**
 * batchSaveConfig 批量保存配置（按 group）
 */
export const batchSaveConfig = async (group: string, pairs: Record<string, string>) => {
    const res = await put<ApiEnvelope<null>>('system.Config/batchSave', { group, pairs })
    return unwrap(res)
}

