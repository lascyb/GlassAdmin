import type { Menu } from '@/layouts/components/menus/types'
import { useAdmin } from '@/pinia/admin'
import { useLayoutMenus } from '@/pinia/layout/menus'
import type { Directive, DirectiveBinding } from 'vue'

/**
 * collectBtnPaths 从菜单树收集 type=btn 的 path 作为权限键
 */
function collectBtnPaths(menus: Menu[]): Set<string> {
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
function getStoredPermissions(): Set<string> {
    const menus = useLayoutMenus().menus
    return collectBtnPaths(menus)
}

/**
 * hasPermission 判断是否拥有指定权限键；超级管理员恒为 true
 */
export function hasPermission(key: string | string[] | undefined | null): boolean {
    if (!key) return true
    const admin = useAdmin()
    if (admin.data?.role_codes?.includes('SUPER')) return true // 超级管理员，拥有全部前端按钮权限

    const keys = Array.isArray(key) ? key : [key]
    const allowed = getStoredPermissions()
    return keys.some((k) => allowed.has(k))
}

/**
 * applyAuth 无权限时隐藏元素（display:none，保留 DOM 便于调试）
 */
function applyAuth(el: HTMLElement, binding: DirectiveBinding<string | string[]>) {
    const allowed = hasPermission(binding.value)
    el.style.display = allowed ? '' : 'none'
}

/** vAuth 前端按钮权限指令，值：单个 path 或 path 数组（任一命中即显示） */
export const vAuth: Directive<HTMLElement, string | string[]> = {
    mounted(el, binding) {
        applyAuth(el, binding)
    },
    updated(el, binding) {
        applyAuth(el, binding)
    },
}

export default vAuth
