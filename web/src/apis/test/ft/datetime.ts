import { delData, getData, postData, putData } from '@/apis/http'

/** DatetimeRow 列表/详情行 */
export type DatetimeRow = {
    id: number
    publish_time?: number
}

/** DatetimeListRes 分页列表 */
export type DatetimeListRes = {
    list: DatetimeRow[]
    total: number
    page: number
    limit: number
}

/** DatetimePayload 新建/更新载荷 */
export type DatetimePayload = {
    publish_time?: number
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.Datetime'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    publish_time_start?: number
    publish_time_end?: number
}) => getData<DatetimeListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<DatetimeRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: DatetimePayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: DatetimePayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

