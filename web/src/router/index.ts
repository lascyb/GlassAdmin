import {createWebHistory, createRouter} from 'vue-router'
import {routes} from "./static.ts";

export const router = createRouter({
    history: createWebHistory(),
    routes,
})
