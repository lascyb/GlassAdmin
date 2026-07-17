import { delData, getData, postData, putData } from '@/apis/http'

/** ProbeRow 列表/详情行 */
export type ProbeRow = {
    id: number
    title?: string | null
    new_col?: string | null
}

/** ProbeListRes 分页列表 */
export type ProbeListRes = {
    list: ProbeRow[]
    total: number
    page: number
    limit: number
}

/** ProbePayload 新建/更新载荷 */
export type ProbePayload = {
    title?: string | null
    new_col?: string | null
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.z.Probe'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
}) => getData<ProbeListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<ProbeRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: ProbePayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: ProbePayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

