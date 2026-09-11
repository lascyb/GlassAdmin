# 约定

封装只组合已有技术的约定，不另设语法。下列三条适用于本仓库文档与代码。

## 1. 薄封装（Thin wrap）

自定义层组合约定，不隐藏 Element Plus / ThinkPHP 行为。源码对应官方文档。

- 前端 `ga-*` 是 `el-*` 的布局与默认值组合。`ga-table` 排列 `el-table` 与 `el-pagination`，列仍为 `el-table-column`。表格行为见 [Element Plus Table](https://element-plus.org/zh-CN/component/table.html)。
- 管理端控制器为 ThinkPHP 8 多应用普通类，位于 `app/admin/controller/`。JSON 外壳：[`app/common/ApiResponse.php`](../app/common/ApiResponse.php)。鉴权：[`app/common/controller/AdminController.php`](../app/common/controller/AdminController.php)。请求与响应见 [ThinkPHP 8 手册](https://doc.thinkphp.cn/v8_0/index.html)。

## 2. 文档随组件走（Docs co-shipped）

`ga-*` 文档需包含：最小示例、props/events、改用 Element Plus 的写法。缺任一则视为未完成。

已写：

- [`docs/frontend/components/ga-table.md`](frontend/components/ga-table.md)
- [`docs/frontend/components/ga-page.md`](frontend/components/ga-page.md)

未写：`ga-delete-confirm`、`ga-icon`、`ga-monaco-editor`、`ga-password-input`、`ga-search-bar`、`ga-select-remote`、`ga-status-tag`、`ga-upload`。

请求层：[docs/frontend/request.md](frontend/request.md)。

## 3. 可选封装（Escape-hatch first）

页面可不使用 `ga-*`，改用 `el-*`、原生 `fetch` / `get`/`post`，或普通 ThinkPHP 控制器。

仓库内已有实现：

- [`web/src/views/system/config/index.vue`](../web/src/views/system/config/index.vue)：`el-card` + `el-table`
- [`web/src/pinia/admin/token.ts`](../web/src/pinia/admin/token.ts)：原生 `fetch` 续期
- [`app/admin/controller/system/Security.php`](../app/admin/controller/system/Security.php)：继承 `BaseController`，无登录/RBAC

写法：[docs/escape-hatches.md](escape-hatches.md)。
