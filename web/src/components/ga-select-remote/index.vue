<script setup lang="ts">
import { getData, postData } from '@/apis/http'
import type { PaginatedResult } from '@/composables/types'
import type { SelectOptionProps } from 'element-plus/es/components/select/src/select'
import { computed, onMounted, ref, shallowRef, useSlots } from 'vue'

defineOptions({ inheritAttrs: false })

const model = defineModel<unknown>()

const props = withDefaults(
    defineProps<{
        /** remoteUrl 远程请求地址 */
        remoteUrl: string
        /** props el-select 选项字段映射 */
        props?: SelectOptionProps
        /** method 请求方法 {GET:GET,POST:POST} */
        method?: 'GET' | 'POST'
        /** transform 响应数据转换，默认适配 PaginatedResult */
        transform?: (response: unknown) => { list: Record<string, unknown>[]; total: number }
        /** limit 每页数量 */
        limit?: number
        /** filters 额外筛选条件，展开合并到请求参数（GET 不可嵌套 object） */
        filters?: Record<string, unknown>
        /** requestFn 自定义请求函数，提供时替代默认 getData/postData */
        requestFn?: (url: string, params: Record<string, unknown>) => Promise<unknown>
        /** initOptions 编辑回显初始选项，提供时跳过首次请求 */
        initOptions?: Record<string, unknown> | Record<string, unknown>[]
    }>(),
    {
        method: 'GET',
        limit: 10,
        filters: () => ({}),
    },
)

/** options 搜索结果选项列表 */
const options = ref<Record<string, unknown>[]>([])
/** loading 加载状态 */
const loading = ref(false)
/** currentPage 当前页码 */
const currentPage = ref(1)
/** total 数据总数 */
const total = ref(0)
/** currentQuery 当前搜索关键词 */
const currentQuery = ref('')

const slots = useSlots()
const passthroughSlotNames = shallowRef(
    Object.keys(slots).filter((name) => !['default', 'header', 'footer'].includes(name)),
)

const valueKey = computed(() => props.props?.value || 'value')
const labelKey = computed(() => props.props?.label || 'label')
const shouldShowPagination = computed(() => total.value > props.limit)

/**
 * transformResponse 将接口响应统一为 {list, total}
 */
const transformResponse = (response: unknown): { list: Record<string, unknown>[]; total: number } => {
    if (props.transform) {
        return props.transform(response)
    }

    if (Array.isArray(response)) {
        return { list: response, total: response.length }
    }

    const data = response as PaginatedResult<Record<string, unknown>> & Record<string, unknown>
    if (Array.isArray(data?.list)) {
        return { list: data.list, total: data.total ?? data.list.length }
    }
    if (Array.isArray(data?.data)) {
        return { list: data.data, total: data.total ?? data.data.length }
    }
    if (response && typeof response === 'object') {
        return { list: [response as Record<string, unknown>], total: 1 }
    }
    return { list: [], total: 0 }
}

/**
 * executeRequest 执行远程请求
 */
const executeRequest = async (params: Record<string, unknown>): Promise<unknown> => {
    if (props.requestFn) {
        return props.requestFn(props.remoteUrl, params)
    }
    return props.method === 'GET'
        ? getData(props.remoteUrl, params)
        : postData(props.remoteUrl, params)
}

/**
 * refreshOptions 刷新选项列表
 */
const refreshOptions = async (query?: string, page = 1, useModelValue = false) => {
    if (currentQuery.value !== '' && (query == null || query === '')) {
        return
    }

    loading.value = true
    currentPage.value = page
    currentQuery.value = query ?? ''

    try {
        const params: Record<string, unknown> = {
            ...props.filters,
            page,
            limit: props.limit,
        }

        if (useModelValue && model.value != null) {
            params.init_values = model.value
        } else {
            const searchQuery = query !== undefined ? query : currentQuery.value
            if (searchQuery) {
                params.keyword = searchQuery
            }
        }

        const response = await executeRequest(params)
        const transformed = transformResponse(response)
        options.value = transformed.list
        total.value = transformed.total
    } catch (error) {
        console.error('远程搜索请求失败:', error)
        if (!useModelValue) {
            options.value = []
        }
        total.value = 0
    } finally {
        loading.value = false
    }
}

/** remoteMethod el-select 远程搜索回调 */
const remoteMethod = (query: string) => {
    if (query) {
        void refreshOptions(query, 1, false)
    } else {
        void refreshOptions(query, currentPage.value, false)
    }
}

/** handlePageChange 分页切换 */
const handlePageChange = (page: number) => {
    void refreshOptions(currentQuery.value, page, false)
}

/** cleanQuery 清除搜索关键词 */
const cleanQuery = () => {
    currentQuery.value = ''
    void refreshOptions(undefined, 1, false)
}

onMounted(() => {
    if (props.initOptions) {
        options.value = Array.isArray(props.initOptions) ? props.initOptions : [props.initOptions]
    } else if (model.value != null) {
        void refreshOptions(undefined, 1, true)
    } else {
        options.value = []
    }
})

defineExpose({ refreshOptions, cleanQuery })
</script>

<template>
    <el-select
        v-bind="$attrs"
        v-model="model"
        filterable
        remote
        clearable
        :remote-method="remoteMethod"
        :loading="loading"
        :props="props.props"
        :options="options"
        :value-on-clear="undefined"
        :debounce="300"
    >
        <template v-if="currentQuery" #header>
            当前搜索：
            <el-tag closable @close="cleanQuery">{{ currentQuery }}</el-tag>
        </template>

        <el-option
            v-for="option in options"
            :key="option[valueKey] as string | number"
            :label="option[labelKey] as string"
            :value="option[valueKey]"
        />

        <template v-if="shouldShowPagination" #footer>
            <el-pagination
                :current-page="currentPage"
                :total="total"
                :page-size="limit"
                :pager-count="5"
                layout="prev, pager, next, jumper, ->, total"
                @current-change="handlePageChange"
            />
        </template>

        <template v-for="name in passthroughSlotNames" :key="name" #[name]="slotProps">
            <slot :name="name" v-bind="slotProps" />
        </template>
    </el-select>
</template>
