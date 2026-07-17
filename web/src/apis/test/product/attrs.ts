import { delData, getData, postData, putData } from '@/apis/http'

/** AttrsRow 列表/详情行 */
export type AttrsRow = {
    key: string
    value: string
}

/** AttrsListRes 分页列表 */
export type AttrsListRes = {
    list: AttrsRow[]
    total: number
    page: number
    limit: number
}

/** AttrsPayload 新建/更新载荷 */
export type AttrsPayload = {
    key: string
    value: string
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.product.Attrs'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    keyword?: string
    key?: string
    value?: string
}) => getData<AttrsListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: string) => getData<AttrsRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: AttrsPayload) =>
    postData<{ id: string }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: string, payload: AttrsPayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: string) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }
