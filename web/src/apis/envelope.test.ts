import { describe, expect, it } from 'vitest'
import { ApiError, unwrap, type ApiEnvelope } from './envelope'

describe('ApiEnvelope 解包', () => {
    it('code===0 返回 data', () => {
        const env: ApiEnvelope<{ id: number }> = { code: 0, msg: 'ok', data: { id: 7 } }
        expect(unwrap(env)).toEqual({ id: 7 })
    })

    it('业务码非 0 抛 ApiError，携带 code / data', () => {
        const env: ApiEnvelope<null> = { code: 40001, msg: '密钥失效', data: null }
        try {
            unwrap(env)
            expect.unreachable('应当抛错')
        } catch (e) {
            expect(e).toBeInstanceOf(ApiError)
            const err = e as ApiError
            expect(err.code).toBe(40001)
            expect(err.message).toBe('密钥失效')
            expect(err.shown).toBe(false)
        }
    })

    it('空 msg 回退为「请求失败」', () => {
        let caught: unknown
        try {
            unwrap({ code: 500, msg: '', data: null })
        } catch (e) {
            caught = e
        }
        // 未抛错时 caught 为 undefined，下面的断言会失败，避免「静默通过」
        expect(caught).toBeInstanceOf(ApiError)
        expect((caught as ApiError).message).toBe('请求失败')
        expect((caught as ApiError).code).toBe(500)
    })
})
