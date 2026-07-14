import { delData, getData, postData, putData } from '@/apis/http'

/** ItemRow 列表/详情行 */
export type ItemRow = {
    id: number
    title: string
    code: string
    category_id?: number | null
    category?: { id: number; name: string } | null
    quantity?: number
    is_top?: number
    status?: number
    cover?: string | null
    publish_time?: number
    remark?: string | null
    sort?: number
    create_time?: number
    update_time?: number
}

/** ItemListRes 分页列表 */
export type ItemListRes = {
    list: ItemRow[]
    total: number
    page: number
    limit: number
}

/** ItemPayload 新建/更新载荷 */
export type ItemPayload = {
    title: string
    code: string
    category_id?: number | null
    quantity?: number
    is_top?: number
    status?: number
    cover?: string | null
    publish_time?: number
    remark?: string | null
    sort?: number
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'demo.Item'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    keyword?: string
    code?: string
    category_id?: number
    is_top?: number
    status?: number
}) => getData<ItemListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<ItemRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: ItemPayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: ItemPayload) =>
    putData<null>(`${API_BASE}/update?id=${id}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

/** UPLOAD_COVER_ACTION 封面上传接口 */
export const UPLOAD_COVER_ACTION = `${API_BASE}/uploadCover`

