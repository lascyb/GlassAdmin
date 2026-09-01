/**
 * iframe 鉴权策略注册表
 * 业务方在 layouts/router-view/iframe/auth/*.ts 导出 IframeAuthProvider（default 或 provider）
 */

/** IframeNavigateCtx 壳内跳转上下文 */
export type IframeNavigateCtx = {
    iframe: HTMLIFrameElement
    target: string
}

/** IframeResolveCtx 壳层传给策略的解析上下文 */
export type IframeResolveCtx = {
    /** 强制重新解析（重试 / reauth / 顶栏刷新） */
    force?: boolean
    /** 当前路由 fullPath */
    routePath: string
    /** keep-alive / refresh 共享 scope（缺省同 routePath） */
    cacheKey: string
    /** 顶栏刷新 nonce */
    refreshNonce: number
    /** 当前已绑定的 iframe src */
    currentSrc: string
}

/** IframeMessageKind 策略对 postMessage 的判定 */
export type IframeMessageKind = 'ready' | 'reauth'

/** IframeAuthProvider iframe 鉴权提供方（业务逻辑放 auth/*.ts，勿写入壳组件） */
export type IframeAuthProvider = {
    /** 策略 ID，写入 system_auth_rule.method */
    id: string
    /** 菜单编辑展示名 */
    label: string
    /** 内嵌地址输入框占位 */
    targetPlaceholder: string
    /** 无 method 时是否认领该 target（如站内相对路径） */
    matchTarget?: (target: string) => boolean
    /** 将菜单 component 解析为 iframe src */
    resolveSrc: (target: string, ctx: IframeResolveCtx) => Promise<string>
    /** 解析壳页 postMessage；未识别则忽略 */
    interpretMessage?: (event: MessageEvent) => IframeMessageKind | undefined
    /** 壳页就绪（登录态已建立） */
    onReady?: (ctx: IframeResolveCtx) => void
    /** iframe load 且已进入可复用壳页（早于业务页 ready） */
    onShellLoad?: (ctx: IframeResolveCtx, iframe: HTMLIFrameElement) => void
    /** 壳页要求重授权 */
    onReauth?: (ctx: IframeResolveCtx) => void
    /** keep-alive 缓存前改写 src（如一次性 ticket → 可重入地址） */
    persistSrc?: (target: string, currentSrc: string) => string
    /** keep-alive 恢复时是否必须重新 resolveSrc */
    shouldReload?: (ctx: IframeResolveCtx) => boolean
    /** SSO/跳转进行中时暂缓 resolveSrc，避免同 Tab 切菜单重复鉴权 */
    shouldDeferResolve?: (ctx: IframeResolveCtx, target: string) => boolean
    /** 已在壳内展示目标页，无需 resolveSrc */
    shouldSkipResolve?: (ctx: IframeResolveCtx, target: string, iframe: HTMLIFrameElement) => boolean
    /** 支持 iframe 实例复用时，keep-alive 共享 key；未设则每菜单独立 fullPath 缓存 */
    keepAliveKey?: string
    /** 是否可在已有 iframe 壳内跳转（不改 src） */
    canNavigateInShell?: (ctx: IframeNavigateCtx) => boolean
    /** 壳内跳转；成功返回 true，失败则 fallback 到 resolveSrc */
    navigateInShell?: (ctx: IframeNavigateCtx) => boolean
}

type AuthModule = {
    default?: IframeAuthProvider
    provider?: IframeAuthProvider
}

const modules = import.meta.glob<AuthModule>('./auth/*.ts', { eager: true })

const providersById = new Map<string, IframeAuthProvider>()

for (const mod of Object.values(modules)) {
    const p = mod.default ?? mod.provider
    if (!p || typeof p.id !== 'string' || p.id.trim() === '') {
        continue
    }
    const id = p.id.trim()
    if (providersById.has(id)) {
        console.warn(`[iframeAuth] duplicate provider id: ${id}`)
        continue
    }
    if (p.keepAliveKey && !p.canNavigateInShell && !p.navigateInShell) {
        console.warn(
            `[iframeAuth] provider "${id}" has keepAliveKey but no shell navigation; iframe reuse disabled`,
        )
    }
    providersById.set(id, { ...p, id })
}

/**
 * listIframeAuthProviders 已注册策略（按 id 排序）
 */
export const listIframeAuthProviders = (): IframeAuthProvider[] =>
    [...providersById.values()].sort((a, b) => a.id.localeCompare(b.id))

/**
 * getIframeAuthProvider 按策略 ID 取提供方；空 ID 表示未指定
 */
export const getIframeAuthProvider = (method: string): IframeAuthProvider | undefined => {
    const id = method.trim()
    if (id === '') {
        return undefined
    }
    return providersById.get(id)
}

/** resolveKeepAliveKeyFromProvider 路由构建时写入 meta.keepAliveKey */
export const resolveKeepAliveKeyFromProvider = (method: string): string | undefined => {
    const key = getIframeAuthProvider(method)?.keepAliveKey
    if (typeof key !== 'string') {
        return undefined
    }
    const trimmed = key.trim()
    return trimmed === '' ? undefined : trimmed
}

/** resolveKeepAliveKeyFromMenu 路由构建：method 优先，否则 matchTarget 认领 */
export const resolveKeepAliveKeyFromMenu = (
    method: string,
    target: string,
): string | undefined => {
    const byMethod = resolveKeepAliveKeyFromProvider(method)
    if (byMethod) {
        return byMethod
    }
    const provider = lookupIframeAuthProvider(method, target)
    const key = provider?.keepAliveKey
    if (typeof key !== 'string') {
        return undefined
    }
    const trimmed = key.trim()
    return trimmed === '' ? undefined : trimmed
}

/**
 * lookupIframeAuthProvider 解析实际策略：有 method 按 ID；否则 matchTarget
 */
export const lookupIframeAuthProvider = (
    method: string,
    target: string,
): IframeAuthProvider | undefined => {
    const byId = getIframeAuthProvider(method)
    if (byId) {
        return byId
    }
    if (method.trim() !== '') {
        return undefined
    }
    for (const p of providersById.values()) {
        if (p.matchTarget?.(target)) {
            return p
        }
    }
    return undefined
}
