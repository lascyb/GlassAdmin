/** PaginatedResult 分页接口 data 结构 */
export type PaginatedResult<T> = {
    list: T[]
    total: number
    page?: number
    limit?: number
}

/** PaginatedParams 分页请求公共参数 */
export type PaginatedParams = {
    page: number
    limit: number
}

/** StatusMapItem 状态映射项（供 ga-status-tag） */
export type StatusMapItem = {
    label: string
    type?: 'primary' | 'success' | 'warning' | 'info' | 'danger'
}

/** StatusMap 状态值 → 展示配置 */
export type StatusMap = Record<string | number, StatusMapItem>

/** EnumOption 静态枚举选项（供 ga-status-tag :options 与生成页 constants） */
export type EnumOption = {
    value: string | number
    label: string
    type?: 'primary' | 'success' | 'warning' | 'info' | 'danger'
}

/** TreeEditController 树形 CRUD 编辑弹窗控制器 */
export type TreeEditController = {
    show?: boolean
    id?: number
    pid?: number
}

/** RsaEncryptedPayload RSA 加密结果（password 字段 + 请求头） */
export type RsaEncryptedPayload = {
    password?: string
    headers?: { 'X-RSA-Serial': string }
}
