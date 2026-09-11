/**
 * request axios 实例：Token、401 刷新重放、RSA 字段加密、查询参数过滤
 * 方法签名与 axios 一致，config 原样透传。
 * 文档：docs/frontend/request.md
 */

import axios, {
    AxiosError,
    type AxiosRequestConfig,
    type InternalAxiosRequestConfig,
} from 'axios'
import { ElMessage } from 'element-plus'

import { ApiError, unwrap, type ApiEnvelope } from '@/apis/envelope'
import { useAdmin } from '@/pinia/admin'
import { useToken } from '@/pinia/admin/token'
import { router } from '@/router'

export { ApiError, unwrap } from '@/apis/envelope'
export type { AxiosProgressEvent, AxiosRequestConfig, AxiosResponse } from 'axios'

declare module 'axios' {
    interface AxiosRequestConfig {
        /** encryptFields 需 RSA 加密的 body 字段路径，多级用 "," */
        encryptFields?: string[]
        /** skipUnwrap 为 true 时返回完整 {code,msg,data}，不抛业务码错误 */
        skipUnwrap?: boolean
        /** silent 为 true 时不自动 ElMessage（轮询、后台预取） */
        silent?: boolean
        /** _retried HTTP 401 已重放 */
        _retried?: boolean
        /** _rsaRetried 业务码 40001 已重放 */
        _rsaRetried?: boolean
        /** _rsaPlainData 加密前的 JSON body，供 40001 重放 */
        _rsaPlainData?: Record<string, unknown>
    }
}

/** RSA_KEY_INVALID_CODE 密钥无效或已过期 */
const RSA_KEY_INVALID_CODE = 40001

/** ENCRYPT_FIELD_SEP 多级字段路径分隔符 */
const ENCRYPT_FIELD_SEP = ','

/** getAdmin / getToken 延迟取 store，避免 request ↔ pinia 循环依赖 */
const getAdmin = () => useAdmin()
const getToken = () => useToken()

/** redirectingToLogin 避免并发 401 / 刷新失败重复跳转登录页 */
let redirectingToLogin = false

/** redirectToLogin 清登录态并跳转登录页 */
const redirectToLogin = async (message = '未授权，请重新登录') => {
    if (redirectingToLogin) {
        return
    }
    const admin = getAdmin()
    const route = router.currentRoute.value
    if (route.name === 'login') {
        admin.clear()
        return
    }
    redirectingToLogin = true
    try {
        admin.clear()
        admin.setReferer(route.fullPath)
        ElMessage.warning({ message })
        await router.push({ name: 'login', replace: true })
    } finally {
        redirectingToLogin = false
    }
}

