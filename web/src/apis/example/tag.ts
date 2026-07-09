import { getData } from '@/apis/http'

/** TagRow 标签行（远程多选下拉） */
export type TagRow = {
    id: number
    name: string
}

/** TAG_LIST_URL ga-select-remote 地址 */
export const TAG_LIST_URL = 'example.Tag/list'

/** list 标签分页（一般由 ga-select-remote 直接请求） */
export const list = (params?: Record<string, unknown>) =>
    getData<{ list: TagRow[]; total: number }>(TAG_LIST_URL, params)
