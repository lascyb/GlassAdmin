import type { ApiEnvelope } from '@/apis/envelope'
import { unwrap } from '@/apis/envelope'
import { del, get, post, postFormData, put } from '@/utils/request'

export { ApiError, unwrap } from '@/apis/envelope'

/** getData GET 请求并解包 data */
export const getData = async <T>(url: string, params?: Record<string, unknown>): Promise<T> => {
    return unwrap(await get<ApiEnvelope<T>>(url, params))
}

/** postData POST JSON 请求并解包 data */
export const postData = async <T>(
    url: string,
    params?: Record<string, unknown>,
    options?: { headers?: Record<string, string> },
): Promise<T> => {
    return unwrap(await post<ApiEnvelope<T>>(url, params, options))
}

/** putData PUT JSON 请求并解包 data */
export const putData = async <T>(
    url: string,
    params?: Record<string, unknown>,
    options?: { headers?: Record<string, string> },
): Promise<T> => {
    return unwrap(await put<ApiEnvelope<T>>(url, params, options))
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
