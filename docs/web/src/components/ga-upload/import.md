# ga-upload/import

源码：[web/src/components/ga-upload/import.vue](../../../../../web/src/components/ga-upload/import.vue)

## 作用

导入对话框：`el-dialog` + 拖拽 `el-upload`（`auto-upload=false`，`limit=1`）。提交用 `httpRequest` 或 `post(url, FormData)`（`silent: true`，带上传进度）。仓库业务页暂无引用。

```ts
import GaUploadImport from '@/components/ga-upload/import.vue'
```

图片上传见 [image.md](image.md)。

## Props / 事件 / 插槽

| 绑定 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `v-model` | `boolean` | `false` | 对话框显隐 |

| 属性 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `title` | `string` | `'文件导入'` | 标题 |
| `templateUrl` | `string` | `''` | 模板下载，空则不展示 |
| `accept` | `string` | `'.xlsx,.xls'` | 扩展名或 MIME |
| `maxSize` | `number` | `10 * 1024 * 1024` | 最大字节，`0` 不限制 |
| `tip` | `string` | 空则按 accept/maxSize 生成 | 底部提示 |
| `url` | `string` | — | 导入接口，如 `ass.batch.Range/import` |
| `httpRequest` | `(file, onProgress?) => Promise` | — | 优先于 `url` |
| `data` | `Record<string, string \| number>` | — | 写入 FormData（仅 url 模式） |
| `closeOnSuccess` | `boolean` | `true` | 成功后关闭 |
| `successMsg` | `string` | `'导入成功'` | 无接口文案时的兜底 |

须配置 `url` 或 `httpRequest` 之一。成功文案优先 `data.msg`，否则 `created`/`skipped`。

| 事件 | 载荷 |
|---|---|
| `success` | 接口返回 `data` |

无插槽。

## 最小示例

```vue
<script setup lang="ts">
import GaUploadImport from '@/components/ga-upload/import.vue'
const open = ref(false)
</script>
<template>
  <el-button @click="open = true">导入</el-button>
  <ga-upload-import v-model="open" url="admin/example.Item/import" @success="load" />
</template>
```

## 逃生舱

`el-dialog` + `el-upload` `drag` + `post(url, formData, { onUploadProgress })`。见 [escape-hatches.md](../../../../escape-hatches.md)。
