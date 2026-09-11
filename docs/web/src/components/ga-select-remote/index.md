# ga-select-remote

源码：[web/src/components/ga-select-remote/index.vue](../../../../../web/src/components/ga-select-remote/index.vue)

## 作用

远程 `el-select`：`filterable` + `remote`，默认 `get`/`post` 拉选项。响应适配数组或 `{list,total}` / `{data,total}`。可分页（`total > limit` 时 footer `el-pagination`）。`inheritAttrs: false`，其余属性 `v-bind="$attrs"` 传给 `el-select`。仓库业务页暂无引用（供 CRUD 生成等）。

```ts
import GaSelectRemote from '@/components/ga-select-remote/index.vue'
```

暴露 `refreshOptions`、`cleanQuery`。

## Props / 事件 / 插槽

| 绑定 | 类型 | 说明 |
|---|---|---|
| `v-model` | `unknown` | 选中值 |

| 属性 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `remoteUrl` | `string` | **必填** | 请求地址 |
| `props` | `SelectOptionProps` | — | 选项 `value`/`label` 字段，默认 `value`/`label` |
| `method` | `'GET' \| 'POST'` | `'GET'` | 无 `requestFn` 时使用 |
| `transform` | `(response) => { list, total }` | — | 自定义转换 |
| `limit` | `number` | `10` | 每页条数 |
| `filters` | `object` | `{}` | 合并到请求参数（GET 不可嵌套 object） |
| `requestFn` | `(url, params) => Promise` | — | 替代默认 get/post |
| `initOptions` | `object \| object[]` | — | 回显初始选项，跳过首次请求 |

请求参数含 `page`、`limit`；搜索时 `keyword`；有 `v-model` 且无 `initOptions` 时首次带 `init_values`。

无自定义 emit。除 `default`/`header`/`footer` 外的插槽透传给 `el-select`。内部占用 `header`（当前搜索标签）、`footer`（分页）。

## 最小示例

```vue
<script setup lang="ts">
import GaSelectRemote from '@/components/ga-select-remote/index.vue'
const id = ref<number>()
</script>
<template>
  <ga-select-remote
    v-model="id"
    remote-url="admin/system.auth.Admin/list"
    :props="{ value: 'id', label: 'username' }"
    :limit="20"
  />
</template>
```

## 逃生舱

`el-select` + `remote-method` + `get(url, { params })`。见 [escape-hatches.md](../../../../escape-hatches.md)、[request.md](../../apis/request.md)。
