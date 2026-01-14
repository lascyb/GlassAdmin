import type {RouteRecordRaw} from "vue-router";

export const routes: RouteRecordRaw[] = [
    // {
    //     path: "/login", component: () => import("../views/login.vue")
    // },
    {
        name: "root", path: '/', component: () => import("../layouts/index.vue"),
        children: [
            {
                path: "/:404*", component: () => import("../layouts/components/error/404.vue")
            },
            // {path: '', redirect: "dashboard"},
            {path: 'dashboard', component: () => import("../views/dashboard.vue"),},
            {
                path: 'auth', children: [
                    {path: "role", component: () => import("../views/auth/role/index.vue")}
                ]
            },
        ],
    },
]