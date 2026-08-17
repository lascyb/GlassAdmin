import { delData, getData, postData, putData } from '@/apis/http'

/** 与后端 castValueByType 一致的配置值 */
export type SystemConfigValue = string | number | boolean | string[]

/** SystemConfigRow 系统配置行 */
export type SystemConfigRow = {
    id: number
    key: string
    value: SystemConfigValue
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

/** SystemConfigMap key => typed value */
export type SystemConfigMap = Record<string, SystemConfigValue>

/** SystemConfigListRes 配置列表分页结果 */
export type SystemConfigListRes = {
    list: SystemConfigRow[]
    total: number
    page: number
    limit: number
}

/** SystemConfigGroupsRes 配置分组结果 */
export type SystemConfigGroupsRes = string[]

/**
 * fetchList 获取配置列表
 */
export const fetchList = async (params: {
    page?: number
    limit?: number
    group?: string
    keyword?: string
}) => {
    return getData<SystemConfigListRes>('system.Config/list', params)
}

/**
 * fetchRead 获取配置详情
 */
export const fetchRead = async (id: number) => {
    return getData<SystemConfigRow>('system.Config/read', { id })
}

/**
 * fetchGroups 获取所有配置分组
 */
export const fetchGroups = async () => {
    return getData<SystemConfigGroupsRes>('system.Config/groups')
}

/**
 * saveConfig 新建配置
 */
export const saveConfig = async (payload: Partial<SystemConfigRow>) => {
    return postData<{ id: number }>('system.Config/save', payload)
}

/**
 * updateConfig 更新配置
 */
export const updateConfig = async (id: number, payload: Partial<SystemConfigRow>) => {
    return putData<null>(`system.Config/update?id=${id}`, payload)
}

/**
 * deleteConfig 删除配置
 */
export const deleteConfig = async (id: number) => {
    return delData<null>('system.Config/delete', { id })
}

/**
 * batchSaveConfig 批量保存配置（按 group）
 */
export const batchSaveConfig = async (group: string, pairs: Record<string, SystemConfigValue>) => {
    return putData<null>('system.Config/batchSave', { group, pairs })
}

/**
 * fetchPublic 未登录可读公开配置（site_title、site_description）
 */
export const fetchPublic = async () => {
    return getData<SystemConfigMap>('system.Config/public')
}

/**
 * fetchRuntime 登录后 Layout 拉取的运行时配置
 */
export const fetchRuntime = async () => {
    return getData<SystemConfigMap>('system.Config/runtime')
}
