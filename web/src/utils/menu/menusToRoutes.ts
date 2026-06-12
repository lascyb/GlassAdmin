import type {RouteRecordRaw} from "vue-router";
import type {Menu} from "@/layouts/components/menus/types.ts";
import {views} from "@/router/views.ts"

const iframeView = () => import("./../../layouts/router-view/iframe.vue");
// const linkView = () => import("./../../layouts/router-view/link.vue");

const getComponent = (component: string | undefined): (() => Promise<unknown>) | undefined => {
    if (component) {
        if (component.startsWith('/')) {
            component = component.substring(1);
        }
        component = `/src/views/${component}${component.endsWith('.vue') ? '' : '.vue'}`

        return views[component]
    }
    return undefined
}
/**
 * menusToRoutes 将菜单数据转换为路由配置
 * @param menus 菜单数组
 * @param parentPath 父级路径，默认为 "/"
 * @returns 路由配置数组
 */
export const menusToRoutes = (menus: Menu[] | undefined, parentPath: string = "/"): RouteRecordRaw[] | undefined => {
    if (menus == undefined) {
        return undefined
    }
    let items: RouteRecordRaw[] = [];
    for (const menu of menus) {
        // 计算当前菜单的完整路径
        let fullPath: string;
        if (menu.path.startsWith("://") || menu.path.startsWith("http://") || menu.path.startsWith("https://")) {
            fullPath = menu.path;
        } else {
            if (menu.path.startsWith("/")) {
                // 绝对路径，直接使用
                fullPath = menu.path;
            } else {
                // 相对路径，拼装父级路径
                // 规范化父级路径：移除末尾的 "/"（如果有）
                const normalizedParent = parentPath === "/" ? "" : parentPath.replace(/\/$/, "");
                // 规范化当前路径：移除开头的 "/"（如果有）
                const normalizedCurrent = menu.path.replace(/^\//, "");
                // 拼装路径
                fullPath = normalizedParent + "/" + normalizedCurrent;
            }

            // 规范化路径：移除多余的斜杠，确保以 "/" 开头
            const pathSegments = fullPath.split("/").filter(segment => segment !== "");
            fullPath = "/" + pathSegments.join("/");
        }

        menu.path_full = fullPath;
        if (menu.type == "menu" || menu.type == "page" || menu.type == "view" || menu.type == "route") {
            let component = getComponent(menu.component)
            let children = menusToRoutes(menu.children, fullPath)
            if (children || component) {
                items.push({
                    name: menu.id + "",
                    path: fullPath,
                    children: children,
                    component: component,
                    meta: {
                        name: menu.name,
                    }
                } as RouteRecordRaw)
            } else {
                //todo
            }


        } else if (menu.type == "iframe") {
            items.push({
                name: menu.id + "",
                path: fullPath,
                component: iframeView(),
                meta: {
                    name: menu.name,
                }
            })
        }
        // else if (menu.type == "link") {
        //     items.push({
        //         path: fullPath,
        //         component: linkView,
        //         meta: {
        //             name: menu.name,
        //             href: fullPath,
        //         }
        //     })
        // }
    }
    return items;
}