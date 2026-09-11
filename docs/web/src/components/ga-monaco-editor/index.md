# ga-monaco-editor

源码：[web/src/components/ga-monaco-editor/index.vue](../../../../../web/src/components/ga-monaco-editor/index.vue)、[types.ts](../../../../../web/src/components/ga-monaco-editor/types.ts)

## 作用

对 `monaco-editor` 的单实例封装：`v-model` 同步文本；卸载时 `dispose`。仓库内暂无启用引用（[404.vue](../../../../../web/src/layouts/components/error/404.vue) 中为注释）。

```ts
import GaMonacoEditor from '@/components/ga-monaco-editor/index.vue'
```

暴露 `getInstance()` 返回底层 `IStandaloneCodeEditor`。

## Props / 事件 / 插槽

| 绑定 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `v-model` | `string` | **必填** | 编辑器文本 |

| 属性 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `initOptions` | `IStandaloneEditorConstructionOptions` | `{}` | 创建时一次性注入 |
| `options` | `IEditorOptions & IGlobalEditorOptions` | `{}` | 运行期 `updateOptions` |
| `language` | `string` | — | 语言模式 |
| `theme` | `string` | `'vs-dark'` | `vs` / `vs-dark` / `hc-black` |
| `height` | `string` | `'200px'` | 容器高度 |

无自定义事件、无插槽。`theme` 走 `monaco.editor.setTheme`（全局）。

## 最小示例

```vue
<script setup lang="ts">
import GaMonacoEditor from '@/components/ga-monaco-editor/index.vue'
const code = ref('{\n  "a": 1\n}')
</script>
<template>
  <ga-monaco-editor v-model="code" language="json" height="240px" />
</template>
```

## 逃生舱

直接 `monaco.editor.create`，或 `el-input type="textarea"`。见 [escape-hatches.md](../../../../escape-hatches.md)。
