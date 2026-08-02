import type { PaginatedParams, PaginatedResult } from '@/composables/types.ts'
import { ElMessage } from 'element-plus'
import { onMounted, ref, unref, type MaybeRefOrGetter, type Ref } from 'vue'

export type PaginatedFetcher<T, P extends Record<string, unknown> = Record<string, unknown>> = (
    params: P & PaginatedParams,
) => Promise<PaginatedResult<T>>

export type UsePaginatedListOptions<P extends Record<string, unknown>> = {
    /** immediate 挂载后自动 load */
    immediate?: boolean
    /** defaultLimit 默认每页条数 */
    defaultLimit?: number
    /** params 额外查询参数（ref / computed / 普通对象） */
    params?: MaybeRefOrGetter<P> | P
    /** showError 加载失败时 ElMessage */
    showError?: boolean
}

/**
 * usePaginatedList 通用分页列表：loading、list、total、page、limit、load、resetPage、search
 */
export function usePaginatedList<T, P extends Record<string, unknown> = Record<string, unknown>>(
    fetcher: PaginatedFetcher<T, P>,
    options: UsePaginatedListOptions<P> = {},
) {
    const { immediate = true, defaultLimit = 20, params, showError = true } = options

    const list = ref<T[]>([]) as Ref<T[]>
    const total = ref(0)
    const page = ref(1)
    const limit = ref(defaultLimit)
    const loading = ref(false)

    const resolveParams = (): P => {
        if (!params) return {} as P
        return unref(params) as P
    }

    /** load 拉取当前页 */
    const load = async () => {
        loading.value = true
        try {
            const res = await fetcher({
                ...resolveParams(),
                page: page.value,
                limit: limit.value,
            })
            list.value = res.list
            total.value = res.total
            if (res.page !== undefined) page.value = res.page
            if (res.limit !== undefined) limit.value = res.limit
        } catch (e: unknown) {
            if (showError) {
                ElMessage.error(e instanceof Error ? e.message : '加载失败')
            }
            throw e
        } finally {
            loading.value = false
        }
    }

    /** resetPage 回到第一页并加载 */
    const resetPage = () => {
        page.value = 1
        return load()
    }

    /** search 查询（重置页码后 load） */
    const search = () => resetPage()

    if (immediate) {
        onMounted(() => {
            void load()
        })
    }

    return { list, total, page, limit, loading, load, resetPage, search }
}
