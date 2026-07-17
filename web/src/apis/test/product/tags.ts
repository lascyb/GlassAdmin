import { delData, getData, postData, putData } from '@/apis/http'

/** TagsRow 列表/详情行 */
export type TagsRow = {
    id: number
    name?: string | null
}

/** TagsListRes 分页列表 */
export type TagsListRes = {
    list: TagsRow[]
    total: number
    page: number
    limit: number
}

/** TagsPayload 新建/更新载荷 */
export type TagsPayload = {
    name?: string | null
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.product.Tags'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    keyword?: string
    name?: string
}) => getData<TagsListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<TagsRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: TagsPayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: TagsPayload) =>
    putData<null>(`${API_BASE}/update?id=${id}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

