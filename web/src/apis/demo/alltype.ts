import { delData, getData, postData, putData } from '@/apis/http'

/** AllTypeRow 列表/详情行 */
export type AllTypeRow = {
    id: number
    title: string
    code: string
    category_id?: number | null
    category?: { id: number; name: string } | null
    tag_ids?: number[]
    tags?: Array<{ id: number; name: string }>
    content?: string | null
    amount?: number
    quantity?: number
    is_top?: number
    status?: number
    level?: number
    color?: string | null
    cover?: string | null
    publish_time?: number
    expire_date?: string | null
    extra?: string[] | null
    remark?: string | null
    sort?: number
    create_time?: number
    update_time?: number
}

/** AllTypeListRes 分页列表 */
export type AllTypeListRes = {
    list: AllTypeRow[]
    total: number
    page: number
    limit: number
}

/** AllTypePayload 新建/更新载荷 */
export type AllTypePayload = {
    title: string
    code: string
    category_id?: number | null
    tag_ids?: number[]
    content?: string | null
    amount?: number
    quantity?: number
    is_top?: number
    status?: number
    level?: number
    color?: string | null
    cover?: string | null
    publish_time?: number
    expire_date?: string | null
    extra?: string[] | null
    remark?: string | null
    sort?: number
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'demo.AllType'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    keyword?: string
    code?: string
    category_id?: number
    tag_ids?: number[]
    amount_min?: number
    amount_max?: number
    is_top?: number
    status?: number
    level?: number
    publish_time_start?: number
    publish_time_end?: number
    expire_date_start?: string
    expire_date_end?: string
}) => getData<AllTypeListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<AllTypeRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: AllTypePayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: AllTypePayload) =>
    putData<null>(`${API_BASE}/update?id=${id}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

/** UPLOAD_COVER_ACTION 封面上传接口 */
export const UPLOAD_COVER_ACTION = `${API_BASE}/uploadCover`

