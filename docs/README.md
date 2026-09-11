# 文档索引

本目录是透明度计划的第一刀：原则可核对、样例组件/请求有文档和逃生舱。不是完整手册。

## 原则与逃生舱

- [三条硬规则](principles.md) — 薄封装、文档随组件走、逃生舱优先
- [如何绕过框架](escape-hatches.md) — 前端 `el-*` / `fetch`，后端普通 ThinkPHP 控制器

## 前端（已覆盖）

- [请求层 request.ts](frontend/request.md)
- [ga-table](frontend/components/ga-table.md)
- [ga-page](frontend/components/ga-page.md)

## 尚未按标准写文档的 `ga-*`

源码仍在 [`web/src/components/`](../web/src/components/)：

- `ga-delete-confirm`
- `ga-icon`（含 picker）
- `ga-monaco-editor`
- `ga-password-input`
- `ga-search-bar`
- `ga-select-remote`
- `ga-status-tag`
- `ga-upload`（`image.vue` / `import.vue`）

缺文档不等于不能用：打开对应 `index.vue` 即可；需要 Element Plus 能力时直接用 `el-*`。
