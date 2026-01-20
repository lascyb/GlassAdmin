import {type RouteRecordRaw} from "vue-router";

export const routes: RouteRecordRaw[] = [
    // {
    //     path: "/login", component: () => import("../views/login.vue")
    // },
    {
        name: "root", path: '/', component: () => import("@/layouts/index.vue"),
        children: [
            {name: "root-default", path: '', component: () => import("@/views/index.vue"),},
            {
                path: ":404*", component: () => import("@/layouts/components/error/404.vue")
            },
        ],
    },

    {name: "login", path: '/login', component: () => import("@/views/login.vue"),},
]
