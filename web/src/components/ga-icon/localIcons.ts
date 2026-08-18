/**
 * localIcons 本地 assets/icons 下图标，供 local: 前缀（stem 无后缀，可含子目录如 foo/bar）
 */
const pngModules = import.meta.glob<string>('@/assets/icons/**/*.png', {
    eager: true,
    query: '?url',
    import: 'default',
})

const webpModules = import.meta.glob<string>('@/assets/icons/**/*.webp', {
    eager: true,
    query: '?url',
    import: 'default',
})

const svgModules = import.meta.glob<string>('@/assets/icons/**/*.svg', {
    eager: true,
    query: '?url',
    import: 'default',
})

function collectStemToUrl(modules: Record<string, string>): Map<string, string> {
    const m = new Map<string, string>()
    for (const path of Object.keys(modules)) {
        const match = path.match(/\/icons\/(.+)\.(svg|png|webp)$/i)
        if (!match?.[1]) continue
        const stem = match[1].replace(/\\/g, '/')
        m.set(stem, modules[path] as string)
    }
    return m
}

/** stemToUrl stem（相对 icons）→ 构建期 url；同 stem 时 svg 优先于 png/webp */
const stemToUrl: Map<string, string> = (() => {
    const m = new Map<string, string>()
    for (const map of [
        collectStemToUrl(pngModules),
        collectStemToUrl(webpModules),
        collectStemToUrl(svgModules),
    ]) {
        for (const [stem, url] of map) {
            m.set(stem, url)
        }
    }
    return m
})()

/**
 * getLocalIconUrl 根据 stem 取本地图标 url，未打包到项目则 undefined
 */
export function getLocalIconUrl(stem: string): string | undefined {
    const k = stem.trim().replace(/^\/+|\/+$/g, '').replace(/\.(svg|png|webp)$/i, '')
    if (!k) return undefined
    return stemToUrl.get(k)
}

/**
 * listLocalIconStems 列出当前已纳入构建的本地图标 stem（排序）
 */
export function listLocalIconStems(): string[] {
    return [...stemToUrl.keys()].sort((a, b) => a.localeCompare(b))
}
