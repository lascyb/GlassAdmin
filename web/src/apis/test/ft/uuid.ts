import { delData, getData, postData, putData } from '@/apis/http'

/** UuidRow 列表/详情行 */
export type UuidRow = {
    id: number
    uuid?: string | null
}

/** UuidListRes 分页列表 */
export type UuidListRes = {
    list: UuidRow[]
    total: number
    page: number
    limit: number
}

/** UuidPayload 新建/更新载荷 */
export type UuidPayload = {
    uuid?: string | null
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.Uuid'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    keyword?: string
}) => getData<UuidListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<UuidRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: UuidPayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: UuidPayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

