import { delData, getData, postData, putData } from '@/apis/http'

/** DataCategoryBrief belongs_to 分类摘要 */
export type DataCategoryBrief = {
    id: number
    name: string
}

/** DataTagBrief belongs_to_many 标签摘要 */
export type DataTagBrief = {
    id: number
    name: string
}

/** DataRow 示例数据列表行 */
export type DataRow = {
    id: number
    title: string
    code: string
    category_id?: number | null
    category?: DataCategoryBrief | null
    tag_ids?: number[]
    tags?: DataTagBrief[]
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

/** DataSavePayload 新建/更新载荷 */
export type DataSavePayload = {
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

/** DataOptionsRes 表单 options */
export type DataOptionsRes = {
    categories: DataCategoryBrief[]
    tags: DataTagBrief[]
}

/**
 * fetchList 分页列表
 */
export const fetchList = async (params: {
    page?: number
    limit?: number
    keyword?: string
    title?: string
    code?: string
    category_id?: number
    status?: number
    level?: number
    is_top?: number
}) => {
    return getData<DataListRes>('example.Data/list', params)
}

/**
 * fetchRead 详情
 */
export const fetchRead = async (id: number) => {
    return getData<DataRow>('example.Data/read', { id })
}

/**
 * fetchOptions 表单下拉选项
 */
export const fetchOptions = async () => {
    return getData<DataOptionsRes>('example.Data/options')
}

/**
 * saveData 新建
 */
export const saveData = async (payload: DataSavePayload) => {
    return postData<{ id: number }>('example.Data/save', payload)
}

/**
 * updateData 更新
 */
export const updateData = async (id: number, payload: DataSavePayload) => {
    return putData<null>('example.Data/update?id=' + id, payload)
}

/**
 * deleteData 删除
 */
export const deleteData = async (id: number) => {
    return delData<null>('example.Data/delete', { id })
}

/** UPLOAD_COVER_ACTION 封面上传接口 path */
export const UPLOAD_COVER_ACTION = 'example.Data/uploadCover'
