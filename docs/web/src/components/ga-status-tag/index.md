# ga-status-tag

源码：[web/src/components/ga-status-tag/index.vue](../../../../../web/src/components/ga-status-tag/index.vue)  
类型：[web/src/composables/types.ts](../../../../../web/src/composables/types.ts) 的 `StatusMap`、`EnumOption`。

## 作用

按值从 `map` 或 `options` 取 `label`/`type`，渲染 `el-tag`。未命中用 `fallback`，再否则 `String(value)` 或 `'—'`。`type` 默认 `'info'`。

```ts
import GaStatusTag from '@/components/ga-status-tag/index.vue'
```

用法：[web/src/views/system/auth/admin/index.vue](../../../../../web/src/views/system/auth/admin/index.vue) + [constants.ts](../../../../../web/src/views/system/auth/admin/constants.ts)。

## Props / 事件 / 插槽

| 属性 | 类型 | 说明 |
|---|---|---|
| `value` | `string \| number \| null \| undefined` | 状态值 |
| `map` | `StatusMap` | `{ [value]: { label, type? } }`（手写页） |
| `options` | `EnumOption[]` | `{ value, label, type? }[]`（生成页 constants） |
| `fallback` | `string` | 未命中时的文案 |

`type` 取值：`primary` / `success` / `warning` / `info` / `danger`。无事件、无插槽。

## 最小示例

```vue
<ga-status-tag :value="row.status" :map="{ 1: { label: '正常', type: 'success' }, 0: { label: '禁用', type: 'info' } }" />
```

## 逃生舱

```vue
<el-tag :type="row.status === 1 ? 'success' : 'info'">
  {{ row.status === 1 ? '正常' : '禁用' }}
</el-tag>
```

见 [escape-hatches.md](../../../../escape-hatches.md)。
