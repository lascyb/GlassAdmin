import {defineStore} from 'pinia'
import {ref, shallowRef} from "vue";
import type {Menu} from "@/layouts/components/menus/types.ts";
import {menusToRoutes} from "@/utils/menu/menusToRoutes.ts";
import {findMenuByPath} from "@/utils/menu/findMenuByPath.ts";
import {router} from "@/router";
import type {RouteRecordRaw} from "vue-router";

type FindResult = {
    menus: Menu[]
    menu: Menu
}


export const useLayoutMenus = defineStore('layout_menus', () => {
    const menus = shallowRef<Menu[]>([])
    const module = ref<number>()

    const init = (data: Menu[]) => {

        let routes = menusToRoutes(data);
        if (routes) {
            console.log(router.isReady())
            console.log(router.getRoutes())
            for (const route of routes) {
                router.addRoute("root", route)
            }
            console.log(router.getRoutes())
        }
        console.log("routes",routes)
        console.log("menus",data)
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
        init,
        find
    }
})
