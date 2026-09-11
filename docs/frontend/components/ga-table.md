# ga-table

源码：[`web/src/components/ga-table/index.vue`](../../../web/src/components/ga-table/index.vue)

列表页壳：工具栏槽 + `el-table` + `el-pagination`。**不发请求、不定义列**。数据由父组件传入，列用默认插槽里的 `el-table-column`。表格行为以 [Element Plus Table](https://element-plus.org/zh-CN/component/table.html) / [Pagination](https://element-plus.org/zh-CN/component/pagination.html) 为准。

本地组件不会自动注册（`web/vite.config.ts` 中 `Components({ dirs: [] })`），页面必须：

```ts
import GaTable from '@/components/ga-table/index.vue'
```

---

## 它实际包了什么

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

`fill` 为 true 时用 flex 把表体撑满可用高度，滚动交给 `el-table` 的 `height="100%"`。弹窗嵌入应关 `fill`（管理员列表嵌入角色页时就是这样做的）。

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
| `loading` | `boolean` | `false` | 传给 `el-table` 的 `v-loading` |
| `list` | `unknown[]` | `[]` | 传给 `el-table` 的 `data` |
| `total` | `number` | `0` | 传给 `el-pagination` 的 `total` |
| `emptyText` | `string` | `'暂无数据'` | `el-table` 的 `empty-text` |
| `pageSizes` | `number[]` | `[10, 20, 50]` | 每页条数选项 |
| `showPagination` | `boolean` | `true` | 是否渲染分页 |
| `border` | `boolean` | `true` | `el-table` 的 `border` |
| `stripe` | `boolean` | `true` | 斑马纹 |
| `size` | `'default' \| 'small' \| 'large'` | `'default'` | `el-table` 的 `size` |
| `fill` | `boolean` | `true` | 是否在可用高度内撑满 |

未向 `el-table` 透传其它属性（无 `row-key`、无 `span-method` 等）。需要这些时用默认插槽解决不了，请直接用 `el-table`（见下方逃生舱）。

固定行为：`highlight-current-row` 始终开启；分页 `layout` 固定为 `total, sizes, prev, pager, next`，带 `background`。

### Events

| 事件 | 载荷 | 何时 |
|---|---|---|
| `load` | 无 | `el-pagination` 的 `@current-change` 或 `@size-change` |

翻页/改页大小时组件只 `emit('load')`，由父组件重新请求。

### Slots

| 插槽 | 用途 |
|---|---|
| 默认 | `el-table` 内部，放 `el-table-column` |
| `toolbar` | 表格上方，常见放 `ga-search-bar` |
| `footer` | 分页下方，常见放编辑弹窗 |

---

## 最小示例

对照管理员列表 [`web/src/views/system/auth/admin/index.vue`](../../../web/src/views/system/auth/admin/index.vue) + [`web/src/apis/system/auth/admin.ts`](../../../web/src/apis/system/auth/admin.ts) + [`web/src/composables/usePaginatedList.ts`](../../../web/src/composables/usePaginatedList.ts)。`usePaginatedList` 不是 `ga-table` 的一部分，可以不用。

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

后端：[`app/admin/controller/system/auth/Admin.php`](../../../app/admin/controller/system/auth/Admin.php) `list()` 返回 `$this->paginate($items, $total, $page, $limit)`。

`fill` 依赖父级给高度。整页列表通常包在 `ga-page` 里并设 `minHeight`（见 [`ga-page.md`](ga-page.md)）；弹窗内设 `:fill="false"`。

---

## 何时直接用 Element Plus

- 不要分页、不要工具栏槽，只是展示几行（仪表盘 [`web/src/views/dashboard/usage.vue`](../../../web/src/views/dashboard/usage.vue) 已是裸 `el-table`）。
- 需要 `el-table` 未透传的 API（多选、树形、自定义 `height`/`max-height`、合并单元格等）。
- 分页布局要改成 `ga-table` 写死的 `layout` 之外的样子。
- 数据不是 `{list,total}` 这种列表。

---

## 逃生舱：`el-table` + `request`

系统配置页已不用 `ga-table`：[`web/src/views/system/config/index.vue`](../../../web/src/views/system/config/index.vue)。带分页的等价写法：

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

也可以不用 `get`，改原生 `fetch`，见 [`docs/frontend/request.md`](../request.md) 与 [`docs/escape-hatches.md`](../../escape-hatches.md)。
