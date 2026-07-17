import { delData, getData, postData, putData } from '@/apis/http'

/** ColorRow 列表/详情行 */
export type ColorRow = {
    id: number
    color?: string | null
}

/** ColorListRes 分页列表 */
export type ColorListRes = {
    list: ColorRow[]
    total: number
    page: number
    limit: number
}

/** ColorPayload 新建/更新载荷 */
export type ColorPayload = {
    color?: string | null
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.Color'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
}) => getData<ColorListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<ColorRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: ColorPayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: ColorPayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

