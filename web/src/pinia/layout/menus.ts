import {defineStore} from 'pinia'
import {ref, shallowRef} from "vue";
import type {Menu} from "@/layouts/components/menus/types.ts";
import {menusToRoutes} from "@/utils/menu/menusToRoutes.ts";
import {router} from "@/router";


export const useLayoutMenus = defineStore('layout_menus', () => {
    const menus = shallowRef<Menu[]>([])
    const module = ref<number>()

    const init = (data: Menu[]) => {

        let routes = menusToRoutes(data);
        if (routes) {
            for (const route of routes) {
                router.addRoute("root", route)
            }
        }
        menus.value = data;
    }

    return {
        menus,
        module,
        init
    }
})
