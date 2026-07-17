import type { ApiEnvelope } from '@/apis/envelope'
import { ApiError, unwrap } from '@/apis/envelope'
import { del, get, post, postFormData, put } from '@/utils/request'

export { ApiError, unwrap } from '@/apis/envelope'

/** RsaRebuildResult rsaRebuild 重加密后返回的新请求体与头 */
export type RsaRebuildResult = {
    params?: Record<string, unknown>
    headers?: Record<string, string>
}

/** HttpRequestOptions 请求选项（含 RSA 无感重试） */
export type HttpRequestOptions = {
    headers?: Record<string, string>
    /** rsaRebuild 业务码 40001 时清 RSA 缓存、重新加密并重试一次 */
    rsaRebuild?: () => Promise<RsaRebuildResult>
    /** _rsaRetried 内部标记，禁止二次重试 */
    _rsaRetried?: boolean
}

/**
 * createRsaRebuild 构造 rsaRebuild 回调（先 forceRefresh 再执行 encryptFn）
 */
export const createRsaRebuild = (
    encryptFn: () => Promise<RsaRebuildResult | null>,
    forceRefresh: () => Promise<unknown>,
): (() => Promise<RsaRebuildResult>) => {
    return async () => {
        await forceRefresh()
        const result = await encryptFn()
        if (!result) {
            throw new ApiError(40001, '获取加密公钥失败')
        }
        return result
    }
}

/**
 * sendWithRsaRetry 解包响应；遇 40001 且提供 rsaRebuild 时无感重试一次
 */
const sendWithRsaRetry = async <T>(
    send: (params?: Record<string, unknown>, options?: HttpRequestOptions) => Promise<ApiEnvelope<T>>,
    params?: Record<string, unknown>,
    options?: HttpRequestOptions,
): Promise<T> => {
    try {
        return unwrap(await send(params, options))
    } catch (e) {
        if (
            e instanceof ApiError
            && e.code === 40001
            && options?.rsaRebuild
            && !options._rsaRetried
        ) {
            const rebuilt = await options.rsaRebuild()
            return sendWithRsaRetry(send, rebuilt.params ?? params, {
                ...options,
                headers: rebuilt.headers ?? options.headers,
                _rsaRetried: true,
            })
        }
        throw e
    }
}

/** getData GET 请求并解包 data */
export const getData = async <T>(url: string, params?: Record<string, unknown>): Promise<T> => {
    return unwrap(await get<ApiEnvelope<T>>(url, params))
}

/** postData POST JSON 请求并解包 data */
export const postData = async <T>(
    url: string,
    params?: Record<string, unknown>,
    options?: HttpRequestOptions,
): Promise<T> => {
    return sendWithRsaRetry(
        (p, o) => post<ApiEnvelope<T>>(url, p, o),
        params,
        options,
    )
}

/** putData PUT JSON 请求并解包 data */
export const putData = async <T>(
    url: string,
    params?: Record<string, unknown>,
    options?: HttpRequestOptions,
): Promise<T> => {
    return sendWithRsaRetry(
        (p, o) => put<ApiEnvelope<T>>(url, p, o),
        params,
        options,
    )
}

/** delData DELETE 请求并解包 data */
export const delData = async <T>(url: string, params?: Record<string, unknown>): Promise<T> => {
    return unwrap(await del<ApiEnvelope<T>>(url, params))
}

/** postFormDataData multipart POST 并解包 data */
export const postFormDataData = async <T>(
    url: string,
    formData: FormData,
    options?: { headers?: Record<string, string> },
): Promise<T> => {
    return unwrap(await postFormData<ApiEnvelope<T>>(url, formData, options))
}
