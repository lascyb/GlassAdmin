# ga-status-tag

源码：[web/src/components/ga-status-tag/index.vue](../../../../../web/src/components/ga-status-tag/index.vue)  
类型：[web/src/composables/types.ts](../../../../../web/src/composables/types.ts) 的 `StatusMap`、`EnumOption`。

## 作用

按值从 `map` 或 `options` 取 `label`/`type`，渲染 `el-tag`。未命中用 `fallback`，再否则 `String(value)` 或 `'—'`。`type` 默认 `'info'`。

```ts
import GaStatusTag from '@/components/ga-status-tag/index.vue'
```

## 推荐使用场景

- 列表列按 `map` 或 `options` 显示状态（`label` + `el-tag` 的 `type`）。
- 只有一两处三元判断：直接 `el-tag`。

## Props / 事件 / 插槽

| 属性 | 类型 | 说明 |
|---|---|---|
| `value` | `string \| number \| null \| undefined` | **必填**。状态值（`null` / `undefined` 表示空） |
| `map` | `StatusMap` | `{ [value]: { label, type? } }`（手写页） |
| `options` | `EnumOption[]` | `{ value, label, type? }[]`（生成页 constants） |
| `fallback` | `string` | 未命中时的文案 |

`type` 取值：`primary` / `success` / `warning` / `info` / `danger`。无事件、无插槽。

## 最小示例

```vue
<ga-status-tag :value="row.status" :map="{ 1: { label: '正常', type: 'success' }, 0: { label: '禁用', type: 'info' } }" />
```

## 已使用场景

- [web/src/views/system/auth/admin/index.vue](../../../../../web/src/views/system/auth/admin/index.vue) + [constants.ts](../../../../../web/src/views/system/auth/admin/constants.ts) 的 `ADMIN_STATUS_MAP`
- CRUD 生成模板：[extend/crud/templates/index/col/enum_tag.vue.tpl](../../../../../extend/crud/templates/index/col/enum_tag.vue.tpl)（[IndexFile.php](../../../../../extend/crud/generator/IndexFile.php) 按需 import）

## 逃生舱

`el-tag` 自行映射 label/type。见 [escape-hatches.md](../../../../escape-hatches.md)。
