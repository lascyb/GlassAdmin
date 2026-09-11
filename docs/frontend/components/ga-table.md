# ga-table

源码：[`web/src/components/ga-table/index.vue`](../../../web/src/components/ga-table/index.vue)

列表布局：工具栏插槽 + `el-table` + `el-pagination`。不发请求、不定义列。数据由父组件传入。列放在默认插槽的 `el-table-column` 中。表格与分页行为见 [Element Plus Table](https://element-plus.org/zh-CN/component/table.html)、[Pagination](https://element-plus.org/zh-CN/component/pagination.html)。

本地组件不自动注册（`web/vite.config.ts` 中 `Components({ dirs: [] })`）：

```ts
import GaTable from '@/components/ga-table/index.vue'
```

---

## 结构

```vue
<div class="ga-table">
  <slot name="toolbar" />
  <el-table v-loading="loading" :data="list" :border :stripe :size :empty-text :height="fill ? '100%' : undefined">
    <slot />
  </el-table>
  <el-pagination v-if="showPagination" v-model:current-page="page" v-model:page-size="limit" ... />
  <slot name="footer" />
</div>
```

`fill` 为 true 时用 flex 撑满可用高度，滚动由 `el-table` 的 `height="100%"` 处理。弹窗嵌入时设 `:fill="false"`（管理员列表嵌入角色页）。

---

## Props / 双向绑定 / Events / Slots

### `v-model`

| 绑定 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `v-model:page` | `number` | `1` | 当前页，对应 `el-pagination` 的 `current-page` |
| `v-model:limit` | `number` | `20` | 每页条数，对应 `page-size` |

### Props

| 属性 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `loading` | `boolean` | `false` | `el-table` 的 `v-loading` |
| `list` | `unknown[]` | `[]` | `el-table` 的 `data` |
| `total` | `number` | `0` | `el-pagination` 的 `total` |
| `emptyText` | `string` | `'暂无数据'` | `el-table` 的 `empty-text` |
| `pageSizes` | `number[]` | `[10, 20, 50]` | 每页条数选项 |
| `showPagination` | `boolean` | `true` | 是否渲染分页 |
| `border` | `boolean` | `true` | `el-table` 的 `border` |
| `stripe` | `boolean` | `true` | 斑马纹 |
| `size` | `'default' \| 'small' \| 'large'` | `'default'` | `el-table` 的 `size` |
| `fill` | `boolean` | `true` | 是否在可用高度内撑满 |

未透传 `el-table` 的其余属性（无 `row-key`、`span-method` 等）。需要这些 API 时使用 `el-table`（见下方）。

固定行为：`highlight-current-row` 始终开启；分页 `layout` 为 `total, sizes, prev, pager, next`，带 `background`。

### Events

| 事件 | 载荷 | 时机 |
|---|---|---|
| `load` | 无 | `el-pagination` 的 `@current-change` 或 `@size-change` |

翻页或改页大小时 `emit('load')`，由父组件请求数据。

### Slots

| 插槽 | 用途 |
|---|---|
| 默认 | `el-table` 内部，放置 `el-table-column` |
| `toolbar` | 表格上方 |
| `footer` | 分页下方 |

---

## 示例

列表实现参考：[web/src/views/system/auth/admin/index.vue](../../../web/src/views/system/auth/admin/index.vue)、[web/src/apis/system/auth/admin.ts](../../../web/src/apis/system/auth/admin.ts)。[`usePaginatedList`](../../../web/src/composables/usePaginatedList.ts) 不是 `ga-table` 的一部分。

```vue
<script setup lang="ts">
import { get } from '@/apis/request'
import GaTable from '@/components/ga-table/index.vue'
import { onMounted, ref } from 'vue'

type Row = { id: number; username: string }
type ListRes = { list: Row[]; total: number; page: number; limit: number }

const list = ref<Row[]>([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)
const loading = ref(false)

const load = async () => {
  loading.value = true
  try {
    const res = await get<ListRes>('admin/system.auth.Admin/list', {
      params: { page: page.value, limit: limit.value },
    })
    list.value = res.list
    total.value = res.total
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <ga-table
    v-model:page="page"
    v-model:limit="limit"
    :loading="loading"
    :list="list"
    :total="total"
    @load="load"
  >
    <el-table-column prop="id" label="ID" width="72" />
    <el-table-column prop="username" label="用户名" />
  </ga-table>
</template>
```

后端：[app/admin/controller/system/auth/Admin.php](../../../app/admin/controller/system/auth/Admin.php) 的 `list()` 返回 `$this->paginate($items, $total, $page, $limit)`。

`fill` 需要父级高度。整页列表通常包在 `ga-page` 并设置 `minHeight`（[ga-page.md](ga-page.md)）；弹窗内 `:fill="false"`。

---

## 改用 Element Plus 的场景

- 无分页、无工具栏，仅展示若干行。[web/src/views/dashboard/usage.vue](../../../web/src/views/dashboard/usage.vue) 使用裸 `el-table`。
- 需要未透传的 `el-table` API（多选、树形、自定义 `height`/`max-height`、合并单元格）。
- 分页 `layout` 与组件内写死的值不同。
- 数据形状不是 `{list,total}`。

---

## 改用 el-table 与 get

[web/src/views/system/config/index.vue](../../../web/src/views/system/config/index.vue) 未使用 `ga-table`。带分页的等价写法：

```vue
<script setup lang="ts">
import { get } from '@/apis/request'
import { onMounted, ref } from 'vue'

type Row = { id: number; username: string }
type ListRes = { list: Row[]; total: number }

const list = ref<Row[]>([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)
const loading = ref(false)

const load = async () => {
  loading.value = true
  try {
    const res = await get<ListRes>('admin/system.auth.Admin/list', {
      params: { page: page.value, limit: limit.value },
    })
    list.value = res.list
    total.value = res.total
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <el-table v-loading="loading" :data="list" border stripe>
    <el-table-column prop="id" label="ID" width="72" />
    <el-table-column prop="username" label="用户名" />
  </el-table>
  <el-pagination
    v-model:current-page="page"
    v-model:page-size="limit"
    :total="total"
    :page-sizes="[10, 20, 50]"
    layout="total, sizes, prev, pager, next"
    background
    class="mt-3 justify-end"
    @current-change="load"
    @size-change="load"
  />
</template>
```

`fetch` 替代 `get`：[request.md](../request.md)、[escape-hatches.md](../../escape-hatches.md)。
