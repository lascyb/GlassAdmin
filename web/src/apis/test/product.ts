import { delData, getData, postData, putData } from '@/apis/http'

/** ProductRow 列表/详情行 */
export type ProductRow = {
    id: number
}

/** ProductListRes 分页列表 */
export type ProductListRes = {
    list: ProductRow[]
    total: number
    page: number
    limit: number
}

/** ProductPayload 新建/更新载荷 */
export type ProductPayload = {

}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.Product'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
}) => getData<ProductListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<ProductRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: ProductPayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: ProductPayload) =>
    putData<null>(`${API_BASE}/update?id=${id}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

