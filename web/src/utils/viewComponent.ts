/**
 * normalizeViewComponent 将菜单 component / glob key / 文件路径归一为相对 views/ 的稳定 key
 * 例：auth/admin/index（无前导 /、无 .vue）
 */
export function normalizeViewComponent(input: string | null | undefined): string | undefined {
    if (!input) return undefined
    let s = input.trim().replace(/\\/g, '/')
    if (!s) return undefined

    // file:// 或绝对磁盘路径 → 截取 views/ 之后
    const viewsIdx = s.toLowerCase().lastIndexOf('/views/')
    if (viewsIdx >= 0) {
        s = s.slice(viewsIdx + '/views/'.length)
    } else {
        s = s
            .replace(/^\/src\/views\//, '')
            .replace(/^@\/views\//, '')
            .replace(/^src\/views\//, '')
    }

    // 去掉 query/hash（import.meta.url）
    s = (s.split('?')[0] ?? s).split('#')[0] ?? s
    s = s.replace(/^\/+/, '')
    if (s.toLowerCase().endsWith('.vue')) {
        s = s.slice(0, -4)
    }
    if (!s || s.includes('://') || /^(https?:)/i.test(s)) {
        return undefined
    }
    return s
}

/**
 * toViewsGlobKey 归一化 key → import.meta.glob 使用的 `/src/views/....vue`
 */
export function toViewsGlobKey(component: string): string {
    const key = normalizeViewComponent(component) ?? component.replace(/^\/+/, '')
    const withExt = key.endsWith('.vue') ? key : `${key}.vue`
    return `/src/views/${withExt}`
}
