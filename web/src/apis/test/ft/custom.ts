import { delData, getData, postData, putData } from '@/apis/http'

/** CustomRow 列表/详情行 */
export type CustomRow = {
    id: number
    custom_val?: string | null
}

/** CustomListRes 分页列表 */
export type CustomListRes = {
    list: CustomRow[]
    total: number
    page: number
    limit: number
}

/** CustomPayload 新建/更新载荷 */
export type CustomPayload = {
    custom_val?: string | null
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.Custom'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
}) => getData<CustomListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<CustomRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: CustomPayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: CustomPayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

