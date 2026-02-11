<script setup lang="ts">
import { get, post } from "@/utils/request";
import type { SelectOptionProps } from "element-plus/es/components/select/src/select";
import { computed, onMounted, ref, watch } from "vue";

const model = defineModel<any>()

interface Props {
  /** 远程请求地址 */
  remoteUrl: string
  /** el-select 的 props 配置 */
  props?: SelectOptionProps
  /** 请求方法，默认 GET */
  method?: 'GET' | 'POST'
  /** 响应数据转换函数，将接口返回的数据转换为选项格式 */
  transform?: (response: any) => { list: any[], total: number }
  /** 每页数量，默认 10 */
  pageSize?: number
  /** 额外的请求参数，会合并到请求参数中 */
  filter?: Record<string, any>
  /** 自定义请求函数，如果提供则使用此函数替代默认的 fetch */
  requestFn?: (url: string, params: Record<string, any>) => Promise<any>
  /** 初始化选项，用于回显。如果提供且与 v-model 值匹配，将直接使用，无需请求接口 */
  initOptions?: Record<string, any> | Record<string, any>[]
}

const props = withDefaults(defineProps<Props>(), {
  method: 'GET',
  transform: undefined,
  pageSize: 10,
  filter: () => ({}),
  requestFn: undefined
})

const searchOptions = ref<Record<string, any>[]>([]) // 搜索结果选项
const selectedOptions = ref<Record<string, any>[]>([]) // 已选中选项
const loading = ref<boolean>(false)
const currentPage = ref<number>(1)
const total = ref<number>(0)
const currentQuery = ref<string>('')

// 计算 valueKey，用于标识选项的值字段
const valueKey = computed(() => props.props?.value || 'value')
const labelKey = computed(() => props.props?.label || 'label')

// 计算是否需要显示分页器（数据量超过每页数量时才显示）
const shouldShowPagination = computed(() => {
  return total.value > props.pageSize
})

// 根据值获取选项
const getOptionByValue = (value: any, optionsList: Record<string, any>[]): Record<string, any> | undefined => {
  return optionsList.find(opt => opt[valueKey.value] === value)
}


