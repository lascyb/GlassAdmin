import { delData, getData, postData, putData } from '@/apis/http'

/** CreateTimeRow 列表/详情行 */
export type CreateTimeRow = {
    id: number
    create_time?: number
}

/** CreateTimeListRes 分页列表 */
export type CreateTimeListRes = {
    list: CreateTimeRow[]
    total: number
    page: number
    limit: number
}

/** CreateTimePayload 新建/更新载荷 */
export type CreateTimePayload = {
    create_time?: number
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.CreateTime'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    create_time_start?: number
    create_time_end?: number
}) => getData<CreateTimeListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<CreateTimeRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: CreateTimePayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: CreateTimePayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

