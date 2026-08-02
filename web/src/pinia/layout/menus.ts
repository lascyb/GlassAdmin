import {defineStore} from 'pinia'
import {ref, shallowRef} from "vue";
import type {Menu} from "@/layouts/components/menus/types.ts";
import { menusToRoutes } from '@/pinia/layout/menusToRoutes.ts'
import { findMenuByPath } from '@/pinia/layout/findMenuByPath.ts'
import {router} from "@/router";

type FindResult = {
    menus: Menu[]
    menu: Menu
}

export const useLayoutMenus = defineStore('layout_menus', () => {
    const menus = shallowRef<Menu[]>([])
    const module = ref<number>()
    /** subMenus 顶栏模块布局下当前一级菜单的子菜单 */
    const subMenus = shallowRef<Menu[]>([])
    const collapse = ref<boolean>(false)

    const init = (data: Menu[]) => {
        const routes = menusToRoutes(data)
        if (routes) {
            // 先移除旧 404，动态路由加完后再挂到最后，避免 catch-all 抢匹配
            if (router.hasRoute('root-not-found')) {
                router.removeRoute('root-not-found')
            }
            for (const route of routes) {
                router.addRoute('root', route)
            }
            router.addRoute('root', {
                name: 'root-not-found',
                path: ':404*',
                component: () => import('@/layouts/components/error/404.vue'),
            })
        }

        menus.value = data
    }

    const find = (path: string): FindResult | undefined => {
        let paths = findMenuByPath(menus.value, path)
        if (paths && paths.length > 0) {
            let c = paths[paths.length - 1]
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
        setActiveModule,
        syncModuleFromPath,
    }
})
