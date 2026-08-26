import type { Menu } from '@/layouts/components/menus/types.ts'
import { findMenuByPath } from '@/pinia/layout/findMenuByPath.ts'
import { menusToRoutes } from '@/pinia/layout/menusToRoutes.ts'
import { router } from '@/router'
import { normalizeViewComponent } from '@/utils/viewComponent'
import { defineStore } from 'pinia'
import { ref, shallowRef } from 'vue'

type FindResult = {
    menus: Menu[]
    menu: Menu
}

/** INDEXABLE_TYPES 可对应 views 下页面的菜单类型（排除 iframe/link，其 component 为 URL） */
const INDEXABLE_TYPES = new Set(['menu', 'view', 'route'])

/**
 * stripPathExtras 去掉 query / hash，与菜单 path_full 对齐
 */
function stripPathExtras(path: string): string {
    const withoutQuery = path.split('?')[0] ?? path
    return withoutQuery.split('#')[0] ?? withoutQuery
}

/**
 * pageHasBtn 指定页面节点的直接子级中是否有 type=btn 且 path 匹配
 */
function pageHasBtn(page: Menu, btn: string): boolean {
    return (page.children ?? []).some((n) => n.type === 'btn' && n.path === btn)
}

/**
 * buildComponentIndex DFS 收集 component → 挂载节点列表
 */
function buildComponentIndex(nodes: Menu[], into: Map<string, Menu[]>): void {
    for (const node of nodes) {
        if (INDEXABLE_TYPES.has(node.type) && node.component) {
            const key = normalizeViewComponent(node.component)
            if (key) {
                const list = into.get(key)
                if (list) {
                    list.push(node)
                } else {
                    into.set(key, [node])
                }
            }
        }
        if (node.children?.length) {
            buildComponentIndex(node.children, into)
        }
    }
}

export const useLayoutMenus = defineStore('layout_menus', () => {
    const menus = shallowRef<Menu[]>([])
    const module = ref<number>()
    /** subMenus 顶栏模块布局下当前一级菜单的子菜单 */
    const subMenus = shallowRef<Menu[]>([])
    const collapse = ref<boolean>(false)
    /** registeredRouteNames 上次 init 注册的动态路由名（便于切换 catalog 时卸掉） */
    const registeredRouteNames = ref<string[]>([])
    /** byComponent 同一 views 文件的全部菜单挂载 */
    const byComponent = shallowRef<Map<string, Menu[]>>(new Map())

    const init = (data: Menu[]) => {
        for (const name of registeredRouteNames.value) {
            if (router.hasRoute(name)) {
                router.removeRoute(name)
            }
        }
        registeredRouteNames.value = []

        const routes = menusToRoutes(data)
        if (routes) {
            if (router.hasRoute('root-not-found')) {
                router.removeRoute('root-not-found')
            }
            for (const route of routes) {
                router.addRoute('root', route)
                if (typeof route.name === 'string') {
                    registeredRouteNames.value.push(route.name)
                }
            }
            router.addRoute('root', {
                name: 'root-not-found',
                path: ':404*',
                component: () => import('@/layouts/components/error/404.vue'),
            })
        }

        menus.value = data
        const index = new Map<string, Menu[]>()
        buildComponentIndex(data, index)
        byComponent.value = index
    }

    const find = (path: string): FindResult | undefined => {
        const paths = findMenuByPath(menus.value, path)
        if (paths && paths.length > 0) {
            const c = paths[paths.length - 1]
            if (c) {
                return {
                    menus: paths,
                    menu: c,
                }
            }
        }
        return undefined
    }

    /**
     * listByComponent 返回挂载该 views 相对路径的全部菜单节点
     */
    const listByComponent = (component: string): Menu[] => {
        const key = normalizeViewComponent(component)
        if (!key) return []
        return byComponent.value.get(key) ?? []
    }

    /**
     * hasBtnForView 按页面文件鉴权
     * - 当前路由菜单的 component 与 viewKey 一致：只查该节点（整页）
     * - 否则（嵌入/跨页）：对该 component 的全部挂载做并集
     * - 索引未命中：false
     */
    const hasBtnForView = (viewKey: string, btn: string): boolean => {
        const key = normalizeViewComponent(viewKey) ?? viewKey
        const mounts = listByComponent(key)
        if (!mounts.length) {
            return false
        }

        const routePath = stripPathExtras(router.currentRoute.value.path)
        const byRoute = routePath ? find(routePath) : undefined
        if (byRoute?.menu) {
            const routeKey = normalizeViewComponent(byRoute.menu.component)
            if (routeKey && routeKey === key) {
                return pageHasBtn(byRoute.menu, btn)
            }
        }

        return mounts.some((m) => pageHasBtn(m, btn))
    }

    /**
     * setActiveModule 切换顶栏一级模块并更新 subMenus
     */
    const setActiveModule = (moduleId: number, options?: { navigate?: boolean }) => {
        module.value = moduleId
        const root = menus.value.find((item) => item.id === moduleId)
        if (!root) {
            subMenus.value = []
            return
        }
        if (root.children?.length) {
            subMenus.value = root.children
            return
        }
        subMenus.value = []
        if (options?.navigate) {
            void router.push(root.path_full)
        }
    }

    /**
     * syncModuleFromPath 按当前路由同步一级模块与子菜单
     */
    const syncModuleFromPath = (path: string) => {
        const result = find(path)
        if (result?.menus[0]) {
            setActiveModule(result.menus[0].id)
        }
    }

    return {
        menus,
        module,
        subMenus,
        collapse,
        init,
        find,
        listByComponent,
        hasBtnForView,
        setActiveModule,
        syncModuleFromPath,
    }
})
