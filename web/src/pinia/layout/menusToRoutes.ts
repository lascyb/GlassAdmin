import type { Menu } from '@/layouts/components/menus/types.ts'
import { views } from '@/router/views.ts'
import { normalizeViewComponent, toViewsGlobKey } from '@/utils/viewComponent'
import type { RouteRecordRaw } from 'vue-router'

const iframeView = () => import('@/layouts/router-view/iframe.vue')
const linkView = () => import('@/layouts/router-view/link.vue')
const noPermissionView = () => import('@/layouts/components/error/no-permission.vue')

/** getComponent 按菜单 component 字段解析懒加载视图 */
const getComponent = (component: string | undefined): (() => Promise<unknown>) | undefined => {
    const key = normalizeViewComponent(component)
    if (!key) {
        return undefined
    }
    return views[toViewsGlobKey(key)]
}

/** isExternalPath 是否为外链 URL（不作为 vue-router path） */
const isExternalPath = (path: string): boolean =>
    /^(https?:)?\/\//.test(path) || path.startsWith('://')

/**
 * resolveFullPath 计算菜单完整路径（供 path_full / el-menu 跳转，始终以 / 开头）
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

const toRoutePath = (fullPath: string): string => fullPath.replace(/^\//, '')

/**
 * menusToRoutes 将菜单数据转换为路由配置
 * - authorized===false 的叶子注册无权限页
 * - link/iframe 经统一 router-view 组件渲染
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

        const authorized = menu.authorized !== false

        if (!authorized) {
            // 未授权可发现项：同一 path 进无权限说明页
            if (
                menu.type === 'view' ||
                menu.type === 'link' ||
                menu.type === 'iframe' ||
                menu.type === 'route'
            ) {
                items.push({
                    name: String(menu.id),
                    path: toRoutePath(fullPath),
                    component: noPermissionView,
                    meta: {
                        title: menu.name,
                        ruleId: menu.id,
                        unauthorized: true,
                        keepAlive: false,
                    },
                })
            }
            const childRoutes = menusToRoutes(menu.children, fullPath)
            if (childRoutes?.length) {
                items.push(...childRoutes)
            }
            continue
        }

        if (menu.type === 'iframe' || menu.type === 'link') {
            items.push({
                name: String(menu.id),
                path: toRoutePath(fullPath),
                component: menu.type === 'iframe' ? iframeView : linkView,
                meta: {
                    title: menu.name,
                    url: menu.component ?? '',
                    method: menu.method ?? '',
                    keepAlive: false,
                },
            })
            const childRoutes = menusToRoutes(menu.children, fullPath)
            if (childRoutes?.length) {
                items.push(...childRoutes)
            }
            continue
        }

        if (menu.type === 'menu' || menu.type === 'view' || menu.type === 'route') {
            const childRoutes = menusToRoutes(menu.children, fullPath)
            const component = getComponent(menu.component)
            if (component) {
                items.push({
                    name: String(menu.id),
                    path: toRoutePath(fullPath),
                    component,
                    meta: { title: menu.name, keepAlive: menu.keep_alive !== 0 },
                })
            }
            if (childRoutes?.length) {
                items.push(...childRoutes)
            }
        }
    }

    return items.length ? items : undefined
}
