# ga-icon/picker

源码：[web/src/components/ga-icon/picker.vue](../../../../../web/src/components/ga-icon/picker.vue)

## 作用

`el-popover` + `el-tabs`：从 Element Plus 图标、本地 svg、图片 URL 中选择，写入 `v-model` 存储串（`el:` / `local:` / `url:`）。展示用内部 [ga-icon](index.md)。

```ts
import GaIconPicker from '@/components/ga-icon/picker.vue'
```

## 推荐使用场景

- 表单要写入 `el:` / `local:` / `url:` 存储串（与 [ga-icon](index.md) 同一套格式）。
- 只需从 Element Plus 图标名里选一个：`el-select` 即可。

## Props / 事件 / 插槽

| 绑定 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `v-model` | `string \| null` | `null` | 图标存储串 |

| 属性 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `hideName` | `boolean` | `false` | 隐藏触发按钮上的图标名文本 |

无自定义事件、无插槽。

## 最小示例

```vue
<script setup lang="ts">
import GaIconPicker from '@/components/ga-icon/picker.vue'
const icon = ref<string | null>('el:User')
</script>
<template>
  <ga-icon-picker v-model="icon" />
</template>
```

## 已使用场景

- [web/src/views/system/auth/rule/edit.vue](../../../../../web/src/views/system/auth/rule/edit.vue)
- [web/src/views/dev/curd/components/GlobalConfigPanel.vue](../../../../../web/src/views/dev/curd/components/GlobalConfigPanel.vue)

## 逃生舱

用 `el-select` 列出 `@element-plus/icons-vue` 名称，或只存组件名、页面里直接 `<el-icon>`。见 [escape-hatches.md](../../../../escape-hatches.md)。
