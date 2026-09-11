# ga-delete-confirm

源码：[web/src/components/ga-delete-confirm/index.vue](../../../../../web/src/components/ga-delete-confirm/index.vue)

## 作用

点击危险按钮弹出 `el-popover`，确认后执行 `action`（如删除接口），成功则 `ElMessage.success` 并可选 `afterSuccess`。失败走 `showError`。列表页更多使用 [useConfirmAction](../../../../../web/src/composables/useConfirmAction.ts)（`ElMessageBox.confirm`），本组件仓库内暂无引用。

```ts
import GaDeleteConfirm from '@/components/ga-delete-confirm/index.vue'
```

## Props / 事件 / 插槽

| 属性 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `message` | `string` | `'确定删除？'` | 确认文案 |
| `action` | `() => void \| Promise<unknown>` | **必填** | 确认后执行 |
| `successMsg` | `string` | `'已删除'` | 成功提示 |
| `afterSuccess` | `() => void \| Promise<void>` | — | 成功后回调 |

无自定义事件、无具名插槽。触发器固定为带 Delete 图标的 `el-button`（`plain` `danger` `small`）。

## 最小示例

```vue
<script setup lang="ts">
import GaDeleteConfirm from '@/components/ga-delete-confirm/index.vue'
import { del } from '@/apis/request'

const reload = () => { /* load list */ }
</script>
<template>
  <ga-delete-confirm
    message="确定删除该行？"
    :action="() => del('admin/system.auth.Admin/delete', { params: { id: row.id } })"
    :after-success="reload"
  />
</template>
```

## 逃生舱

管理员列表用 `useConfirmAction` + `el-button`，见 [web/src/views/system/auth/admin/index.vue](../../../../../web/src/views/system/auth/admin/index.vue)。

```ts
await ElMessageBox.confirm('确定删除？', '确认', { type: 'warning' })
await del('admin/system.auth.Admin/delete', { params: { id } })
```

或 `el-popconfirm` / `el-popover`。见 [escape-hatches.md](../../../../escape-hatches.md)。
