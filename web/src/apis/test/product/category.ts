import { delData, getData, postData, putData } from '@/apis/http'

/** CategoryRow 列表/详情行 */
export type CategoryRow = {
    id: number
    name: string
}

/** CategoryListRes 分页列表 */
export type CategoryListRes = {
    list: CategoryRow[]
    total: number
    page: number
    limit: number
}

/** CategoryPayload 新建/更新载荷 */
export type CategoryPayload = {
    name: string
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.product.Category'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    keyword?: string
    name?: string
}) => getData<CategoryListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<CategoryRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: CategoryPayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: CategoryPayload) =>
    putData<null>(`${API_BASE}/update?id=${id}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

