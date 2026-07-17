import { delData, getData, postData, putData } from '@/apis/http'

/** TextareaRow 列表/详情行 */
export type TextareaRow = {
    id: number
    content?: string | null
}

/** TextareaListRes 分页列表 */
export type TextareaListRes = {
    list: TextareaRow[]
    total: number
    page: number
    limit: number
}

/** TextareaPayload 新建/更新载荷 */
export type TextareaPayload = {
    content?: string | null
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.Textarea'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
}) => getData<TextareaListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<TextareaRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: TextareaPayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: TextareaPayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

