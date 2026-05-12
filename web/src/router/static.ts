import { type RouteRecordRaw } from 'vue-router'

import { useAdmin } from '@/pinia/admin'

//视图路由
export const routes: RouteRecordRaw[] = [
    {
        name: "root",
        path: '/',
        component: () => import("@/layouts/index.vue"), //全部走layout布局模板
        beforeEnter: (_to, _from, next) => {
            const admin = useAdmin()
            if (admin.loggedIn) {
                next()
            } else {
                admin.clear()
                next({ name: 'login', replace: true })
            }
        },
        children: [
            {name: "root-default", path: '', component: () => import("@/views/index.vue"),}, //默认首页快捷入口
            {
                path: ":404*", component: () => import("@/layouts/components/error/404.vue") // 默认 404 页面
            },
            //动态路由插入点
        ],
    },
    {name: "login", path: '/login', component: () => import("@/views/login.vue"),},
]
