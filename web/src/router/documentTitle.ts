import type { RouteLocationNormalized } from 'vue-router'

import { useSystemConfig } from '@/pinia/system/config'

/** resolveRouteTitle 从路由 meta.title（兼容旧 meta.name）取页面标题 */
export const resolveRouteTitle = (to: RouteLocationNormalized): string => {
    const raw = to.meta.title ?? to.meta.name
    return typeof raw === 'string' ? raw.trim() : ''
}

/**
 * applyDocumentTitle 按「页面标题 - 站点名」设置浏览器标题
 * @param siteTitleOverride 登录页等尚未拉取 runtime 配置时可传入 public 站点名
 */
export const applyDocumentTitle = (
    to: RouteLocationNormalized,
    siteTitleOverride?: string,
): void => {
    const page = resolveRouteTitle(to)
    const fromStore = useSystemConfig().getString('site_title', '')
    const site = String(siteTitleOverride ?? fromStore)
        .trim() || 'GlassAdmin'
    document.title = page ? `${page} - ${site}` : site
}
