import type { Menu } from '@/layouts/components/menus/types'
import { get } from '@/utils/request'

/** ApiEnvelope 后端统一 JSON 外壳 */
export type ApiEnvelope<T> = {
    code: number
    msg: string
    data: T
}

/**
 * index 拉取当前用户动态菜单（需登录 token）
 *
 * @returns menus 供 useLayoutMenus.init
 */
export const index = async (): Promise<{ menus: Menu[] }> => {
    const res = await get<ApiEnvelope<{ menus: Menu[] }>>('menu/menus')
    if (res.code !== 0) {
        throw new Error(res.msg || '菜单加载失败')
    }
    return res.data
}
