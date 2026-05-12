<script setup lang="ts">
import {get, post} from "@/utils/request";
import type {SelectOptionProps} from "element-plus/es/components/select/src/select";
import {computed, onMounted, ref, useSlots, shallowRef} from "vue";

/**
 * 组件双向绑定的值，支持单选或多选
 * 用途：用于存储选中的选项值
 */
const model = defineModel<any>()

/**
 * Props 组件属性接口
 * 用途：定义组件接收的所有配置项
 */
interface Props {
  /** 远程请求地址，用于获取选项数据 */
  remoteUrl: string
  /** el-select 的 props 配置，用于自定义选项的 value 和 label 字段名 */
  props?: SelectOptionProps
  /** 请求方法，默认 GET。{GET: 'GET', POST: 'POST'} */
  method?: 'GET' | 'POST'
  /** 响应数据转换函数，将接口返回的数据转换为选项格式。用途：适配不同后端接口的数据结构 */
  transform?: (response: any) => { list: any[], total: number }
  /** 每页数量，默认 10。用途：控制分页大小 */
  pageSize?: number
  /** 额外的请求参数，会合并到请求参数中。用途：传递额外的筛选条件 */
  filters?: Record<string, any>
  /** 自定义请求函数，如果提供则使用此函数替代默认的 fetch。用途：支持自定义请求逻辑，如添加特殊认证、拦截器等 */
  requestFn?: (url: string, params: Record<string, any>) => Promise<any>
  /** 初始化选项，用于回显。如果提供且与 v-model 值匹配，将直接使用，无需请求接口。用途：编辑场景下的数据回显优化 */
  initOptions?: Record<string, any> | Record<string, any>[]
}

const props = withDefaults(defineProps<Props>(), {
  method: 'GET',
  pageSize: 10,
  filters: () => ({}),
})

/** 搜索结果选项列表 */
const options = ref<Record<string, any>[]>([])
/** 加载状态，用于显示 loading 动画 */
const loading = ref<boolean>(false)
/** 当前页码 */
const currentPage = ref<number>(1)
/** 数据总数，用于分页计算 */
const total = ref<number>(0)
/** 当前搜索关键词，用于分页时保留搜索条件 */
const currentQuery = ref<string>('')

/**
 * passthroughSlotNames 需要透传的 el-select 插槽名称列表
 * 用途：自动兼容除 default、header、footer 外的所有 el-select 插槽
 */
const slots = useSlots()
const passthroughSlotNames = shallowRef(Object.keys(slots).filter(name => !['default', 'header', 'footer'].includes(name)))

/**
 * valueKey 计算属性：用于标识选项的值字段
 * 用途：支持自定义选项的值字段名，默认使用 'value'
 */
const valueKey = computed(() => props.props?.value || 'value')

/**
 * labelKey 计算属性：用于标识选项的标签字段
 * 用途：支持自定义选项的标签字段名，默认使用 'label'
 */
const labelKey = computed(() => props.props?.label || 'label')

/**
 * shouldShowPagination 计算属性：判断是否需要显示分页器
 * 用途：当数据量超过每页数量时才显示分页器，避免不必要的 UI 元素
 */
const shouldShowPagination = computed(() => {
  return total.value > props.pageSize
})

/**
 * transformResponse 处理响应数据转换
 * 用途：将不同格式的接口响应统一转换为组件需要的格式 {list: [], total: 0}
 * 支持的数据格式：
 * - 直接数组：Array
 * - 对象包含 list：{list: [], total: number}
 * - 对象包含 data：{data: [], total: number}
 * - 单个对象：Object（用于根据值查询单个选项的场景）
 *
 * @param response 接口返回的原始数据
 * @returns 转换后的数据格式 {list: any[], total: number}
 */
const transformResponse = (response: any): { list: any[], total: number } => {
  // 如果提供了自定义转换函数，优先使用
  if (props.transform) {
    return props.transform(response)
  }

  // todo 增加全局通用的数据响应解构,默认处理：支持 {list: [], total: 0} 或直接是数组
  if (Array.isArray(response)) {
    return {list: response, total: response.length}
  } else if (response?.list && Array.isArray(response.list)) {
    return {list: response.list, total: response.total ?? response.list.length}
  } else if (response?.data && Array.isArray(response.data)) {
    return {list: response.data, total: response.total ?? response.data.length}
  } else if (response && !Array.isArray(response)) {
    // 单个对象（用于根据值查询单个选项的场景）
    return {list: [response], total: 1}
  } else {
    return {list: [], total: 0}
  }
}

/**
 * executeRequest 执行请求
 * 用途：统一处理请求逻辑，支持自定义请求函数或使用默认的 GET/POST 方法
 *
 * @param params 请求参数
 * @returns Promise<any> 请求响应数据
 */
