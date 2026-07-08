/** formatUnixTime Unix 秒时间戳转本地时间字符串，无效或空值返回 — */
export function formatUnixTime(ts?: number | null): string {
    if (!ts) return '—'
    const d = new Date(ts * 1000)
    if (Number.isNaN(d.getTime())) return '—'
    const pad = (n: number) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}
