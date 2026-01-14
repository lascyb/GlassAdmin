import {defineStore} from 'pinia'
import {shallowRef} from "vue";
import type {Menu} from "../../layouts/components/menus/types.ts";

export const useLayoutMenus = defineStore('layout_menus', () => {
    const menus = shallowRef<Menu[]>([
        {
            id: 9,
            name: "仪表盘",
            path: "/dashboard"
        },
        {
            id: 1,
            name: "权限管理",
            path: "/auth",
            children: [
                {
                    id: 2,
                    name: "角色管理",
                    path: "/auth/role",
                },
                {
                    id: 3,
                    name: "规则管理",
                    path: "/auth/rule",
                    children: [
                        {
                            id: 7,
                            name: "页面管理",
                            path: "/auth/rule/page",
                        },
                        {
                            id: 8,
                            name: "接口管理",
                            path: "/auth/rule/api",
                        }
                    ]
                }
            ]
        },
        {
            id: 4,
            name: "组织架构",
            path: "/organization",
            children: [
                {
                    id: 5,
                    name: "部门管理",
                    path: "/organization/department",
                },
                {
                    id: 6,
                    name: "人员管理",
                    path: "/organization/personnel"
                }
            ]
        }
    ])
    // const find = (path: string, paths: string[] = ["/"],) => {
    //     let full_path = paths.join("/")
    //     for (let i = 0; i < data.value.length; i++) {
    //         let item = data.value[i]
    //         if (item){
    //             paths.push(item.path)
    //             if (full_path + "/" + item.path == path){
    //                 return paths
    //             }else if (item?.children && item?.children.length>0){
    //                 find()
    //             }
    //         }
    //     }
    //     return paths
    // }
    return {
        menus: menus,
    }
})
