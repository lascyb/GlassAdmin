import type { Menu } from '@/layouts/components/menus/types'
import { getData } from '@/apis/http'

export type { ApiEnvelope } from '@/apis/envelope'
export { ApiError, unwrap, getData, postData, putData, delData, postFormDataData } from '@/apis/http'

/**
 * index 拉取当前用户动态菜单（需登录 token）
 *
 * @param catalog 是否包含可发现未授权菜单
 * @returns menus 供 useLayoutMenus.init
 */
export const index = async (catalog = false): Promise<{ menus: Menu[] }> => {
    return getData<{ menus: Menu[] }>('admin/system.account.Menu/menus', {
        catalog: catalog ? 1 : 0,
    })
}

/** MenuDescribe 无权限页描述 */
export type MenuDescribe = {
    id: number
    name: string
    type: string
    path: string
    icon?: string | null
    description?: string | null
}

/**
 * fetchMenuDescribe 按规则 id 拉取描述（授权或可发现）
 */
export const fetchMenuDescribe = async (id: number) => {
    return getData<MenuDescribe>('admin/system.account.Menu/describe', { id })
}