/** getApiBaseUrl 站点入口（VITE_API_BASE_URL，不含 /admin） */
const getApiBaseUrl = (): string => {
    return (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')
}

/** isEnvelope 是否为后端 JSON 外壳 */
const isEnvelope = (data: unknown): data is { code: number; msg: string; data: unknown } => {
    return (
        !!data &&
        typeof data === 'object' &&
        'code' in data &&
        'msg' in data &&
        typeof (data as { code: unknown }).code === 'number'
    )
}

/** toastIfNeeded 把错误提示到界面；silent 或已提示过则跳过 */
const toastIfNeeded = (error: Error, config?: AxiosRequestConfig) => {
    if (config?.silent) {
        return
    }
    if (error instanceof ApiError) {
        if (error.shown) {
            return
        }
        error.shown = true
    }
    ElMessage.error({ message: error.message || '请求失败', grouping: true })
}

/**
 * showError 页面 catch 用：请求层已 ElMessage 则跳过，其它错误用 fallback
 */
export const showError = (e: unknown, fallback = '请求失败') => {
    if (e instanceof ApiError && e.shown) {
        return
    }
    toastIfNeeded(e instanceof Error ? e : new Error(fallback))
}

/** httpStatusMessage HTTP 状态码给用户看的文案 */
const httpStatusMessage = (status: number): string => {
    if (status === 401) {
        return '未授权，请重新登录'
    }
    if (status === 403) {
        return '没有权限'
    }
    if (status === 404) {
        return '请求地址不存在'
    }
    if (status >= 500) {
        return `服务异常（${status}）`
    }
    return `请求失败（${status}）`
}

/** toRequestError 统一成 ApiError，shown 才能避免 catch 再弹一次 */
const toRequestError = (error: unknown): ApiError => {
    if (error instanceof ApiError) {
        return error
    }
    if (axios.isAxiosError(error)) {
        if (error.code === 'ECONNABORTED' || /timeout/i.test(error.message)) {
            return new ApiError(-1, '请求超时')
        }
        if (!error.response) {
            return new ApiError(-1, '网络异常')
        }
        if (isEnvelope(error.response.data)) {
            const env = error.response.data
            return new ApiError(env.code, env.msg || '请求失败', env.data)
        }
        return new ApiError(error.response.status, httpStatusMessage(error.response.status))
    }
    if (error instanceof Error) {
        return new ApiError(-1, error.message)
    }
    return new ApiError(-1, String(error))
}

/** serializeParams 跳过空值 */
const serializeParams = (params: Record<string, unknown>): string => {
    const search = new URLSearchParams()
    for (const [key, value] of Object.entries(params)) {
        if (value !== undefined && value !== null && value !== '') {
            search.append(key, String(value))
        }
    }
    return search.toString()
}

/** PROACTIVE_REFRESH_INTERVAL_MS 主动续期间隔，避免轮询每次都打 refresh */
const PROACTIVE_REFRESH_INTERVAL_MS = 30_000
let lastProactiveRefreshAt = 0

/** tryProactiveRefresh 成功响应后按需续期，不阻塞本次结果 */
const tryProactiveRefresh = (): void => {
    const now = Date.now()
    if (now - lastProactiveRefreshAt < PROACTIVE_REFRESH_INTERVAL_MS) {
        return
    }
    lastProactiveRefreshAt = now
    void (async () => {
        const admin = getAdmin()
        if (!admin.data) {
            return
        }
        const ok = await getToken().tryRefreshAccessTokenIfNeeded()
        if (!ok) {
            await redirectToLogin('登录已失效，请重新登录')
        }
    })()
}

/** isPlainBody 可 RSA 加密的 JSON 对象 body */
const isPlainBody = (data: unknown): data is Record<string, unknown> => {
    return !!data && typeof data === 'object' && !(data instanceof FormData) && !Array.isArray(data)
}

const parseFieldPath = (field: string): string[] => {
    return field
        .split(ENCRYPT_FIELD_SEP)
        .map((s) => s.trim())
        .filter((s) => s !== '')
}

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

const cloneBody = (params: Record<string, unknown>): Record<string, unknown> => {
    return JSON.parse(JSON.stringify(params)) as Record<string, unknown>
}

/** applyRsaEncrypt 按 encryptFields 加密 body，写入 X-RSA-Serial */
const applyRsaEncrypt = async (config: InternalAxiosRequestConfig): Promise<void> => {
    const fields = config.encryptFields
    if (!fields?.length) {
        return
    }
    const plain = config._rsaPlainData ?? (isPlainBody(config.data) ? cloneBody(config.data) : undefined)
    if (!plain) {
        return
    }
    config._rsaPlainData = plain

    const paths: string[][] = []
    const plains: string[] = []
    for (const field of fields) {
        const path = parseFieldPath(field)
        if (path.length === 0) {
            continue
        }
        const value = getAtPath(plain, path)
        if (typeof value === 'string' && value !== '') {
            paths.push(path)
            plains.push(value)
        }
    }
    if (plains.length === 0) {
        config.data = plain
        return
    }

    const { useSecurityRsa } = await import('@/pinia/security/rsa')
    const { serial, texts } = await useSecurityRsa().encrypt(...plains)
    if (!serial || texts.some((t) => !t)) {
        throw new ApiError(RSA_KEY_INVALID_CODE, '获取加密公钥失败')
    }

    const body = cloneBody(plain)
    paths.forEach((path, i) => {
        setAtPath(body, path, texts[i])
    })
    config.data = body
    config.headers['X-RSA-Serial'] = serial
}

/** retryWithNewRsa 公钥失效后刷新并重放（请求拦截器会重新加密） */
const retryWithNewRsa = async (config: InternalAxiosRequestConfig) => {
    config._rsaRetried = true
    config.data = config._rsaPlainData
    const { useSecurityRsa } = await import('@/pinia/security/rsa')
    await useSecurityRsa().forceRefreshPublicKey()
    return http.request(config)
}

export const http = axios.create({
    baseURL: getApiBaseUrl(),
    timeout: 30_000,
    withCredentials: false,
    paramsSerializer: serializeParams,
})

/** withFormTimeout FormData 上传不走默认超时 */
const withFormTimeout = (data: unknown, config?: AxiosRequestConfig): AxiosRequestConfig | undefined => {
    if (!(data instanceof FormData)) {
        return config
    }
    return { timeout: 0, ...config }
}

http.interceptors.request.use(async (config) => {
    const token = getToken().token
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    try {
        await applyRsaEncrypt(config)
    } catch (e) {
        if (
            e instanceof ApiError &&
            e.code === RSA_KEY_INVALID_CODE &&
            config.encryptFields?.length &&
            !config._rsaRetried
        ) {
            config._rsaRetried = true
            const { useSecurityRsa } = await import('@/pinia/security/rsa')
            await useSecurityRsa().forceRefreshPublicKey()
            await applyRsaEncrypt(config)
        } else {
            toastIfNeeded(e instanceof Error ? e : new Error(String(e)), config)
            throw e
        }
    }
    return config
})

http.interceptors.response.use(
    async (response) => {
        const config = response.config
        const payload = response.data
        if (
            isEnvelope(payload) &&
            payload.code === RSA_KEY_INVALID_CODE &&
            config.encryptFields?.length &&
            !config._rsaRetried
        ) {
            return retryWithNewRsa(config)
        }
        tryProactiveRefresh()
        return response
    },
    async (error: AxiosError) => {
        const config = error.config as InternalAxiosRequestConfig | undefined
        if (error.response?.status === 401 && config) {
            const rejectAfterLogin = async () => {
                await redirectToLogin()
                const err = toRequestError(error)
                err.shown = true
                return Promise.reject(err)
            }
            if (config._retried) {
                return rejectAfterLogin()
            }
            config._retried = true
            const refreshed = await getToken().tryRefreshAccessToken()
            if (!refreshed) {
                return rejectAfterLogin()
            }
            const token = getToken().token
            if (token) {
                config.headers.Authorization = `Bearer ${token}`
            }
            return http.request(config)
        }
        const err = toRequestError(error)
        toastIfNeeded(err, config)
        return Promise.reject(err)
    },
)

/** resolveBody 默认解包信封；skipUnwrap 或非信封（如 blob）原样返回 */
const resolveBody = <T>(data: unknown, config?: AxiosRequestConfig): T => {
    if (config?.skipUnwrap || !isEnvelope(data)) {
        return data as T
    }
    try {
        return unwrap(data as ApiEnvelope<T>)
    } catch (e) {
        if (e instanceof Error) {
            toastIfNeeded(e, config)
        }
        throw e
    }
}

/** get GET，config 与 axios.get 相同；默认解包 data */
export const get = async <T = unknown>(url: string, config?: AxiosRequestConfig): Promise<T> => {
    const { data } = await http.get<ApiEnvelope<T> | T>(url, config)
    return resolveBody<T>(data, config)
}

/** post POST，config 与 axios.post 相同；默认解包 data */
export const post = async <T = unknown>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig,
): Promise<T> => {
    const { data: body } = await http.post<ApiEnvelope<T> | T>(url, data, withFormTimeout(data, config))
    return resolveBody<T>(body, config)
}

/** put PUT，config 与 axios.put 相同；默认解包 data */
export const put = async <T = unknown>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig,
): Promise<T> => {
    const { data: body } = await http.put<ApiEnvelope<T> | T>(url, data, config)
    return resolveBody<T>(body, config)
}

/** patch PATCH，config 与 axios.patch 相同；默认解包 data */
export const patch = async <T = unknown>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig,
): Promise<T> => {
    const { data: body } = await http.patch<ApiEnvelope<T> | T>(url, data, config)
    return resolveBody<T>(body, config)
}

/** del DELETE，config 与 axios.delete 相同；默认解包 data */
export const del = async <T = unknown>(url: string, config?: AxiosRequestConfig): Promise<T> => {
    const { data } = await http.delete<ApiEnvelope<T> | T>(url, config)
    return resolveBody<T>(data, config)
}
