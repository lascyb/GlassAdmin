/**
 * resolveStorageUrl 将 public/storage 下的相对路径拼成可访问 URL（依赖与 API 同源或可推导的站点根）
 */
export function resolveStorageUrl(path: string | null | undefined): string {
    const p = (path ?? '').trim()
    if (!p) return ''
    if (/^https?:\/\//i.test(p)) return p
    const apiBase = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')
    const origin = apiBase.replace(/\/api\/?$/i, '')
    const rel = p.replace(/^\/+/, '')
    const prefix = origin ? `${origin}/storage/` : '/storage/'
    return `${prefix}${rel}`
}
