/**
 * request 使用 VueUse 的 createFetch 封装的请求函数
 * 自动添加 token 等认证信息
 */

import { useAdmin } from '@/pinia/admin';
import { useToken } from '@/pinia/admin/token';
import { router } from "@/router";
import { createFetch } from '@vueuse/core';
import { ElMessage } from "element-plus";

/** getAdmin / getToken 延迟获取 store，避免 request ↔ pinia 模块循环依赖 */
const getAdmin = () => useAdmin()
const getToken = () => useToken()

/** retried401Execute 已对该 useFetch 实例执行过「刷新 + 重放」后，再次 401 则不再重试（按 execute 引用区分并发请求） */
const retried401Execute = new WeakSet<() => Promise<Response | null>>()

/** redirectingToLogin 避免并发 401 / 刷新失败重复跳转登录页 */
let redirectingToLogin = false

/** redirectToLogin 清登录态并跳转登录页 */
const redirectToLogin = async (message = '未授权，请重新登录') => {
    if (redirectingToLogin) return
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
/**
 * getApiBaseUrl 站点入口（VITE_API_BASE_URL，不含 /admin）
 * 未配置则为空，相对路径由调用串自带 admin/
 */
const getApiBaseUrl = (): string => {
    return import.meta.env.VITE_API_BASE_URL || ''
}

/**
 * isFullUrl 判断是否是完整 URL
 * @param url 待判断的 URL
 */
const isFullUrl = (url: string): boolean => {
    return url.startsWith('http://') || url.startsWith('https://')
}

/**
 * buildUrl 构建带查询参数的 URL（不拼接域名）
 * @param url 请求地址（相对路径或完整 URL）
 * @param params 查询参数（可选）
 */
const buildUrl = (url: string, params?: Record<string, any>): string => {
    if (!params) return url

    // 构建查询参数
    // 如果是完整 URL，直接使用 URL 对象
    if (isFullUrl(url)) {
        const urlObj = new URL(url)
        Object.keys(params).forEach(key => {
            const value = params[key]
            if (value !== undefined && value !== null && value !== '') {
                urlObj.searchParams.append(key, String(value))
            }
        })
        return urlObj.toString()
    }

    // 相对路径：使用当前域名作为 base 来构建 URL 对象
    const urlObj = new URL(url, window.location.origin)
    Object.keys(params).forEach(key => {
        const value = params[key]
        if (value !== undefined && value !== null && value !== '') {
            urlObj.searchParams.append(key, String(value))
        }
    })
    // 返回相对路径部分（去掉域名）
    return urlObj.pathname + urlObj.search
}


/**
 * useFetch 使用 createFetch 创建的请求实例
 * 自动添加 token 和处理错误
 */
export const useFetch = createFetch({
    options: {
        async beforeFetch({
                              url, options
                          }) {
            // 处理相对路径的 baseURL 拼接（get 函数已通过 buildUrl 处理，这里主要处理 post 等请求）
            let requestUrl = url
            if (!isFullUrl(url)) {
                const baseUrl = getApiBaseUrl()
                if (baseUrl) {
                    requestUrl = `${baseUrl}${url.startsWith('/') ? '' : '/'}${url}`
                }
            }

            // 设置默认 Content-Type
            if (!options.headers) {
                options.headers = {}
            }
            const headers = options.headers as Record<string, string>
            const body = options.body
            if (!(body instanceof FormData)) {
                if (!headers['Content-Type']) {
                    headers['Content-Type'] = 'application/json'
                }
            }

            // 添加 token
            const tokenStore = getToken()
            if (tokenStore.token) {
                options.headers = {
                    ...options.headers, Authorization: `Bearer ${tokenStore.token}`,
                } as HeadersInit
            }


            return {
                options, url: requestUrl
            }
        }, async afterFetch({data}) {
            // 请求成功后按需续期；刷新失败则清登录态并回登录页（不阻塞本次响应）
            void (async () => {
                const admin = getAdmin()
                if (!admin.data) return
                const ok = await getToken().tryRefreshAccessTokenIfNeeded()
                if (!ok) {
                    await redirectToLogin('登录已失效，请重新登录')
                }
            })()
            return {data}
        }, async onFetchError({response, error, execute}) {
            if (response?.status === 401) {
                if (retried401Execute.has(execute)) {
                    await redirectToLogin()
                    return {error}
                }
                const refreshed = await getToken().tryRefreshAccessToken()
                if (!refreshed) {
                    await redirectToLogin()
                    return {error}
                }
                retried401Execute.add(execute)
                try {
                    const res = await execute(false)
                    if (res === null) {
                        return {error}
                    }
                    return {error: null}
                } finally {
                    retried401Execute.delete(execute)
                }
            }
            return {error}
        },
    }, fetchOptions: {
        mode: 'cors', credentials: "omit", // cache: "default"
    },
})

/**
 * get 发起 GET 请求
 * @param url 请求地址（相对路径会自动拼接 baseURL，完整 URL 会直接使用）
 * @param params 查询参数
 */
export const get = async <T = any>(url: string, params?: Record<string, any>): Promise<T> => {
    // 构建带查询参数的 URL（不拼接域名，域名由 beforeFetch 处理）
    const requestUrl = buildUrl(url, params)

    const fetchResult = useFetch<T>(requestUrl, {
        method: 'GET',
    }, {
        immediate: false,
    }).json()

    // 手动触发请求并等待完成
    await fetchResult.execute()

    if (fetchResult.error.value) {
        throw fetchResult.error.value
    }

    return fetchResult.data.value as T
}

/**
 * post 发起 POST 请求
 * @param url 请求地址（相对路径会自动拼接 baseURL，完整 URL 会直接使用）
 * @param params 请求体参数
 * @param options
 */
export const post = async <T = any>(url: string, params?: Record<string, any>, options?: {
    headers?: Record<string, string>
}): Promise<T> => {
    // URL 处理由 beforeFetch 统一处理，这里直接传入原始 URL
    const fetchResult = useFetch<T>(url, {
        method: 'POST', body: params ? JSON.stringify(params) : undefined, headers: options?.headers,
    }, {
        immediate: false,
    }).json()

    // 手动触发请求并等待完成
    await fetchResult.execute()

    if (fetchResult.error.value) {
        throw fetchResult.error.value
    }

    return fetchResult.data.value as T
}

/**
 * postFormData 发起 multipart POST（如上传文件），不设置 JSON Content-Type
 */
export const postFormData = async <T = any>(
    url: string,
    formData: FormData,
    options?: { headers?: Record<string, string> },
): Promise<T> => {
    const fetchResult = useFetch<T>(
        url,
        {
            method: 'POST',
            body: formData,
            headers: options?.headers,
        },
        {
            immediate: false,
        },
    ).json()

    await fetchResult.execute()

    if (fetchResult.error.value) {
        throw fetchResult.error.value
    }

    return fetchResult.data.value as T
}

/**
 * put 发起 PUT 请求（JSON body）
 */
export const put = async <T = any>(url: string, params?: Record<string, any>, options?: {
    headers?: Record<string, string>
}): Promise<T> => {
    const headers: Record<string, string> = {
        'Content-Type': 'application/json', ...(options?.headers ?? {}),
    }
    const fetchResult = useFetch<T>(url, {
        method: 'PUT', body: params !== undefined ? JSON.stringify(params) : undefined, headers,
    }, {
        immediate: false,
    }).json()

    await fetchResult.execute()

    if (fetchResult.error.value) {
        throw fetchResult.error.value
    }

    return fetchResult.data.value as T
}

/**
 * patch 发起 PATCH 请求（JSON body）
 */
export const patch = async <T = any>(url: string, params?: Record<string, any>, options?: {
    headers?: Record<string, string>
}): Promise<T> => {
    const headers: Record<string, string> = {
        'Content-Type': 'application/json', ...(options?.headers ?? {}),
    }
    const fetchResult = useFetch<T>(url, {
        method: 'PATCH', body: params !== undefined ? JSON.stringify(params) : undefined, headers,
    }, {
        immediate: false,
    }).json()

    await fetchResult.execute()

    if (fetchResult.error.value) {
        throw fetchResult.error.value
    }

    return fetchResult.data.value as T
}

/**
 * del 发起 DELETE 请求
 */
export const del = async <T = any>(url: string, params?: Record<string, any>): Promise<T> => {
    const requestUrl = buildUrl(url, params)
    const fetchResult = useFetch<T>(requestUrl, {
        method: 'DELETE',
    }, {
        immediate: false,
    }).json()

    await fetchResult.execute()

    if (fetchResult.error.value) {
        throw fetchResult.error.value
    }

    return fetchResult.data.value as T
}
