/** ApiEnvelope 后端统一 JSON 外壳 */
export type ApiEnvelope<T> = {
    code: number
    msg: string
    data: T
}

/** ApiError 业务码非 0 时抛出的错误 */
export class ApiError extends Error {
    code: number
    data: unknown
    /** shown 请求层已 ElMessage 过，页面 catch 勿再弹 */
    shown: boolean

    constructor(code: number, msg: string, data: unknown = null) {
        super(msg)
        this.name = 'ApiError'
        this.code = code
        this.data = data
        this.shown = false
    }
}

/**
 * unwrap 解析 code===0 的 data，否则抛出 ApiError
 */
export function unwrap<T>(res: ApiEnvelope<T>): T {
    if (res.code !== 0) {
        throw new ApiError(res.code, res.msg || '请求失败', res.data)
    }
    return res.data
}
