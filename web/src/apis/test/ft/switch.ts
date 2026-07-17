import { delData, getData, postData, putData } from '@/apis/http'

/** SwitchFieldRow 列表/详情行 */
export type SwitchFieldRow = {
    id: number
    is_top?: number
}

/** SwitchFieldListRes 分页列表 */
export type SwitchFieldListRes = {
    list: SwitchFieldRow[]
    total: number
    page: number
    limit: number
}

/** SwitchFieldPayload 新建/更新载荷 */
export type SwitchFieldPayload = {
    is_top?: number
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.SwitchField'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    is_top?: number
}) => getData<SwitchFieldListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<SwitchFieldRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: SwitchFieldPayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: SwitchFieldPayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

