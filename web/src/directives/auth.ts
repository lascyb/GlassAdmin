import type { Menu } from '@/layouts/components/menus/types'
import { useLayoutMenus } from '@/pinia/layout/menus'
import { router } from '@/router'
import { normalizeViewComponent } from '@/utils/viewComponent'
import type {
    ComponentInternalInstance,
    ComponentPublicInstance,
    Directive,
    DirectiveBinding,
} from 'vue'

/** AuthBindingValue 指令值：旧字符串 btn、scope 路径/component、或 { btn, scope }；未赋值时 Vue 传入 true */
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

type GaViewComponent = {
    __gaView?: string
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
 * toInternal 从指令 binding.instance（公开实例）取内部实例
 */
function toInternal(
    instance: ComponentPublicInstance | ComponentInternalInstance | null | undefined,
): ComponentInternalInstance | null {
    if (!instance) return null
    const dollar = (instance as ComponentPublicInstance & { $?: ComponentInternalInstance }).$
    if (dollar) return dollar
    return instance as ComponentInternalInstance
}

/**
 * resolveViewKey 从指令所在实例向上查找 __gaView；
 * 优先取「已出现在菜单 component 索引」的节点（子页如 rule/backend 对齐到菜单的 rule/index）
 */
function resolveViewKey(
    instance: ComponentPublicInstance | ComponentInternalInstance | null | undefined,
): string | undefined {
    let cur: ComponentInternalInstance | null = toInternal(instance)
    let fallback: string | undefined
    const store = useLayoutMenus()
    while (cur) {
        const type = cur.type as GaViewComponent | string | null | undefined
        if (type && typeof type === 'object' && typeof type.__gaView === 'string' && type.__gaView) {
            const key = normalizeViewComponent(type.__gaView) ?? type.__gaView
            if (store.listByComponent(key).length > 0) {
                return key
            }
            if (!fallback) {
                fallback = key
            }
        }
        cur = cur.parent
    }
    return fallback
}

/**
 * hasPermissionByPath 按菜单 path 查单节点 btn（显式 path scope / 路由回退）
 */
function hasPermissionByPath(btn: string, pagePath: string): boolean {
    const found = useLayoutMenus().find(stripPathExtras(pagePath))
    if (!found) return false
    return pageHasBtn(found.menu, btn)
}

/**
 * hasPermission 当前页文件（或 scope）下是否有指定按钮
 * - scope 以 / 开头：按 path
 * - 其它非空 scope：按 component（走 hasBtnForView）
 * - 无 scope：优先 instance.__gaView，否则当前路由 path
 */
export function hasPermission(
    btn?: string | null,
    scope?: string | null,
    instance?: ComponentPublicInstance | ComponentInternalInstance | null,
): boolean {
    if (!btn) return true

    const store = useLayoutMenus()

    if (scope) {
        if (scope.startsWith('/')) {
            return hasPermissionByPath(btn, scope)
        }
        const key = normalizeViewComponent(scope) ?? scope
        return store.hasBtnForView(key, btn)
    }

    const viewKey = resolveViewKey(instance)
    if (viewKey) {
        return store.hasBtnForView(viewKey, btn)
    }

    const pagePath = stripPathExtras(router.currentRoute.value.path)
    if (!pagePath) return false
    return hasPermissionByPath(btn, pagePath)
}

/**
 * applyAuth 无权限时隐藏元素（display:none，保留 DOM 便于调试）
 */
function applyAuth(el: HTMLElement, binding: DirectiveBinding<AuthBindingValue>) {
    const { btn, scope } = parseAuth(binding)
    const instance = binding.instance as ComponentPublicInstance | null
    el.style.display = hasPermission(btn, scope, instance) ? '' : 'none'
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
