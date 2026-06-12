/**
 * localIcons 本地 assets/icons 下 svg，供 local: 前缀（stem 无 .svg 后缀，可含子目录如 foo/bar）
 */
const modules = import.meta.glob<string>('@/assets/icons/**/*.svg', {
    eager: true,
    query: '?url',
    import: 'default',
})

/** stemToUrl stem（相对 icons）→ 构建期 url */
const stemToUrl: Map<string, string> = (() => {
    const m = new Map<string, string>()
    for (const path of Object.keys(modules)) {
        const match = path.match(/\/icons\/(.+)\.svg$/i)
        if (!match?.[1]) continue
        const stem = match[1].replace(/\\/g, '/')
        m.set(stem, modules[path] as string)
    }
    return m
})()

/**
 * getLocalIconUrl 根据 stem 取 svg 的 url，未打包到项目则 undefined
 */
export function getLocalIconUrl(stem: string): string | undefined {
    const k = stem.trim().replace(/^\/+|\/+$/g, '')
    if (!k) return undefined
    return stemToUrl.get(k)
}

/**
 * listLocalIconStems 列出当前已纳入构建的本地图标 stem（排序）
 */
export function listLocalIconStems(): string[] {
    return [...stemToUrl.keys()].sort((a, b) => a.localeCompare(b))
}
