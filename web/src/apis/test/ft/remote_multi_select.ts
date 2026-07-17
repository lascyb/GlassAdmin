import { delData, getData, postData, putData } from '@/apis/http'

/** RemoteMultiSelectRow 列表/详情行 */
export type RemoteMultiSelectRow = {
    id: number
    tag_ids?: number[]
    tags?: Array<{ id: number; name: string }>
}

/** RemoteMultiSelectListRes 分页列表 */
export type RemoteMultiSelectListRes = {
    list: RemoteMultiSelectRow[]
    total: number
    page: number
    limit: number
}

/** RemoteMultiSelectPayload 新建/更新载荷 */
export type RemoteMultiSelectPayload = {
    tag_ids?: number[]
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.RemoteMultiSelect'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    tag_ids?: number[]
}) => getData<RemoteMultiSelectListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<RemoteMultiSelectRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: RemoteMultiSelectPayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: RemoteMultiSelectPayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