// 处理响应数据转换
const transformResponse = (response: any): { list: any[], total: number } => {
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

// 执行请求
const executeRequest = async (params: Record<string, any>): Promise<any> => {
  if (props.requestFn) {
    return await props.requestFn(props.remoteUrl, params)
  }

  return props.method === 'GET'
      ? await get(props.remoteUrl, params)
      : await post(props.remoteUrl, params)
}

/**
 * refreshOptions 刷新选项列表
 * @param query 搜索关键词，如果为 undefined 则使用当前保存的查询关键词
 * @param page 页码
 * @param useModelValue 是否使用 model.value 作为查询条件（用于自动加载初始值）
 */
const refreshOptions = async (query?: string, page: number = 1, useModelValue: boolean = false) => {
  console.log("query", query)
  console.log("currentQuery.value", currentQuery.value)
  if (currentQuery.value !== "" && (query == null || query == "")) {
    return
  }

  loading.value = true
  currentPage.value = page
  // searchOptions.value = []  
  // 更新当前搜索关键词
  if (query !== undefined) {
    currentQuery.value = query || ''
  }

  try {
    const params: Record<string, any> = {
      filters: {...props.filter}
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

    const response = await executeRequest(params)
    const transformed = transformResponse(response)

    // 处理搜索结果
    if (useModelValue) {
      // 使用 model.value 查询时，将返回结果添加到 searchOptions，供 watch 查找
      searchOptions.value = transformed.list
    } else {
      // 普通搜索，更新搜索结果
      searchOptions.value = transformed.list
    }
    // selectedOptions 的更新由 watch 统一处理，这里不需要重复处理

    total.value = transformed.total
  } catch (error) {
    console.error('远程搜索请求失败:', error)
    if (!useModelValue) {
      searchOptions.value = []
    }
    total.value = 0
  } finally {
    loading.value = false
  }
}

/**
 * remoteMethod 远程搜索方法
 * @param query 搜索关键词
 */
const remoteMethod = (query: string) => {
  refreshOptions(query, 1, false)
}

/**
 * handlePageChange 分页变化处理
 * @param page 页码
 */
const handlePageChange = (page: number) => {
  // 分页时保留当前搜索关键词，分页不需要防抖
  refreshOptions(currentQuery.value, page, false)
}
const cleanQuery = () => {
  currentQuery.value = ''
  refreshOptions(undefined, 1, false)
}

// 监听 model 值变化，当值变化时自动刷新选项
watch(() => model.value, (newVal, oldVal) => {
  if (newVal != null && newVal !== oldVal) {
    // 优先检查 initOptions
    if (props.initOptions) {
      const initOptionsArray = Array.isArray(props.initOptions)
          ? props.initOptions
          : [props.initOptions]

      if (Array.isArray(newVal)) {
        // 多选场景：检查 initOptions 是否完全匹配
        if (initOptionsArray.length === newVal.length) {
          const allMatched = newVal.every((item: any) =>
              initOptionsArray.some((opt: any) => opt[valueKey.value] === item)
          )
          const allOptionsMatched = initOptionsArray.every((opt: any) =>
              newVal.includes(opt[valueKey.value])
          )

          if (allMatched && allOptionsMatched) {
            selectedOptions.value = initOptionsArray
            return
          }
        }
      } else {
        // 单选场景：检查是否有匹配的选项
        const matchedOption = initOptionsArray.find((opt: any) => opt[valueKey.value] === newVal)
        if (matchedOption) {
          selectedOptions.value = [matchedOption]
          return
        }
      }
    }

    // 检查是否已经在已选中或搜索结果中存在
    if (Array.isArray(newVal)) {
      // 多选场景：从已选中和搜索结果中查找所有匹配的选项
      const matchedOptions = newVal
          .map((val: any) => getOptionByValue(val, [...selectedOptions.value, ...searchOptions.value]))
          .filter((opt): opt is Record<string, any> => opt !== undefined)

      if (matchedOptions.length === newVal.length) {
        // 所有值都找到了对应的选项
        selectedOptions.value = matchedOptions
      } else {
        // 有值找不到对应选项，需要加载
        refreshOptions(undefined, 1, true).then(() => {
          // 接口返回后，再次从 searchOptions 中查找
          const matchedAfterLoad = newVal
              .map((val: any) => getOptionByValue(val, [...selectedOptions.value, ...searchOptions.value]))
              .filter((opt): opt is Record<string, any> => opt !== undefined)
          if (matchedAfterLoad.length > 0) {
            // 保留不在搜索结果中但仍在 model.value 中的已选选项
            const existingValidOptions = selectedOptions.value.filter((opt: any) => {
              const optValue = opt[valueKey.value]
              return newVal.includes(optValue) && 
                     !matchedAfterLoad.some((f: any) => f[valueKey.value] === optValue)
            })
            selectedOptions.value = [...existingValidOptions, ...matchedAfterLoad].filter((opt, index, self) => 
              index === self.findIndex((o) => o[valueKey.value] === opt[valueKey.value])
            )
          }
        })
      }
    } else {
      // 单选场景
      const existingOption = getOptionByValue(newVal, [...selectedOptions.value, ...searchOptions.value])

      if (existingOption) {
        // 如果已存在，移动到已选中分组
        selectedOptions.value = [existingOption]
      } else {
        // 如果不存在，加载对应的选项
        refreshOptions(undefined, 1, true).then(() => {
          // 接口返回后，再次从 searchOptions 中查找
          const foundAfterLoad = getOptionByValue(newVal, [...selectedOptions.value, ...searchOptions.value])
          if (foundAfterLoad) {
            selectedOptions.value = [foundAfterLoad]
          }
        })
      }
    }
  } else if (newVal == null) {
    // 值被清空时，清空已选中选项
    selectedOptions.value = []
  }
}, {immediate: false})

// 组件挂载时，如果 model.value 存在，自动加载对应的选项
onMounted(() => {
  if (model.value != null) {
    // 优先使用 initOptions
    if (props.initOptions) {
      const initOptionsArray = Array.isArray(props.initOptions)
          ? props.initOptions
          : [props.initOptions]

      // initOptions 要求必须与 model.value 完全匹配
      if (Array.isArray(model.value)) {
        // 多选场景：检查 initOptions 数组长度和值是否完全匹配
        if (initOptionsArray.length === model.value.length) {
          // 检查每个 model.value 是否都在 initOptions 中有对应项
          const allMatched = model.value.every((item: any) =>
              initOptionsArray.some((opt: any) => opt[valueKey.value] === item)
          )
          // 检查每个 initOptions 是否都在 model.value 中有对应值
          const allOptionsMatched = initOptionsArray.every((opt: any) =>
              model.value.includes(opt[valueKey.value])
          )

          if (allMatched && allOptionsMatched) {
            selectedOptions.value = initOptionsArray
            searchOptions.value = []
            return
          }
        }
      } else {
        // 单选场景：检查是否有匹配的选项
        const matchedOption = initOptionsArray.find((opt: any) => opt[valueKey.value] === model.value)
        if (matchedOption) {
          // 如果找到匹配的选项，直接使用，无需请求接口
          selectedOptions.value = [matchedOption]
          searchOptions.value = []
          return
        }
      }
    }

    // 如果没有 initOptions 或没有匹配的选项，则请求接口
    refreshOptions(undefined, 1, true)
  } else {
    // 初始加载空列表，等待用户搜索
    searchOptions.value = []
    selectedOptions.value = []
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
      :props="props"
      :value-on-clear="undefined"
      remote-show-suffix
      :debounce="300"
  >
    <template #header v-if="currentQuery">
      当前搜索：
      <el-tag closable @close="cleanQuery">{{ currentQuery }}</el-tag>
    </template>
    <!-- 搜索结果分组 -->
    <el-option-group v-if="searchOptions.length > 0" label="搜索结果">
      <el-option
          v-for="option in searchOptions"
          :key="option[valueKey]"
          :label="option[labelKey]"
          :value="option[valueKey]"
      />
    </el-option-group>

    <!-- 已选中数据分组 -->
    <el-option-group v-if="selectedOptions.length > 0" label="已选中">
      <el-option
          v-for="option in selectedOptions"
          :key="option[valueKey]"
          :label="option[labelKey]"
          :value="option[valueKey]"
      />
    </el-option-group>

    <template #footer v-if="shouldShowPagination">
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