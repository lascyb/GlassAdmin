import { getData } from '@/apis/http'

/** CategoryRow 分类行（远程下拉） */
export type CategoryRow = {
    id: number
    name: string
}

/** CATEGORY_LIST_URL ga-select-remote 地址 */
export const CATEGORY_LIST_URL = 'example.Category/list'

/** list 分类分页（一般由 ga-select-remote 直接请求） */
export const list = (params?: Record<string, unknown>) =>
    getData<{ list: CategoryRow[]; total: number }>(CATEGORY_LIST_URL, params)
