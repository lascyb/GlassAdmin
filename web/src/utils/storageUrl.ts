/**
 * siteOrigin 从 VITE_API_BASE_URL 取协议+主机（去掉 /admin 等应用路径）
 * 静态资源在站点根 /storage，不在 admin 子路径下
 */
function siteOrigin(): string {
    const base = (import.meta.env.VITE_API_BASE_URL || '').trim().replace(/\/$/, '')
    if (!base) return ''
    try {
        return new URL(base).origin
    } catch {
        return base
    }
}

/**
 * resolveStorageUrl 将 public/storage 下的相对路径拼成可访问 URL
 */
export function resolveStorageUrl(path: string | number | null | undefined): string {
    if (path == null || path === '') return ''
    const p = String(path).trim()
    if (!p) return ''
    if (/^https?:\/\//i.test(p)) return p
    let rel = p.replace(/^\/+/, '')
    if (rel.startsWith('storage/')) {
        rel = rel.slice('storage/'.length)
    }
    const origin = siteOrigin()
    const prefix = origin ? `${origin}/storage/` : '/storage/'
    return `${prefix}${rel}`
}
