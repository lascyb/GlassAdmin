import { delData, getData, postData, putData } from '@/apis/http'

/** AmountRow 列表/详情行 */
export type AmountRow = {
    id: number
    amount?: number
}

/** AmountListRes 分页列表 */
export type AmountListRes = {
    list: AmountRow[]
    total: number
    page: number
    limit: number
}

/** AmountPayload 新建/更新载荷 */
export type AmountPayload = {
    amount?: number
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.Amount'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    amount_min?: number
    amount_max?: number
}) => getData<AmountListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<AmountRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: AmountPayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: AmountPayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

