import { type RouteLocationNormalizedGeneric, type RouteRecordRaw } from 'vue-router'

import { useAdmin } from '@/pinia/admin'

//视图路由
export const routes: RouteRecordRaw[] = [
    {
        name: "root",
        path: '/',
        component: () => import("@/layouts/index.vue"), //全部走layout布局模板
        beforeEnter: async (to, _from: RouteLocationNormalizedGeneric, next) => {
            const admin = useAdmin()
            // access 过期或即将过期时先续期；刷新失败会 clear，避免假登录态停留
            const sessionOk = await admin.tryRefreshAccessTokenIfNeeded()
            if (sessionOk && admin.loggedIn) {
                next()
            } else {
                admin.clear()
                admin.setReferer(to.fullPath)
                next({ name: 'login', replace: true })
            }
        },
        children: [
            {name: "root-default", path: '', component: () => import("@/views/index.vue"),}, //默认首页快捷入口
            {
                name: 'account-profile',
                path: 'account/profile',
                component: () => import('@/views/account/profile.vue'),
                meta: { title: '个人信息' },
            },
            // 动态路由由 menus.init addRoute("root", ...) 插入；404 须排在其后（见 pinia/layout/menus）
            {
                name: 'root-not-found',
                path: ':404*',
                component: () => import('@/layouts/components/error/404.vue'),
            },
        ],
    },
    {name: "login", path: '/login', component: () => import("@/views/login.vue"),},
]
