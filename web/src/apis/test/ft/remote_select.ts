import { delData, getData, postData, putData } from '@/apis/http'

/** RemoteSelectRow 列表/详情行 */
export type RemoteSelectRow = {
    id: number
    category_id?: number | null
    category?: { id: number; name: string } | null
}

/** RemoteSelectListRes 分页列表 */
export type RemoteSelectListRes = {
    list: RemoteSelectRow[]
    total: number
    page: number
    limit: number
}

/** RemoteSelectPayload 新建/更新载荷 */
export type RemoteSelectPayload = {
    category_id?: number | null
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.RemoteSelect'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    category_id?: number
}) => getData<RemoteSelectListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<RemoteSelectRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: RemoteSelectPayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: RemoteSelectPayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

