import { delData, getData, postData, putData } from '@/apis/http'

/** NumberRow 列表/详情行 */
export type NumberRow = {
    id: number
    quantity?: number
}

/** NumberListRes 分页列表 */
export type NumberListRes = {
    list: NumberRow[]
    total: number
    page: number
    limit: number
}

/** NumberPayload 新建/更新载荷 */
export type NumberPayload = {
    quantity?: number
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.Number'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    quantity_min?: number
    quantity_max?: number
}) => getData<NumberListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<NumberRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: NumberPayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: NumberPayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

