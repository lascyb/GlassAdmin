import {createWebHistory, createRouter} from 'vue-router'
import {routes} from "./static.ts";
import {useAdmin} from "@/pinia/admin";

export const router = createRouter({
    history: createWebHistory(),
    routes,
})
export const views = import.meta.glob('@/views/**/*.vue')

router.beforeEach((to, from, next) => {
    console.log(to, from)
    console.log(useAdmin())
    next()
})