import { delData, getData, postData, putData } from '@/apis/http'

/** FileRow 列表/详情行 */
export type FileRow = {
    id: number
    attachment?: string | null
}

/** FileListRes 分页列表 */
export type FileListRes = {
    list: FileRow[]
    total: number
    page: number
    limit: number
}

/** FilePayload 新建/更新载荷 */
export type FilePayload = {
    attachment?: string | null
}

/** API_BASE 控制器路径前缀 */
const API_BASE = 'test.ft.File'

/** list 分页列表 */
export const list = (params: {
    page?: number
    limit?: number
}) => getData<FileListRes>(`${API_BASE}/list`, params)

/** read 详情 */
export const read = (id: number) => getData<FileRow>(`${API_BASE}/read`, { id })

/** create 新建 */
export const create = (payload: FilePayload) =>
    postData<{ id: number }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: number, payload: FilePayload) =>
    putData<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: number) => delData<null>(`${API_BASE}/delete`, { id })

export { remove as delete }

/** UPLOAD_ATTACHMENT_ACTION 附件上传接口 */
export const UPLOAD_ATTACHMENT_ACTION = `${API_BASE}/uploadAttachment`

