import { delData, getData, postData, putData } from '@/apis/http'

/** DateRow 列表/详情行 */
export type DateRow = {
    id: number
    biz_date?: string | null
}

/** DateListRes 分页列表 */
export type DateListRes = {
    list: DateRow[]
    total: number
    page: number
    limit: number
}

/** DatePayload 新建/更新载荷 */
export type DatePayload = {
    biz_date?: string | null
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.Date'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    biz_date_start?: string
    biz_date_end?: string
}) => getData<DateListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<DateRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: DatePayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: DatePayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

