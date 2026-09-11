# ga-page

源码：[web/src/components/ga-page/index.vue](../../../../../web/src/components/ga-page/index.vue)

## 作用

页面外壳：内边距 + `el-card`。不发请求、不处理路由。卡片行为见 [Element Plus Card](https://element-plus.org/zh-CN/component/card.html)。

```ts
import GaPage from '@/components/ga-page/index.vue'
```

`minHeight` 非空时加 `ga-page--stretch`，供内部 [ga-table](../ga-table/index.md) 的 `fill` 分配高度。未透传 `el-card` 的 `header-class` 等。

## 推荐使用场景

- 整页需要统一内边距 + `el-card` 外壳。
- 内部放 `ga-table` 且要 `fill` 撑满时，设 `minHeight`。
- 弹窗/抽屉里的内容、或页面已有自己的 `el-card` 布局：用 `div` / 裸 `el-card`。

## Props / 事件 / 插槽

| 属性 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `title` | `string` | — | 无 `header` 插槽时作为页头文本 |
| `shadow` | `'always' \| 'hover' \| 'never'` | `'never'` | `el-card` 的 `shadow` |
| `padding` | `'none' \| 'sm' \| 'md' \| 'lg'` | `'md'` | 外层 padding；`none` 时卡片 `body-style` 为 `padding:0` |
| `minHeight` | `string` | `''` | CSS `min-height`；非空启用 stretch |

事件：无。

| 插槽 | 用途 |
|---|---|
| 默认 | `el-card` 内容 |
| `header` | 覆盖页头；有此插槽或 `title` 时渲染 `#header` |

## 最小示例

[web/src/views/system/attachment/index.vue](../../../../../web/src/views/system/attachment/index.vue)：

```vue
<script setup lang="ts">
import GaPage from '@/components/ga-page/index.vue'
</script>
<template>
  <ga-page padding="sm" min-height="max(calc(100vh - 100px), 400px)">
    <!-- 列表、表单 -->
  </ga-page>
</template>
```

## 已使用场景

- [web/src/views/system/auth/admin/index.vue](../../../../../web/src/views/system/auth/admin/index.vue)（整页；`embedded` 时换成 `div`）
- [web/src/views/system/attachment/index.vue](../../../../../web/src/views/system/attachment/index.vue)
- [web/src/views/system/account/profile.vue](../../../../../web/src/views/system/account/profile.vue)（操作日志卡片）
- [web/src/views/dashboard/server.vue](../../../../../web/src/views/dashboard/server.vue)、[usage.vue](../../../../../web/src/views/dashboard/usage.vue)
- CRUD 生成模板：[extend/crud/templates/index/index.vue.tpl](../../../../../extend/crud/templates/index/index.vue.tpl)

对照未使用：[web/src/views/system/config/index.vue](../../../../../web/src/views/system/config/index.vue)。

## 逃生舱

`div` + `el-card`，或弹窗内不用本组件（[admin/index.vue](../../../../../web/src/views/system/auth/admin/index.vue) `shellIs`）。对照 [config/index.vue](../../../../../web/src/views/system/config/index.vue)。见 [escape-hatches.md](../../../../escape-hatches.md)。
