import type { Menu } from '@/layouts/components/menus/types'
import { useAdmin } from '@/pinia/admin'
import { useLayoutMenus } from '@/pinia/layout/menus'

/** SUPER_ROLE_ID 超级管理员，拥有全部前端按钮权限 */
const SUPER_ROLE_ID = 1

/**
 * collectBtnPaths 从菜单树收集 type=btn 的 path 作为权限键
 */
export function collectBtnPaths(menus: Menu[]): Set<string> {
    const keys = new Set<string>()
    const walk = (nodes: Menu[]) => {
        for (const n of nodes) {
            if (n.type === 'btn' && n.path) {
                keys.add(n.path)
            }
            if (n.children?.length) walk(n.children)
        }
    }
    walk(menus)
    return keys
}

/**
 * getStoredPermissions 当前用户已授权的前端按钮 path 集合
 */
export function getStoredPermissions(): Set<string> {
    const menus = useLayoutMenus().menus
    return collectBtnPaths(menus)
}

/**
 * hasPermission 判断是否拥有指定权限键；超级管理员恒为 true
 */
export function hasPermission(key: string | string[] | undefined | null): boolean {
    if (!key) return true
    const admin = useAdmin()
    if (admin.data?.role_ids?.includes(SUPER_ROLE_ID)) return true

    const keys = Array.isArray(key) ? key : [key]
    const allowed = getStoredPermissions()
    return keys.some((k) => allowed.has(k))
}
