import type { Menu } from '@/layouts/components/menus/types'

/**
 * menusForDisplay 过滤 method=route 的节点（仍保留其子节点提升到同级），用于侧栏/顶栏展示
 */
export function menusForDisplay(menus: Menu[] | undefined | null): Menu[] {
    if (!menus?.length) {
        return []
    }
    const out: Menu[] = []
    for (const menu of menus) {
        const children = menu.children?.length ? menusForDisplay(menu.children) : undefined
        if (menu.method === 'route') {
            if (children?.length) {
                out.push(...children)
            }
            continue
        }
        out.push(children?.length ? { ...menu, children } : { ...menu, children: undefined })
    }
    return out
}
