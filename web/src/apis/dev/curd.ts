import { getData, postData } from '@/apis/http'
import type { CrudGenerateConfig } from '@/views/dev/curd/types'

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
    status: 'planned' | 'written' | 'skipped'
}

/** GenerateSchemaPlan 建表计划项 */
export type GenerateSchemaPlan = {
    table: string
    exists: boolean
    sql: string
    /** status 建表/同步状态 */
    status: 'pending' | 'will_alter' | 'unchanged' | 'created' | 'altered' | 'exists' | 'skipped'
}

/** GenerateAuthPlan 权限计划项 */
export type GenerateAuthPlan = {
    name: string
    status: 'planned' | 'created' | 'updated' | 'reused'
}

/** GenerateAuthMeta 权限写入汇总（不含分项列表） */
export type GenerateAuthMeta = {
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
    auth: GenerateAuthPlan[]
    authMeta: GenerateAuthMeta | null
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

/** RemoteControllerItem 远程数据源控制器项 */
export type RemoteControllerItem = {
    key: string
    controller: string
    listUrl: string
    model: string | null
    table: string | null
    pk: string | null
}

/** RemoteTableColumn 远程表字段项 */
export type RemoteTableColumn = {
    name: string
    type: string
    comment: string
}

/** RemoteTableColumnsRes 表字段响应 */
export type RemoteTableColumnsRes = {
    table: string
    pk: string | null
    fields: RemoteTableColumn[]
}

/** PivotTableItem 中间表候选项 */
export type PivotTableItem = {
    table: string
    foreignKey: string
    relatedKey: string
    comment: string
    suggested: boolean
}

/** PivotTablesRes 中间表匹配响应 */
export type PivotTablesRes = {
    mainTable: string
    relatedTable: string
    expectedName: string
    items: PivotTableItem[]
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'dev.curd.Index'
const HISTORY_BASE = 'dev.curd.History'
const REMOTE_BASE = 'dev.curd.Remote'

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

/** listRemoteControllers 远程下拉可选控制器列表 */
export const listRemoteControllers = () =>
    getData<RemoteControllerItem[]>(`${REMOTE_BASE}/controllers`)

/** getRemoteTableColumns 按表名取字段列表 */
export const getRemoteTableColumns = (table: string) =>
    getData<RemoteTableColumnsRes>(`${REMOTE_BASE}/tableColumns`, { table })

/** listPivotTables 按主表 + 关联表匹配中间表 */
export const listPivotTables = (mainTable: string, relatedTable: string) =>
    getData<PivotTablesRes>(`${REMOTE_BASE}/pivotTables`, { mainTable, relatedTable })
