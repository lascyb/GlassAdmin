import type { Menu } from '@/layouts/components/menus/types'
import { getData } from '@/apis/http'

export type { ApiEnvelope } from '@/apis/envelope'
export { ApiError, unwrap, getData, postData, putData, delData, postFormDataData } from '@/apis/http'

/**
 * index 拉取当前用户动态菜单（需登录 token）
 *
 * @returns menus 供 useLayoutMenus.init
 */
export const index = async (): Promise<{ menus: Menu[] }> => {
    return getData<{ menus: Menu[] }>('account.Menu/menus')
}
