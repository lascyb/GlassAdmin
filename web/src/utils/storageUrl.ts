/**
 * resolveStorageUrl 将 public/storage 下的相对路径拼成可访问 URL
 * 基于 VITE_API_BASE_URL 站点入口（不含 /admin）
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
    const base = (import.meta.env.VITE_API_BASE_URL || '').trim().replace(/\/$/, '')
    const prefix = base ? `${base}/storage/` : '/storage/'
    return `${prefix}${rel}`
}
