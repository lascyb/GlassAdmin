export const index = async () => {
    return new Promise((resolve, reject) => {
        setTimeout(() => {
            resolve({
                menus: [
                    {
                        id: 9,
                        name: "仪表盘",
                        path: "dashboard",
                        type: "page",
                        component: "dashboard",
                    },
                    {
                        id: 1,
                        name: "权限管理",
                        path: "auth",
                        type: "menu",
                        children: [
                            {
                                id: 2,
                                name: "角色管理",
                                path: "role",
                                type: "page",
                                component: "auth/role/index.vue",
                                children: [
                                    {
                                        id: 9,
                                        name: "添加",
                                        path: "add",
                                        type: "btn",
                                    },
                                    {
                                        id: 10,
                                        name: "编辑",
                                        path: "edit",
                                        type: "btn",
                                    },
                                    {
                                        id: 11,
                                        name: "删除",
                                        path: "del",
                                        type: "btn",
                                    }
                                ]
                            },
                            {
                                id: 3,
                                name: "规则管理",
                                path: "rule",
                                type: "page",
                                component: "auth/rule/index.vue",
                                children: [
                                    {
                                        id: 7,
                                        name: "页面管理",
                                        type: "route",
                                        path: "page",
                                        component: "auth/rule/page",
                                    },
                                    {
                                        id: 8,
                                        name: "接口管理",
                                        type: "route",
                                        path: "api",
                                        component: "auth/rule/api",
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        id: 4,
                        name: "组织架构",
                        path: "/organization",
                        type: "menu",
                        children: [
                            {
                                id: 5,
                                name: "部门管理",
                                path: "/organization/department",
                                type: "page",
                                component: "organization/department",
                            },
                            {
                                id: 6,
                                name: "人员管理",
                                path: "/organization/personnel",
                                type: "page",
                                component: "organization/personnel",
                            }
                        ]
                    }
                ]
            })
        }, 1000);
    })
    // reject()
}