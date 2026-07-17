import { delData, getData, postData, putData } from '@/apis/http'

/** RadioRow 列表/详情行 */
export type RadioRow = {
    id: number
    status?: number
}

/** RadioListRes 分页列表 */
export type RadioListRes = {
    list: RadioRow[]
    total: number
    page: number
    limit: number
}

/** RadioPayload 新建/更新载荷 */
export type RadioPayload = {
    status?: number
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.Radio'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    status?: number
}) => getData<RadioListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<RadioRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: RadioPayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: RadioPayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

