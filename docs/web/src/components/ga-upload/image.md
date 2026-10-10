# ga-upload/image

源码：[web/src/components/ga-upload/image.vue](../../../../../web/src/components/ga-upload/image.vue)

## 作用

图片上传：`el-upload` + 自定义 `httpRequest`（父组件负责真正请求，返回存储 path）。`v-model` 为相对 path，展示经 `resolveStorageUrl`。校验 `accept` 与 `maxSize`。

```ts
import GaUploadImage from '@/components/ga-upload/image.vue'
```

导入对话框见 [import.md](import.md)。

## 推荐使用场景

- 单图（头像等）：`v-model` 存相对 path，父组件提供 `httpRequest`。
- 多文件、拖拽导入 Excel：用 [import.md](import.md) 或裸 `el-upload`。
- 上传逻辑要完全自定义（裁剪、多图列表）：直接 `el-upload`。

## Props / 事件 / 插槽

| 绑定 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `v-model` | `string \| null` | `null` | 存储路径 |

| 属性 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `httpRequest` | `(file, onProgress?) => Promise<string>` | **必填** | 上传并返回 path；`onProgress` 为 0–100 |
| `accept` | `string` | `'image/jpeg,image/png,image/gif,image/webp'` | 传给 `el-upload` |
| `maxSize` | `number` | `2 * 1024 * 1024` | 最大字节，`0` 不限制 |
| `tip` | `string` | `''` | 底部提示 |
| `disabled` | `boolean` | `false` | 禁用 |
| `maxSizeHintUrl` | `string` | `''` | 超限时压缩工具链接 |

| 事件 | 载荷 |
|---|---|
| `success` | `path: string` |

| 插槽 | 作用域 |
|---|---|
| `trigger` | `{ url, uploading, percent, disabled }` |
| `tip` | 覆盖底部提示 |

## 最小示例

```vue
<script setup lang="ts">
import GaUploadImage from '@/components/ga-upload/image.vue'
import { post } from '@/apis/request'

const path = ref<string | null>(null)
const httpRequest = async (file: File, onProgress?: (p: number) => void) => {
  const fd = new FormData()
  fd.append('file', file)
  const res = await post<{ avatar: string }>('admin/system.account.Profile/uploadAvatar', fd, {
    onUploadProgress: (e) => {
      if (e.total) onProgress?.(Math.round((e.loaded / e.total) * 100))
    },
  })
  return res.avatar
}
</script>
<template>
  <ga-upload-image v-model="path" :http-request="httpRequest" tip="最大 2MB" />
</template>
```

## 已使用场景

- [web/src/views/system/account/profile.vue](../../../../../web/src/views/system/account/profile.vue)（头像，自定义 `#trigger`）

## 逃生舱

`el-upload` + `:http-request` 自行 `post(FormData)`。见 [escape-hatches.md](../../../../escape-hatches.md)。
