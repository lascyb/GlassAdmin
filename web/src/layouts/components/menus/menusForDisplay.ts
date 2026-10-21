import type { Menu } from '@/layouts/components/menus/types'

/** VISIBILITY_HIDDEN 目录不入侧栏 */
export const VISIBILITY_HIDDEN = 2

/** UNGROUPED_MODULE_ID 双栏布局虚拟模块「未分组」（不与真实 auth_rule id 冲突） */
export const UNGROUPED_MODULE_ID = -1

/** PAGE_TYPES 根级单独页面，归入「未分组」 */
const PAGE_TYPES = new Set(['view', 'iframe', 'link'])

/**
 * isModuleLeafPage 双栏 Module 列中的单独页面（非目录）
 */
export function isModuleLeafPage(menu: Menu): boolean {
    return PAGE_TYPES.has(menu.type)
}

/**
 * menusForDisplay 过滤不入侧栏的节点（type=route 与 visibility=hidden 目录，子节点提升到同级）
 */
export function menusForDisplay(menus: Menu[] | undefined | null): Menu[] {
    if (!menus?.length) {
        return []
    }
    const out: Menu[] = []
    for (const menu of menus) {
        const children = menu.children?.length ? menusForDisplay(menu.children) : undefined
        if (menu.type === 'route' || menu.visibility === VISIBILITY_HIDDEN) {
            if (children?.length) {
                out.push(...children)
            }
            continue
        }
        out.push(children?.length ? { ...menu, children } : { ...menu, children: undefined })
    }
    return out
}

/** NAVIGABLE_TYPES 侧栏可点进的叶子类型 */
const NAVIGABLE_TYPES = new Set(['view', 'route', 'link', 'iframe'])

export type FlatMenuHit = {
    menu: Menu
    /** 祖先目录名（不含自身） */
    trail: string[]
}

/**
 * flattenNavigableMenus 将展示树压平为可跳转叶子（与侧栏渲染同源：先 menusForDisplay）
 */
export function flattenNavigableMenus(menus: Menu[] | undefined | null): FlatMenuHit[] {
    const walk = (nodes: Menu[], trail: string[]): FlatMenuHit[] => {
        const out: FlatMenuHit[] = []
        for (const node of nodes) {
            if (node.type === 'menu' && node.children?.length) {
                out.push(...walk(node.children, [...trail, node.name]))
                continue
            }
            if (NAVIGABLE_TYPES.has(node.type) && node.path_full) {
                out.push({ menu: node, trail })
            }
        }
        return out
    }
    return walk(menusForDisplay(menus), [])
}

/**
 * menusForModules 双栏 Module 列：有子级的目录 + 末尾虚拟「未分组」；空目录丢弃
 */
export function menusForModules(menus: Menu[] | undefined | null): Menu[] {
    const displayed = menusForDisplay(menus)
    const modules: Menu[] = []
    const leaves: Menu[] = []
    for (const item of displayed) {
        if (item.type === 'menu') {
            if (item.children?.length) {
                modules.push(item)
            }
            continue
        }
        if (isModuleLeafPage(item)) {
            leaves.push(item)
        }
    }
    if (leaves.length > 0) {
        modules.push({
            id: UNGROUPED_MODULE_ID,
            name: '未分组',
            path: '',
            path_full: '',
            type: 'menu',
            icon: 'Files',
            children: leaves,
        })
    }
    return modules
}
