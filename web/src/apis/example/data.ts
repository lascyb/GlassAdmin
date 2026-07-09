import { delData, getData, postData, putData } from '@/apis/http'
import type { CategoryRow } from '@/apis/example/category'
import type { TagRow } from '@/apis/example/tag'

/** DataRow 列表/详情行 */
export type DataRow = {
    id: number
    title: string
    code: string
    category_id?: number | null
    category?: CategoryRow | null
    tag_ids?: number[]
    tags?: TagRow[]
    content?: string | null
    amount: number
    quantity: number
    is_top: number
    status: number
    level: number
    color?: string | null
    cover?: string | null
    publish_time?: number | null
    expire_date?: string | null
    extra?: string[] | null
    remark?: string | null
    sort: number
    create_time?: number | null
    update_time?: number | null
}

/** DataListRes 分页列表 */
export type DataListRes = {
    list: DataRow[]
    total: number
    page: number
    limit: number
}

/** DataPayload 新建/更新载荷 */
export type DataPayload = {
    title: string
    code: string
    category_id?: number | null
    tag_ids?: number[]
    content?: string
    amount: number
    quantity: number
    is_top: number
    status: number
    level: number
    color?: string
    cover?: string
    publish_time?: number | null
    expire_date?: string | null
    extra?: string[]
    remark?: string
    sort: number
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'example.Data'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
    keyword?: string
    code?: string
    category_id?: number
    tag_ids?: number[]
    status?: number
    level?: number
    is_top?: number
}) => getData<DataListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<DataRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: DataPayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: DataPayload) =>
    putData<null>(`${API_BASE}/update?id=${id}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

/** UPLOAD_COVER_ACTION 封面上传接口 */
export const UPLOAD_COVER_ACTION = `${API_BASE}/uploadCover`
