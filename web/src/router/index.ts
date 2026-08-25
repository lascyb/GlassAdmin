import { createWebHistory, createRouter } from 'vue-router'

import { useAdmin } from '@/pinia/admin'
import { useLayoutMenus } from '@/pinia/layout/menus'
import { useWorkbenchRecent } from '@/pinia/system/workbenchRecent'
import { applyDocumentTitle } from './documentTitle.ts'
import { routes } from './static.ts'

export const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to, _from, next) => {
    applyDocumentTitle(to)
    next()
})

/** 工作台首页与登录页不记最近访问 */
const SKIP_RECENT_PATHS = new Set(['/', '/login'])

router.afterEach((to) => {
    const path = to.path
    if (SKIP_RECENT_PATHS.has(path) || path.endsWith('/login')) {
        return
    }
    const admin = useAdmin()
    if (!admin.loggedIn || !admin.data?.id) {
        return
    }
    const found = useLayoutMenus().find(path)
    if (!found?.menu) {
        return
    }
    const menu = found.menu
    if (menu.type === 'menu' || menu.type === 'btn') {
        return
    }
    useWorkbenchRecent().touch(admin.data.id, menu)
})
