import path from 'node:path'
import type { Plugin } from 'vite'

/**
 * 为 src/views 下 vue 页面注入 __gaView（相对 views/ 的稳定 key），供 v-auth 反查菜单。
 * 使用独立 script 块与 script setup 合并，避免改写 setup AST。
 */
export function gaViewPlugin(): Plugin {
    return {
        name: 'ga-view-key',
        enforce: 'pre',
        transform(code, id) {
            const normalized = id.replace(/\\/g, '/')
            const marker = '/src/views/'
            const idx = normalized.indexOf(marker)
            if (idx < 0 || !normalized.endsWith('.vue')) {
                return null
            }
            // 跳过 query（如 ?vue&type=style）
            if (normalized.includes('?')) {
                return null
            }

            const rel = normalized.slice(idx + marker.length).replace(/\.vue$/i, '')
            if (!rel || code.includes('__gaView')) {
                return null
            }

            const inject = `<script lang="ts">\nexport default { __gaView: ${JSON.stringify(rel)} }\n</script>\n`
            return {
                code: inject + code,
                map: null,
            }
        },
    }
}

/** viewKeyFromFilePath 供测试/工具从绝对路径推导 key（与插件一致） */
export function viewKeyFromFilePath(filePath: string, viewsRoot?: string): string | undefined {
    const normalized = filePath.replace(/\\/g, '/')
    if (viewsRoot) {
        const root = path.resolve(viewsRoot).replace(/\\/g, '/')
        if (normalized.startsWith(root + '/') || normalized.startsWith(root)) {
            const rel = normalized.slice(root.length).replace(/^\//, '').replace(/\.vue$/i, '')
            return rel || undefined
        }
    }
    const marker = '/src/views/'
    const i = normalized.toLowerCase().lastIndexOf(marker)
    if (i < 0) return undefined
    return normalized.slice(i + marker.length).replace(/\.vue$/i, '') || undefined
}
