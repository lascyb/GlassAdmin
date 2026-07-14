import { getData, postData } from '@/apis/http'
import type { CrudGenerateConfig } from '@/crud/types'

/** GenerateOptions 生成开关 */
export type GenerateOptions = {
    dryRun?: boolean
    force?: boolean
    noAuth?: boolean
    saveHistory?: boolean
}

/** GenerateFilePlan 计划写入文件 */
export type GenerateFilePlan = {
    path: string
    bytes: number
}

/** GenerateSchemaPlan 建表计划项 */
export type GenerateSchemaPlan = {
    table: string
    exists: boolean
    sql: string
}

/** GenerateAuthResult 权限写入结果 */
export type GenerateAuthResult = {
    created: string[]
    updated: string[]
    reused: string[]
    bindings: number
    bindingsCleared: number
    viewOverwritten: boolean
}

/** GenerateResult 生成/预览结果 */
export type GenerateResult = {
    dryRun: boolean
    module: string
    entity: string
    table: string
    title: string
    writeMode: string
    generateAuth: boolean
    files: GenerateFilePlan[]
    schema: GenerateSchemaPlan[]
    auth: string[]
    written: string[]
    skipped: string[]
    tablesCreated: string[]
    tablesSkipped: string[]
    authResult: GenerateAuthResult | null
    historyId: number | null
    historyError?: string
}

/** HistoryRow 历史列表行 */
export type HistoryRow = {
    id: number
    name: string
    create_time: string
}

/** HistoryListRes 历史分页 */
export type HistoryListRes = {
    list: HistoryRow[]
    total: number
    page: number
    limit: number
}

/** HistoryDetail 历史详情 */
export type HistoryDetail = {
    id: number
    name: string
    config: string
    fields: string
    create_time: string
    configParsed: CrudGenerateConfig | null
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'dev.curd.Index'
const HISTORY_BASE = 'dev.curd.History'

/** listSamples 列出样例名 */
export const listSamples = () => getData<string[]>(`${API_BASE}/samples`)

/** loadSample 加载样例配置 */
export const loadSample = (name: string) =>
    getData<CrudGenerateConfig>(`${API_BASE}/sample`, { name })

/** listTables 列出当前库业务表名 */
export const listTables = () => getData<string[]>(`${API_BASE}/tables`)

/** loadTableSchema 反推表结构为配置草稿 */
export const loadTableSchema = (table: string) =>
    getData<CrudGenerateConfig>(`${API_BASE}/tableSchema`, { table })

/** generate 执行生成或 dry-run */
export const generate = (config: CrudGenerateConfig, options: GenerateOptions = {}) =>
    postData<GenerateResult>(`${API_BASE}/generate`, {
        config: config as unknown as Record<string, unknown>,
        dryRun: options.dryRun ?? false,
        force: options.force ?? false,
        noAuth: options.noAuth ?? false,
        saveHistory: options.saveHistory ?? true,
    })

/** listHistory 历史列表 */
export const listHistory = (params?: { page?: number; limit?: number }) =>
    getData<HistoryListRes>(`${HISTORY_BASE}/index`, params)

/** readHistory 历史详情 */
export const readHistory = (id: number) =>
    getData<HistoryDetail>(`${HISTORY_BASE}/read`, { id })
