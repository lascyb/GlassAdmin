import type { Menu } from '@/layouts/components/menus/types.ts'
import { views } from '@/router/views.ts'
import type { RouteRecordRaw } from 'vue-router'

const iframeView = () => import('@/layouts/router-view/iframe.vue')

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
 * resolveFullPath 计算菜单完整路径（供 path_full / el-menu 跳转，始终以 / 开头）
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
 * toRoutePath 挂到 root(`/`) 下的 route.path：去掉前导 /
 * 避免绝对 path 子路由在兄弟页之间跳转后 matched 异常导致白屏
 */
const toRoutePath = (fullPath: string): string => fullPath.replace(/^\//, '')

/**
 * menusToRoutes 将菜单数据转换为路由配置
 * - 始终写回 menu.path_full（绝对路径，供侧栏）
 * - route.path 用不带前导 / 的相对段（作为 root 子路由）
 * - 有 component 才注册；子路由一律提升到同级（不嵌套，页面组件无 router-view）
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
                path: toRoutePath(fullPath),
                component: iframeView,
                meta: { name: menu.name },
            })
            // iframe 下若仍有子路由，同样提升到同级
            const childRoutes = menusToRoutes(menu.children, fullPath)
            if (childRoutes?.length) {
                items.push(...childRoutes)
            }
            continue
        }

        if (menu.type === 'menu' || menu.type === 'page' || menu.type === 'view' || menu.type === 'route') {
            const childRoutes = menusToRoutes(menu.children, fullPath)
            const component = getComponent(menu.component)
            if (component) {
                items.push({
                    name: String(menu.id),
                    path: toRoutePath(fullPath),
                    component,
                    meta: { name: menu.name },
                })
            }
            // 无论自身是否注册，子路由都提到同级（避免嵌进无 router-view 的页面组件）
            if (childRoutes?.length) {
                items.push(...childRoutes)
            }
        }
    }

    return items.length ? items : undefined
}
