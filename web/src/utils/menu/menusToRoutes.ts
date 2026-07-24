import type { Menu } from '@/layouts/components/menus/types.ts'
import { views } from '@/router/views.ts'
import type { RouteRecordRaw } from 'vue-router'

const iframeView = () => import('./../../layouts/router-view/iframe.vue')

/** getComponent 按菜单 component 字段解析懒加载视图 */
const getComponent = (component: string | undefined): (() => Promise<unknown>) | undefined => {
    if (!component) {
        return undefined
    }
    if (component.startsWith('/')) {
        component = component.substring(1)
    }
    component = `/src/views/${component}${component.endsWith('.vue') ? '' : '.vue'}`

    return views[component]
}

/** isExternalPath 是否为外链（不入 vue-router） */
const isExternalPath = (path: string): boolean =>
    /^(https?:)?\/\//.test(path) || path.startsWith('://')

/**
 * resolveFullPath 计算菜单完整路径
 * 绝对 path 原样规范化；相对 path 拼到 parentPath 下
 */
const resolveFullPath = (path: string, parentPath: string): string => {
    if (isExternalPath(path)) {
        return path
    }
    const raw = path.startsWith('/')
        ? path
        : `${parentPath === '/' ? '' : parentPath.replace(/\/$/, '')}/${path.replace(/^\//, '')}`

    return '/' + raw.split('/').filter((s) => s !== '').join('/')
}

/**
 * menusToRoutes 将菜单数据转换为路由配置
 * - 始终写回 menu.path_full
 * - 无 component 的节点不注册路由；其子路由提升到同级
 * - 外链不入路由表
 */
export const menusToRoutes = (
    menus: Menu[] | undefined,
    parentPath: string = '/',
): RouteRecordRaw[] | undefined => {
    if (!menus?.length) {
        return undefined
    }

    const items: RouteRecordRaw[] = []
    for (const menu of menus) {
        const fullPath = resolveFullPath(menu.path, parentPath)
        menu.path_full = fullPath

        if (isExternalPath(fullPath)) {
            continue
        }

        if (menu.type === 'iframe') {
            items.push({
                name: String(menu.id),
                path: fullPath,
                component: iframeView,
                meta: { name: menu.name },
            })
            continue
        }

        if (menu.type === 'menu' || menu.type === 'page' || menu.type === 'view' || menu.type === 'route') {
            const childRoutes = menusToRoutes(menu.children, fullPath)
            const component = getComponent(menu.component)
            if (component) {
                items.push({
                    name: String(menu.id),
                    path: fullPath,
                    children: childRoutes,
                    component,
                    meta: { name: menu.name },
                })
            } else if (childRoutes?.length) {
                // 纯目录：不注册自身，子路由提升到同级
                items.push(...childRoutes)
            }
        }
    }

    return items.length ? items : undefined
}
