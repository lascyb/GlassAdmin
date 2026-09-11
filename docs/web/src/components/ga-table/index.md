# ga-table

源码：[web/src/components/ga-table/index.vue](../../../../../web/src/components/ga-table/index.vue)

## 作用

列表布局：工具栏插槽 + `el-table` + `el-pagination`。不发请求、不定义列。数据由父组件传入，列放在默认插槽的 `el-table-column`。行为见 [Element Plus Table](https://element-plus.org/zh-CN/component/table.html)、[Pagination](https://element-plus.org/zh-CN/component/pagination.html)。

须显式导入（`web/vite.config.ts` 中 `Components({ dirs: [] })`）：

```ts
import GaTable from '@/components/ga-table/index.vue'
```

`fill` 为 true 时 flex 撑满高度，滚动由 `el-table` 的 `height="100%"` 处理。弹窗内 `:fill="false"`。固定：`highlight-current-row`；分页 `layout` 为 `total, sizes, prev, pager, next`，带 `background`。未透传 `row-key`、`span-method` 等。

## Props / 事件 / 插槽

| 绑定 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `v-model:page` | `number` | `1` | `el-pagination` 的 `current-page` |
| `v-model:limit` | `number` | `20` | `page-size` |

| 属性 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `loading` | `boolean` | `false` | `el-table` 的 `v-loading` |
| `list` | `unknown[]` | `[]` | `el-table` 的 `data` |
| `total` | `number` | `0` | `el-pagination` 的 `total` |
| `emptyText` | `string` | `'暂无数据'` | `empty-text` |
| `pageSizes` | `number[]` | `[10, 20, 50]` | 每页条数选项 |
| `showPagination` | `boolean` | `true` | 是否渲染分页 |
| `border` | `boolean` | `true` | `border` |
| `stripe` | `boolean` | `true` | 斑马纹 |
| `size` | `'default' \| 'small' \| 'large'` | `'default'` | `size` |
| `fill` | `boolean` | `true` | 是否撑满可用高度 |

| 事件 | 载荷 | 时机 |
|---|---|---|
| `load` | 无 | 翻页或改页大小 |

| 插槽 | 用途 |
|---|---|
| 默认 | `el-table-column` |
| `toolbar` | 表格上方 |
| `footer` | 分页下方 |

## 最小示例

参考 [web/src/views/system/auth/admin/index.vue](../../../../../web/src/views/system/auth/admin/index.vue)。

```vue
<script setup lang="ts">
import { get } from '@/apis/request'
import GaTable from '@/components/ga-table/index.vue'
import { onMounted, ref } from 'vue'

const list = ref<{ id: number; username: string }[]>([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)
const loading = ref(false)

const load = async () => {
  loading.value = true
  try {
    const res = await get<{ list: typeof list.value; total: number }>(
      'admin/system.auth.Admin/list',
      { params: { page: page.value, limit: limit.value } },
    )
    list.value = res.list
    total.value = res.total
  } finally {
    loading.value = false
  }
}
onMounted(load)
</script>

<template>
  <ga-table v-model:page="page" v-model:limit="limit" :loading="loading" :list="list" :total="total" @load="load">
    <el-table-column prop="id" label="ID" width="72" />
    <el-table-column prop="username" label="用户名" />
  </ga-table>
</template>
```

## 逃生舱

[web/src/views/system/config/index.vue](../../../../../web/src/views/system/config/index.vue)、[web/src/views/dashboard/usage.vue](../../../../../web/src/views/dashboard/usage.vue) 使用裸 `el-table`。

```vue
<el-table v-loading="loading" :data="list" border stripe>
  <el-table-column prop="username" label="用户名" />
</el-table>
<el-pagination
  v-model:current-page="page"
  v-model:page-size="limit"
  :total="total"
  layout="total, sizes, prev, pager, next"
  background
  @current-change="load"
  @size-change="load"
/>
```

见 [escape-hatches.md](../../../../escape-hatches.md)。