const executeRequest = async (params: Record<string, any>): Promise<any> => {
  // 如果提供了自定义请求函数，优先使用
  if (props.requestFn) {
    return await props.requestFn(props.remoteUrl, params)
  }

  // 根据 method 选择 GET 或 POST 请求
  return props.method === 'GET'
      ? await get(props.remoteUrl, params)
      : await post(props.remoteUrl, params)
}

/**
 * refreshOptions 刷新选项列表
 * 用途：核心方法，负责发起请求、处理响应、更新选项列表和分页信息
 *
 * @param query 搜索关键词，如果为 undefined 则使用当前保存的查询关键词
 * @param page 页码，默认为 1
 * @param useModelValue 是否使用 model.value 作为查询条件（用于自动加载初始值）
 */
const refreshOptions = async (query?: string, page: number = 1, useModelValue: boolean = false) => {
  // 防抖优化：如果已有搜索关键词且新查询为空，则不执行请求
  if (currentQuery.value !== "" && (query == null || query == "")) {
    return
  }

  loading.value = true
  currentPage.value = page
  // 更新当前搜索关键词
  currentQuery.value = query || ''

  try {
    // 构建请求参数
    const params: Record<string, any> = {
      filters: {...props.filters}
    }

    // 如果使用 model.value 作为查询条件（用于自动加载初始值）
    if (useModelValue && model.value != null) {
      params.init_values = model.value
    } else {
      // 添加搜索关键词（使用当前保存的查询关键词）
      const searchQuery = query !== undefined ? query : currentQuery.value
      if (searchQuery != null && searchQuery !== '') {
        params.keyword = searchQuery
      }
    }

    // 添加分页参数（固定使用 page 和 pageSize）
    params.page = page
    params.pageSize = props.pageSize

    // 执行请求并转换响应数据
    const response = await executeRequest(params)
    const transformed = transformResponse(response)

    // 更新选项列表和总数
    options.value = transformed.list
    total.value = transformed.total
  } catch (error) {
    console.error('远程搜索请求失败:', error)
    // 如果是非初始值加载失败，清空选项列表
    if (!useModelValue) {
      options.value = []
    }
    total.value = 0
  } finally {
    loading.value = false
  }
}

/**
 * remoteMethod 远程搜索方法
 * 用途：作为 el-select 的 remote-method 回调，处理用户输入搜索
 *
 * @param query 搜索关键词
 */
const remoteMethod = (query: string) => {
  if (query) {
    refreshOptions(query, 1, false)
  } else {
    refreshOptions(query, currentPage.value, false) //保持在同一页
  }
}

/**
 * handlePageChange 分页变化处理
 * 用途：处理用户切换页码时的逻辑，保留当前搜索关键词
 *
 * @param page 页码
 */
const handlePageChange = (page: number) => {
  // 分页时保留当前搜索关键词，分页不需要防抖
  refreshOptions(currentQuery.value, page, false)
}

/**
 * cleanQuery 清除搜索关键词
 * 用途：清除当前搜索条件并重新加载第一页数据
 */
const cleanQuery = () => {
  currentQuery.value = ''
  refreshOptions(undefined, 1, false)
}

/**
 * 组件挂载时的初始化逻辑
 * 用途：根据 v-model 初始值和 initOptions 决定是否需要自动加载选项数据
 * 优先级：initOptions > model.value > 空列表
 */
onMounted(() => {
  // 优先使用 initOptions（编辑场景下的回显优化）
  if (props.initOptions) {
    options.value = Array.isArray(props.initOptions)
        ? props.initOptions
        : [props.initOptions]
  } else if (model.value != null) {
    // 如果没有 initOptions 但 model.value 存在，则请求接口加载对应选项
    refreshOptions(undefined, 1, true)
  } else {
    // 初始加载空列表，等待用户搜索
    options.value = []
  }
})
</script>

<template>
  <el-select
      v-model="model"
      filterable
      remote
      :remote-method="remoteMethod"
      :loading="loading"
      :props="props.props"
      :options="options"
      :value-on-clear="undefined"
      :debounce="300"
  >

    <template #header v-if="currentQuery">
      当前搜索：
      <el-tag closable @close="cleanQuery">{{ currentQuery }}</el-tag>
    </template>
    <!-- 搜索结果 -->
    <el-option
        v-for="option in options"
        :key="option[valueKey]"
        :label="option[labelKey]"
        :value="option[valueKey]"
    />

    <template #footer>
      <el-pagination
          v-if="shouldShowPagination"
          :current-page="currentPage"
          :total="total"
          :page-size="pageSize"
          :pager-count="5"

          layout="prev, pager, next, jumper, ->, total"
          @current-change="handlePageChange"
      />
    </template>
    <template
        v-for="name in passthroughSlotNames"
        :key="name"
        #[name]="slotProps"
    >
      <slot :name="name" v-bind="slotProps"/>
    </template>
  </el-select>
</template>

<style scoped>
</style>