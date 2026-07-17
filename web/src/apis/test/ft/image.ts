import { delData, getData, postData, putData } from '@/apis/http'

/** ImageRow 列表/详情行 */
export type ImageRow = {
    id: number
    cover?: string | null
}

/** ImageListRes 分页列表 */
export type ImageListRes = {
    list: ImageRow[]
    total: number
    page: number
    limit: number
}

/** ImagePayload 新建/更新载荷 */
export type ImagePayload = {
    cover?: string | null
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.Image'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
}) => getData<ImageListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<ImageRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: ImagePayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: ImagePayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

/** UPLOAD_COVER_ACTION 封面上传接口 */
export const UPLOAD_COVER_ACTION = `${API_BASE}/uploadCover`

