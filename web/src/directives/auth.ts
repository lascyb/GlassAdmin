import type { Menu } from '@/layouts/components/menus/types'
import { useLayoutMenus } from '@/pinia/layout/menus'
import { router } from '@/router'
import type { Directive, DirectiveBinding } from 'vue'

/** AuthBindingValue 指令值：旧字符串 btn、scope 路径、或 { btn, scope }；未赋值时 Vue 传入 true */
export type AuthBindingValue =
    | string
    | boolean
    | null
    | undefined
    | { btn?: string; scope?: string }

type AuthQuery = {
    btn?: string
    scope?: string
}

/**
 * stripPathExtras 去掉 query / hash，得到与菜单 path_full 对齐的路径
 */
function stripPathExtras(path: string): string {
    const withoutQuery = path.split('?')[0] ?? path
    return withoutQuery.split('#')[0] ?? withoutQuery
}

/**
 * parseAuth 解析四种写法：v-auth:add、v-auth:refresh="scope"、v-auth="'add'"、v-auth="{ btn, scope }"
 */
function parseAuth(binding: DirectiveBinding<AuthBindingValue>): AuthQuery {
    const arg = typeof binding.arg === 'string' && binding.arg ? binding.arg : undefined
    const val = binding.value

    let btn = arg
    let scope: string | undefined

    if (val && typeof val === 'object' && !Array.isArray(val)) {
        if (!btn && val.btn) {
            btn = val.btn
        }
        if (val.scope) {
            scope = val.scope
        }
    } else if (typeof val === 'string' && val) {
        if (btn) {
            scope = val
        } else {
            btn = val
        }
    }

    return { btn, scope }
}

/**
 * pageHasBtn 指定页面节点的直接子级中是否有已授权的 type=btn 且 path 匹配
 */
function pageHasBtn(page: Menu, btn: string): boolean {
    return (page.children ?? []).some((n) => n.type === 'btn' && n.path === btn)
}

/**
 * hasPermission 当前页（或 scope 页）下是否有指定按钮
 */
export function hasPermission(btn?: string | null, scope?: string | null): boolean {
    if (!btn) return true

    const pagePath = stripPathExtras(scope || router.currentRoute.value.path)
    if (!pagePath) return false

    const found = useLayoutMenus().find(pagePath)
    if (!found) return false

    return pageHasBtn(found.menu, btn)
}

/**
 * applyAuth 无权限时隐藏元素（display:none，保留 DOM 便于调试）
 */
function applyAuth(el: HTMLElement, binding: DirectiveBinding<AuthBindingValue>) {
    const { btn, scope } = parseAuth(binding)
    el.style.display = hasPermission(btn, scope) ? '' : 'none'
}

/** vAuth 前端按钮权限指令，按页面子按钮鉴权 */
export const vAuth: Directive<HTMLElement, AuthBindingValue> = {
    mounted(el, binding) {
        applyAuth(el, binding)
    },
    updated(el, binding) {
        applyAuth(el, binding)
    },
}

export default vAuth
