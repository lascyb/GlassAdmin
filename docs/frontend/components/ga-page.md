# ga-page

源码：[`web/src/components/ga-page/index.vue`](../../../web/src/components/ga-page/index.vue)

页面外壳：内边距 + `el-card`。不发请求、不处理路由、不提供表格。卡片行为见 [Element Plus Card](https://element-plus.org/zh-CN/component/card.html)。

须显式导入（本地组件不自动注册）：

```ts
import GaPage from '@/components/ga-page/index.vue'
```

---

## 结构

```vue
<div class="ga-page" :style="{ padding, minHeight }">
  <el-card :shadow="shadow" :body-style="padding === 'none' ? 'padding:0' : undefined">
    <template v-if="title || $slots.header" #header>
      <slot name="header">{{ title }}</slot>
    </template>
    <slot />
  </el-card>
</div>
```

`minHeight` 非空时添加 `ga-page--stretch`：外层、`.el-card`、`.el-card__body` 为纵向 flex，供内部 `ga-table` 的 `fill` 分配剩余高度。

---

## Props / Events / Slots

### Props

| 属性 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `title` | `string` | — | 无 `header` 插槽时作为 `el-card` 页头文本 |
| `shadow` | `'always' \| 'hover' \| 'never'` | `'never'` | `el-card` 的 `shadow` |
| `padding` | `'none' \| 'sm' \| 'md' \| 'lg'` | `'md'` | 外层 padding：`0` / `--ga-space-2` / `--ga-space-4` / `--ga-space-6`。`none` 时 `el-card` 的 `body-style` 为 `padding:0` |
| `minHeight` | `string` | `''` | CSS `min-height`；非空则启用 stretch |

未透传 `el-card` 的 `header-class`、`body-class` 等。

### Events

无。

### Slots

| 插槽 | 用途 |
|---|---|
| 默认 | `el-card` 内容 |
| `header` | 覆盖页头；存在该插槽或 `title` 时渲染 `#header` |

---

## 示例

附件列表：[web/src/views/system/attachment/index.vue](../../../web/src/views/system/attachment/index.vue)。

```vue
<script setup lang="ts">
import GaPage from '@/components/ga-page/index.vue'
</script>

<template>
  <ga-page padding="sm" min-height="max(calc(100vh - 100px), 400px)">
    <!-- 列表、表单等 -->
  </ga-page>
</template>
```

带标题（个人中心操作日志）：[web/src/views/system/account/profile.vue](../../../web/src/views/system/account/profile.vue)。

```vue
<ga-page shadow="never" padding="none" min-height="480px" title="我的操作日志">
  <!-- ga-table 或其他内容 -->
</ga-page>
```

仪表盘仅使用外壳，内部自行放置 `el-card`：[web/src/views/dashboard/usage.vue](../../../web/src/views/dashboard/usage.vue)（`padding="md"` + `min-height="calc(100vh - 120px)"`）。

管理员列表整页使用 `GaPage`，嵌入弹窗时换为 `div`：[web/src/views/system/auth/admin/index.vue](../../../web/src/views/system/auth/admin/index.vue) 的 `shellIs`。

---

## 改用 Element Plus 的场景

- 不需要卡片与该层 padding。
- 外层已有 `el-card` / `el-dialog`（避免双层卡片）。
- 只需 `el-card` 的 header/body，不需要 stretch / `minHeight`。
- 嵌入其他组件内部（同上 `shellIs`）。

[web/src/views/system/config/index.vue](../../../web/src/views/system/config/index.vue) 未使用 `ga-page`。

---

## 改用 `el-card`

与 [web/src/views/system/config/index.vue](../../../web/src/views/system/config/index.vue) 相同：

```vue
<template>
  <div class="p-3">
    <el-card shadow="never" style="min-height: max(calc(100vh - 100px), 400px)">
      <div class="text-base font-medium">页面标题</div>
      <!-- el-table、表单等 -->
    </el-card>
  </div>
</template>
```

卡片页头使用 `el-card` 的 `#header` 插槽。

可选路径：[docs/escape-hatches.md](../../escape-hatches.md)。
