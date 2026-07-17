import { delData, getData, postData, putData } from '@/apis/http'

/** TimeRow 列表/详情行 */
export type TimeRow = {
    id: number
    alarm_time?: number
}

/** TimeListRes 分页列表 */
export type TimeListRes = {
    list: TimeRow[]
    total: number
    page: number
    limit: number
}

/** TimePayload 新建/更新载荷 */
export type TimePayload = {
    alarm_time?: number
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.Time'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    alarm_time_start?: number
    alarm_time_end?: number
}) => getData<TimeListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<TimeRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: TimePayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: TimePayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

