import { describe, expect, it } from 'vitest'
import { normalizeViewComponent, toViewsGlobKey } from './viewComponent'
import { viewKeyFromFilePath } from '../../vite-plugin-ga-view'
import { resolveKeepAliveBaseKey, resolvePageKey } from './pageKey'

/**
 * 权限反查与动态路由映射的核心契约：
 * vite 插件注入的 __gaView、菜单 component、glob key 必须归一到同一个 key。
 */
describe('viewComponent 归一化', () => {
    it('把各种输入归一为相对 views/ 的无扩展名 key', () => {
        expect(normalizeViewComponent('system/auth/admin/index')).toBe('system/auth/admin/index')
        expect(normalizeViewComponent('system/auth/admin/index.vue')).toBe('system/auth/admin/index')
        expect(normalizeViewComponent('@/views/system/auth/admin/index.vue')).toBe(
            'system/auth/admin/index',
        )
        expect(normalizeViewComponent('/src/views/system/auth/admin/index.vue')).toBe(
            'system/auth/admin/index',
        )
        expect(normalizeViewComponent('E:\\app\\web\\src\\views\\system\\auth\\admin\\index.vue')).toBe(
            'system/auth/admin/index',
        )
    })

    it('忽略 query / hash 与前后斜杠', () => {
        expect(normalizeViewComponent('/src/views/about.vue?t=1#x')).toBe('about')
        expect(normalizeViewComponent('///about.vue')).toBe('about')
    })

    it('拒绝 URL 与空值', () => {
        expect(normalizeViewComponent('https://example.com/a.vue')).toBeUndefined()
        expect(normalizeViewComponent('://weird')).toBeUndefined()
        expect(normalizeViewComponent('')).toBeUndefined()
        expect(normalizeViewComponent(null)).toBeUndefined()
    })

    it('toViewsGlobKey 与 import.meta.glob 的 key 对齐', () => {
        expect(toViewsGlobKey('system/auth/admin/index')).toBe('/src/views/system/auth/admin/index.vue')
        expect(toViewsGlobKey('about.vue')).toBe('/src/views/about.vue')
    })
})

describe('vite 插件 key 与归一化一致', () => {
    it('插件从文件路径推导的 key 等于归一化结果', () => {
        const file = 'E:/app/web/src/views/system/auth/rule/backend.vue'
        const key = viewKeyFromFilePath(file)
        expect(key).toBe('system/auth/rule/backend')
        expect(normalizeViewComponent(key)).toBe(key)
        expect(toViewsGlobKey(key!)).toBe('/src/views/system/auth/rule/backend.vue')
    })

    it('viewsRoot 形式同样对齐', () => {
        const key = viewKeyFromFilePath('E:/app/web/src/views/dashboard/server.vue', 'E:/app/web/src/views')
        expect(key).toBe('dashboard/server')
    })

    it('非 views 路径返回 undefined', () => {
        expect(viewKeyFromFilePath('E:/app/web/src/components/ga-icon/index.vue')).toBeUndefined()
    })
})

describe('pageKey', () => {
    it('keepAliveKey 优先于 fullPath', () => {
        expect(
            resolveKeepAliveBaseKey({ fullPath: '/a?x=1', meta: { keepAliveKey: 'iframe:sso' } }),
        ).toBe('iframe:sso')
    })

    it('缺省用 fullPath，空白 keepAliveKey 回落', () => {
        expect(resolveKeepAliveBaseKey({ fullPath: '/a?x=1', meta: {} })).toBe('/a?x=1')
        expect(resolveKeepAliveBaseKey({ fullPath: '/a', meta: { keepAliveKey: '  ' } })).toBe('/a')
    })

    it('resolvePageKey 拼接刷新 nonce', () => {
        expect(resolvePageKey({ fullPath: '/a', meta: {} } as never, 3)).toBe('/a:3')
    })
})
