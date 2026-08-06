import type { Menu } from '@/layouts/components/menus/types'

/** VISIBILITY_HIDDEN 目录不入侧栏 */
export const VISIBILITY_HIDDEN = 2

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
