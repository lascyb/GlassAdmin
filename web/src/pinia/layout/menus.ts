import {defineStore} from 'pinia'
import {ref, shallowRef} from "vue";
import type {Menu} from "@/layouts/components/menus/types.ts";
import {menusToRoutes} from "@/utils/menu/menusToRoutes.ts";
import {findMenuByPath} from "@/utils/menu/findMenuByPath.ts";
import {router} from "@/router";

type FindResult = {
    menus: Menu[]
    menu: Menu
}

export const useLayoutMenus = defineStore('layout_menus', () => {
    const menus = shallowRef<Menu[]>([])
    const module = ref<number>()
    const collapse = ref<boolean>(false)

    const init = (data: Menu[]) => {

        let routes = menusToRoutes(data);
        if (routes) {
            for (const route of routes) {
                router.addRoute("root", route)
            }
        }
        menus.value = data;
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

    return {
        menus,
        module,
        collapse,
        init,
        find
    }
})
