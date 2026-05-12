/** ParsedMenuIcon 解析菜单 icon 字段后的形态 */
export type ParsedMenuIcon =
    | { source: 'ep'; epName: string }
    | { source: 'url'; href: string }
    | { source: 'none' }

/**
 * parseMenuIconString 解析存储的图标串：ep:组件名、url:地址；无前缀时视为 ep 组件名（兼容）
 */
export function parseMenuIconString(raw: string | null | undefined): ParsedMenuIcon {
    const s = raw?.trim() ?? ''
    if (!s) {
        return { source: 'none' }
    }
    if (s.startsWith('ep:')) {
        const name = s.slice(3).trim()
        return name ? { source: 'ep', epName: name } : { source: 'none' }
    }
    if (s.startsWith('url:')) {
        const href = s.slice(4).trim()
        return href ? { source: 'url', href } : { source: 'none' }
    }
    return { source: 'ep', epName: s }
}
