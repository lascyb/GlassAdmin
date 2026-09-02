/** iframe 按菜单 method 发现业务组件：iframe/method/<id>/index.vue */

const METHOD_ID = /^[a-zA-Z0-9_-]+$/
const METHOD_VIEW = /^\.\/method\/([a-zA-Z0-9_-]+)\/index\.vue$/

const embedViews = import.meta.glob('./method/*/index.vue')

const methodIds = Object.keys(embedViews)
    .map((key) => key.match(METHOD_VIEW)?.[1])
    .filter((id): id is string => !!id)
    .sort((a, b) => a.localeCompare(b))

const directView = () => import('./direct.vue')

/** listIframeMethods 已落地的鉴权目录名（菜单下拉） */
export const listIframeMethods = (): string[] => [...methodIds]

/** hasIframeMethod 目录是否存在 */
export const hasIframeMethod = (method: string): boolean => {
    const id = method.trim()
    return METHOD_ID.test(id) && !!embedViews[`./method/${id}/index.vue`]
}

/** resolveIframeView 有目录用业务组件，否则直链 */
export const resolveIframeView = (method: string) => {
    const id = method.trim()
    if (hasIframeMethod(id)) {
        return embedViews[`./method/${id}/index.vue`]!
    }
    return directView
}

/** resolveIframeKeepAliveKey 已注册 method 共享实例；直链 / 未知不共享 */
export const resolveIframeKeepAliveKey = (method: string): string | undefined => {
    const id = method.trim()
    if (!hasIframeMethod(id)) {
        return undefined
    }
    return `iframe:${id}`
}
