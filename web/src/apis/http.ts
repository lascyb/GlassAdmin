import type { ApiEnvelope } from '@/apis/envelope'
import { ApiError, unwrap } from '@/apis/envelope'
import { del, get, post, postFormData, put } from '@/utils/request'

export { ApiError, unwrap } from '@/apis/envelope'

/** RSA_KEY_INVALID_CODE 密钥无效或已过期 */
const RSA_KEY_INVALID_CODE = 40001

/** ENCRYPT_FIELD_SEP 多级字段路径分隔符 */
const ENCRYPT_FIELD_SEP = ','

/** HttpRequestOptions 请求选项（含 RSA 字段加密与 40001 无感重试） */
export type HttpRequestOptions = {
    headers?: Record<string, string>
    /**
     * encryptFields 需 RSA 加密的字段路径列表
     * 多级用 "," 分隔，如 'password'、'data,password'
     * 传入明文 params，发送前自动加密并带 X-RSA-Serial；40001 时清密钥重加密再试一次
     */
    encryptFields?: string[]
    /** _rsaRetried 内部标记，禁止二次重试 */
    _rsaRetried?: boolean
}

/**
 * isRsaKeyInvalidError 是否为密钥无效/过期（40001）
 */
const isRsaKeyInvalidError = (e: unknown): boolean => {
    if (!e || typeof e !== 'object') {
        return false
    }
    return Number((e as { code?: unknown }).code) === RSA_KEY_INVALID_CODE
}

/**
 * parseFieldPath 解析多级字段路径
 */
const parseFieldPath = (field: string): string[] => {
    return field
        .split(ENCRYPT_FIELD_SEP)
        .map((s) => s.trim())
        .filter((s) => s !== '')
}

/**
 * getAtPath 按路径取值
 */
const getAtPath = (obj: unknown, path: string[]): unknown => {
    let cur: unknown = obj
    for (const key of path) {
        if (cur === null || cur === undefined || typeof cur !== 'object') {
            return undefined
        }
        cur = (cur as Record<string, unknown>)[key]
    }
    return cur
}

/**
 * setAtPath 按路径赋值（中间层非对象时创建为普通对象）
 */
const setAtPath = (obj: Record<string, unknown>, path: string[], value: unknown): void => {
    let cur: Record<string, unknown> = obj
    for (let i = 0; i < path.length - 1; i++) {
        const key = path[i]!
        const next = cur[key]
        if (next === null || next === undefined || typeof next !== 'object' || Array.isArray(next)) {
            cur[key] = {}
        }
        cur = cur[key] as Record<string, unknown>
    }
    cur[path[path.length - 1]!] = value
}

/**
 * cloneParams 浅拷贝可序列化请求体
 */
const cloneParams = (params: Record<string, unknown>): Record<string, unknown> => {
    return JSON.parse(JSON.stringify(params)) as Record<string, unknown>
}

/**
 * encryptRequestParams 对指定字段做 RSA 加密，返回密文 params 与 X-RSA-Serial
 */
const encryptRequestParams = async (
    params: Record<string, unknown> | undefined,
    fields: string[],
): Promise<{ params?: Record<string, unknown>; headers?: Record<string, string> }> => {
    if (!params || fields.length === 0) {
        return { params }
    }

    const paths: string[][] = []
    const plains: string[] = []
    for (const field of fields) {
        const path = parseFieldPath(field)
        if (path.length === 0) {
            continue
        }
        const value = getAtPath(params, path)
        if (typeof value === 'string' && value !== '') {
            paths.push(path)
            plains.push(value)
        }
    }
    if (plains.length === 0) {
        return { params }
    }

    const { useSecurityRsa } = await import('@/pinia/security/rsa')
    const { serial, texts } = await useSecurityRsa().encrypt(...plains)
    if (!serial || texts.some((t) => !t)) {
        throw new ApiError(RSA_KEY_INVALID_CODE, '获取加密公钥失败')
    }

    const body = cloneParams(params)
    paths.forEach((path, i) => {
        setAtPath(body, path, texts[i])
    })
    return {
        params: body,
        headers: { 'X-RSA-Serial': serial },
    }
}

/**
 * clearAndRefreshRsaKey 清空 pinia 中的 RSA 缓存并重新拉取公钥
 */
const clearAndRefreshRsaKey = async (): Promise<void> => {
    const { useSecurityRsa } = await import('@/pinia/security/rsa')
    await useSecurityRsa().forceRefreshPublicKey()
}

/**
 * sendWithRsaRetry 发送请求；encryptFields 时先加密；遇 40001 清密钥后用明文重加密再试一次
 */
const sendWithRsaRetry = async <T>(
    send: (params?: Record<string, unknown>, options?: HttpRequestOptions) => Promise<ApiEnvelope<T>>,
    plainParams?: Record<string, unknown>,
    options?: HttpRequestOptions,
): Promise<T> => {
    const fields = options?.encryptFields
    const prepare = async () => {
        if (!fields?.length) {
            return {
                params: plainParams,
                headers: options?.headers,
            }
        }
        const encrypted = await encryptRequestParams(plainParams, fields)
        return {
            params: encrypted.params,
            headers: { ...options?.headers, ...encrypted.headers },
        }
    }

    try {
        const prepared = await prepare()
        return unwrap(
            await send(prepared.params, {
                ...options,
                headers: prepared.headers,
            }),
        )
    } catch (e) {
        if (isRsaKeyInvalidError(e) && fields?.length && !options?._rsaRetried) {
            await clearAndRefreshRsaKey()
            return sendWithRsaRetry(send, plainParams, {
                ...options,
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
