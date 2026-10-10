# useTreeCrud

源码：[web/src/composables/useTreeCrud.ts](../../../../web/src/composables/useTreeCrud.ts)

## 作用

树形列表的加载、删除确认和编辑弹窗状态。不渲染树。`T` 须有 `id: number`，子节点字段为可选的 `children`。删除确认走 [useConfirmAction](../../../../web/src/composables/useConfirmAction.ts)（`ElMessageBox`）。

本函数**不会**在创建时自动 `load`。调用方在 `onMounted(load)` 里拉第一棵树。

## 推荐使用场景

- 一次拉整棵树，再在本地展开；删除后整树重载。角色、权限规则是这种页面。
- 分页表：用 [usePaginatedList](usePaginatedList.md) 和 [ga-table](../components/ga-table/index.md)。
- 确认框文案或成功后的动作要完全自己定：直接 `useConfirmAction`。

## API / 返回值

```ts
useTreeCrud<T>(fetchTree, deleteItem, options?)
```

| 参数 | 含义 |
|---|---|
| `fetchTree` | `() => Promise<T[]>` |
| `deleteItem` | `(id: number) => Promise<unknown>` |
| `options.getDeleteMessage` | 默认 `'确定删除该节点？'` |
| `options.getRowId` | 默认 `row.id` |
| `options.deleteSuccessMsg` | 默认 `'已删除'` |

| 返回值 | 含义 |
|---|---|
| `treeData` | 根节点数组（`shallowRef`） |
| `loading` | `load` 进行中 |
| `topIds` | 第一层 `id`，供默认展开 |
| `editController` | `{ show?, id?, pid? }` |
| `load` | 调 `fetchTree`；失败 `showError` |
| `remove(row)` | 确认后 `deleteItem`，成功再 `load` |
| `openEdit(id)` | `{ show: true, id, pid: undefined }` |
| `openAdd()` | `{ show: true, id: undefined, pid: undefined }` |
| `openAddChild(pid)` | `{ show: true, id: undefined, pid }` |
| `onSaved` | `show = false` 并 `load` |

## 最小示例

与 [role/index.vue](../../../../web/src/views/system/auth/role/index.vue) 相同的接法：

```ts
import { useTreeCrud } from '@/composables/useTreeCrud'
import { onMounted } from 'vue'

const { treeData, loading, load, remove, openEdit, openAdd, openAddChild, onSaved } =
  useTreeCrud(
    () => roleApi.fetchTree(),
    (id) => roleApi.deleteRole(id),
    { getDeleteMessage: () => '确定删除该角色？' },
  )

onMounted(load)
```

## 已使用场景

- [web/src/views/system/auth/role/index.vue](../../../../web/src/views/system/auth/role/index.vue)（`onMounted(load)`）
- [web/src/views/system/auth/rule/useRuleTreePage.ts](../../../../web/src/views/system/auth/rule/useRuleTreePage.ts)（[frontend.vue](../../../../web/src/views/system/auth/rule/frontend.vue)、[backend.vue](../../../../web/src/views/system/auth/rule/backend.vue) 再 `onMounted(load)`）

CRUD 生成器写的是分页列表（[IndexFile.php](../../../../extend/crud/generator/IndexFile.php) 的 `usePaginatedList`），不生成本函数。

## 逃生舱

`el-tree` 自己请求，删除用 `useConfirmAction` 或 `ElMessageBox`。见 [escape-hatches.md](../../../escape-hatches.md)。
