<script setup lang="ts">
import { get, post } from "@/utils/request";
import { useDebounceFn } from "@vueuse/core";
import type { SelectOptionProps } from "element-plus/es/components/select/src/select";
import { onMounted, ref, watch } from "vue";

const model = defineModel<any>()

interface Props {
  /** 远程请求地址 */
  remoteUrl: string
  /** 初始选项，用于回显 */
  initOption?: Record<string, any>
  /** el-select 的 props 配置 */
  props?: SelectOptionProps
  /** 请求方法，默认 GET */
  method?: 'GET' | 'POST'
  /** 响应数据转换函数，将接口返回的数据转换为选项格式 */
  transform?: (response: any) => { list: any[], total: number }
  /** 是否启用分页，默认 false */
  pagination?: boolean
  /** 每页数量，默认 10 */
  pageSize?: number
  /** 额外的请求参数，会合并到请求参数中 */
  filter?: Record<string, any>
  /** 自定义请求函数，如果提供则使用此函数替代默认的 fetch */
  requestFn?: (url: string, params: Record<string, any>) => Promise<any>
}

const props = withDefaults(defineProps<Props>(), {
  method: 'GET',
  transform: undefined,
  pagination: false,
  pageSize: 10,
  filter: () => ({}),
  requestFn: undefined
})

const options = ref<Record<string, any>[]>([])
const loading = ref<boolean>(false)
const currentPage = ref<number>(1)
const total = ref<number>(0)
const currentQuery = ref<string>('')

/**
 * refreshOptions 刷新选项列表
 * @param query 搜索关键词
 * @param page 页码
 */
const refreshOptions = async (query?: string, page: number = 1) => {
  loading.value = true
  currentPage.value = page
  
  // 更新当前搜索关键词
  if (query !== undefined) {
    currentQuery.value = query || ''
  }

  try {
    const params: Record<string, any> = {
      ...props.filter
    }

    // 添加搜索关键词（使用当前保存的查询关键词）
    const searchQuery = query !== undefined ? query : currentQuery.value
    if (searchQuery !== undefined && searchQuery !== null && searchQuery !== '') {
      params.keyword = searchQuery
    }

    // 添加分页参数（内部处理，固定使用 page 和 pageSize）
    if (props.pagination) {
      params.page = page
      params.pageSize = props.pageSize
    }

    let response: any

    // 使用自定义请求函数或封装的 request
    if (props.requestFn) {
      response = await props.requestFn(props.remoteUrl, params)
    } else {
      if (props.method === 'GET') {
        response = await get(props.remoteUrl, params)
      } else {
        response = await post(props.remoteUrl, params)
      }
    }

    // 转换响应数据
    if (props.transform) {
      const transformed = props.transform(response)
      options.value = transformed.list
      total.value = transformed.total
    } else {
      // 默认处理：支持 {list: [], total: 0} 或直接是数组
      if (Array.isArray(response)) {
        options.value = response
        total.value = response.length
      } else if (response?.list && Array.isArray(response.list)) {
        options.value = response.list
        total.value = response.total || response.list.length
      } else if (response?.data && Array.isArray(response.data)) {
        options.value = response.data
        total.value = response.total || response.data.length
      } else {
        options.value = []
        total.value = 0
      }
    }
  } catch (error) {
    console.error('远程搜索请求失败:', error)
    options.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

// 使用 VueUse 的防抖函数
const debouncedRefreshOptions = useDebounceFn(refreshOptions, 300)

/**
 * remoteMethod 远程搜索方法
 * @param query 搜索关键词
 */
const remoteMethod = (query: string) => {
  debouncedRefreshOptions(query, 1)
}

/**
 * handlePageChange 分页变化处理
 * @param page 页码
 */
const handlePageChange = (page: number) => {
  // 分页时保留当前搜索关键词，分页不需要防抖
  refreshOptions(currentQuery.value, page)
}

/**
 * loadInitialValue 加载初始值对应的选项
 */
const loadInitialValue = async () => {
  if (!model.value) return

  const valueKey = props.props?.value || 'value'
  
  // 如果已有初始选项且值匹配，直接使用
  if (props.initOption && props.initOption[valueKey] === model.value) {
    options.value = [props.initOption]
    return
  }

  // 否则尝试从远程加载
  loading.value = true
  try {
    const params: Record<string, any> = {
      ...props.filter
    }
    
    // 根据值查询对应的选项
    params[valueKey] = model.value

    let response: any
    if (props.requestFn) {
      response = await props.requestFn(props.remoteUrl, params)
    } else {
      if (props.method === 'GET') {
        response = await get(props.remoteUrl, params)
      } else {
        response = await post(props.remoteUrl, params)
      }
    }

    // 处理响应数据
    if (props.transform) {
      const transformed = props.transform(response)
      options.value = transformed.list
    } else {
      if (Array.isArray(response)) {
        options.value = response
      } else if (response?.list && Array.isArray(response.list)) {
        options.value = response.list
      } else if (response?.data && Array.isArray(response.data)) {
        options.value = response.data
      } else if (response && !Array.isArray(response)) {
        // 单个对象
        options.value = [response]
      } else {
        options.value = []
      }
    }
  } catch (error) {
    console.error('加载初始值失败:', error)
    options.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  // 如果有初始选项，直接使用
  if (props.initOption) {
    options.value = [props.initOption]
  } else if (model.value) {
    // 如果有初始值但没有初始选项，尝试加载
    loadInitialValue()
  } else {
    // 初始加载空列表，等待用户搜索
    options.value = []
  }
})

// 监听 model 值变化，用于外部设置值时的回显
watch(() => model.value, (newVal, oldVal) => {
  if (newVal && newVal !== oldVal && options.value.length === 0) {
    const valueKey = props.props?.value || 'value'
    const hasValue = options.value.some(opt => opt[valueKey] === newVal)
    if (!hasValue) {
      loadInitialValue()
    }
  }
})
</script>

<template>
  <el-select
    v-model="model"
    filterable
    remote
    :remote-method="remoteMethod"
    :options="options"
    :loading="loading"
    :props="props"
    :value-on-clear="undefined"
    remote-show-suffix
  >
    <template #footer v-if="pagination">
      <el-pagination
        :current-page="currentPage"
        :total="total"
        :page-size="pageSize"
        :pager-count="5"
        small
        layout="prev, pager, next"
        @current-change="handlePageChange"
      />
    </template>
  </el-select>
</template>

<style scoped>
</style>