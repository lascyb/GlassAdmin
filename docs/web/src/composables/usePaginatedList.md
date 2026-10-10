# usePaginatedList

源码：[web/src/composables/usePaginatedList.ts](../../../../web/src/composables/usePaginatedList.ts)

## 作用

分页列表的状态：`page` / `limit` / `list` / `total` / `loading`，以及 `load`。不渲染表格。把返回值绑到 [ga-table](../components/ga-table/index.md)（或自己的 `el-table` + `el-pagination`）。请求由传入的 `fetcher` 发出，一般是 `get` / `post` 解包后的 `{ list, total }`。

## 推荐使用场景

- 管理端标准分页列表，和 `ga-table` 的 `v-model:page` / `v-model:limit`、`@load` 对接。
- 树形整表加载：用 [useTreeCrud](useTreeCrud.md)，不要用本函数。
- 只有一页、或分页字段不是 `page` / `limit`：页面自己写 `ref` 与请求。

## API / 返回值

```ts
usePaginatedList<T, P>(fetcher, options?)
```

| 参数 | 含义 |
|---|---|
| `fetcher` | `(params: P & { page, limit }) => Promise<{ list, total, page?, limit? }>` |
| `options.immediate` | 默认 `true`：`onMounted` 时 `load` |
| `options.defaultLimit` | 默认 `20` |
| `options.params` | 额外查询参数；`ref` / `computed` / 普通对象，请求时 `unref` |

| 返回值 | 含义 |
|---|---|
| `list` / `total` / `page` / `limit` / `loading` | 列表状态 |
| `load` | 用当前页码请求；`fetcher` 抛错时吞掉（请求层已提示），`loading` 仍会关掉 |
| `resetPage` | `page = 1` 再 `load` |
| `search` | 同 `resetPage` |

响应里若带 `page` / `limit`，会写回对应 ref。

## 最小示例

与 [attachment/index.vue](../../../../web/src/views/system/attachment/index.vue) 相同的接法：

```ts
import { usePaginatedList } from '@/composables/usePaginatedList'
import GaTable from '@/components/ga-table/index.vue'

const { list, total, page, limit, loading, load, search } = usePaginatedList(
  (params) => attachmentApi.fetchList(params),
  { params: queryParams },
)
```

```vue
<ga-table
  v-model:page="page"
  v-model:limit="limit"
  :loading="loading"
  :list="list"
  :total="total"
  @load="load"
/>
```

## 已使用场景

- [web/src/views/system/auth/admin/index.vue](../../../../web/src/views/system/auth/admin/index.vue)
- [web/src/views/system/attachment/index.vue](../../../../web/src/views/system/attachment/index.vue)
- [web/src/views/system/account/profile.vue](../../../../web/src/views/system/account/profile.vue)（操作日志）
- CRUD 生成会写入 import：[extend/crud/generator/IndexFile.php](../../../../extend/crud/generator/IndexFile.php)（模板 [index.vue.tpl](../../../../extend/crud/templates/index/index.vue.tpl)）

## 逃生舱

页面内自己维护 `page` / `limit` 并调用 `get` / `post`。见 [ga-table](../components/ga-table/index.md) 的最小示例，以及 [escape-hatches.md](../../../escape-hatches.md)。
