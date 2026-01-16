import type {RouteRecordRaw} from "vue-router";
import type {Menu} from "@/layouts/components/menus/types.ts";

// 预加载所有视图文件，使用相对路径
export const views = import.meta.glob("@/views/**/*.vue");

const getComponent = (component: string | undefined): (() => Promise<unknown>) => {
    if (component) {
        if (component.startsWith('/')) {
            component = component.substring(1);
        }
        return views[`/src/views/${component}${component.endsWith('.vue') ? '' : '.vue'}`] ?? (() => import("@/layouts/components/error/404.vue"))
    }
    return () => import("@/layouts/components/error/404.vue");
}
console.log(views);
/**
 * menusToRoutes 将菜单数据转换为路由配置
 * @param menus 菜单数组
 * @returns 路由配置数组
 */
export const menusToRoutes = (menus: Menu[] | undefined): RouteRecordRaw[] | undefined => {
    if (menus == undefined) {
        return undefined
    }
    let items: RouteRecordRaw[] = [];
    for (const menu of menus) {
        items.push({
            path: menu.path,
            children: menusToRoutes(menu.children),
            component: getComponent(menu.component),
            meta: {
                name: menu.name,
                error: "AAAA"
            }
        })
    }
    return items;
}
export const menuToRoute = (menu: Menu): RouteRecordRaw => {
    let meta = {
        name: menu.name,
        buttons: [] as Menu[],
    }
    if (menu.type === "btn") {

    }
    if (menu.children || menu.children) {

    }else {
        return {}
    }
    if (menu.children && menu.children.length > 0) {
        for (const child of menu.children) {
            if (child.type === "btn") {

            }
            let child_menu = menuToRoute(child);
            if (child_menu) {

            } else {
                meta.buttons.push(menu);
            }
        }
    }
    switch (menu.type) {
        case "btn":
            return undefined;
        case "menu":

        case "page":
            if (menu.component) {
                return views[menu.component];
            }


    }
    return {
        path: menu.path,
        children: menusToRoutes(menu.children),
        component: getComponent(menu.component),
        meta: meta
    };
};