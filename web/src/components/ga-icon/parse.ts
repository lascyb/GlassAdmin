/** ParsedGaIcon 解析 GaIcon 存储串后的形态 */
export type ParsedGaIcon =
    | { source: 'el'; elName: string }
    | { source: 'url'; href: string }
    | { source: 'local'; stem: string }
    | { source: 'none' }

/**
 * parseGaIconString 解析存储的图标串：el:（Element Plus 图标组件名）、url:、local:（assets/icons 下 .svg，stem 无后缀）；无前缀时视为 el 组件名
 */
export function parseGaIconString(raw: string | null | undefined): ParsedGaIcon {
    const s = raw?.trim() ?? ''
    if (!s) {
        return { source: 'none' }
    }
    if (s.startsWith('el:')) {
        const name = s.slice(3).trim()
        return name ? { source: 'el', elName: name } : { source: 'none' }
    }
    if (s.startsWith('url:')) {
        const href = s.slice(4).trim()
        return href ? { source: 'url', href } : { source: 'none' }
    }
    if (s.startsWith('local:')) {
        const stem = s.slice(6).trim().replace(/\.svg$/i, '')
        return stem ? { source: 'local', stem } : { source: 'none' }
    }
    return { source: 'el', elName: s }
}
