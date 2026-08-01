import { delData, getData } from '@/apis/http'

/** AttachmentAdminBrief 上传人摘要 */
export type AttachmentAdminBrief = {
    id: number
    username: string
    nickname?: string | null
}

/** AttachmentRow 附件列表/详情行 */
export type AttachmentRow = {
    id: number
    name: string
    path: string
    disk: string
    mime?: string | null
    ext?: string | null
    size: number
    scene: string
    sha1?: string | null
    admin_id?: number | null
    /** {0:文件在盘,1:物理文件已删除} */
    file_deleted?: number
    url?: string | null
    admin?: AttachmentAdminBrief | null
    create_time?: number | null
    update_time?: number | null
}

/** AttachmentListRes 分页列表 */
export type AttachmentListRes = {
    list: AttachmentRow[]
    total: number
    page: number
    limit: number
}

/**
 * fetchList 分页列表
 */
export const fetchList = async (params: {
    page?: number
    limit?: number
    keyword?: string
    scene?: string
    mime?: string
    admin_id?: number
}) => {
    return getData<AttachmentListRes>('system.Attachment/list', params)
}

/**
 * fetchRead 详情
 */
export const fetchRead = async (id: number) => {
    return getData<AttachmentRow>('system.Attachment/read', { id })
}

/**
 * deleteAttachment 删除附件
 */
export const deleteAttachment = async (id: number) => {
    return delData<null>('system.Attachment/delete', { id })
}
