# 约定

封装只组合已有技术，不另设语法。

## 1. 薄封装（Thin wrap）

自定义层不隐藏 Element Plus / ThinkPHP 行为。源码对应官方文档。

- `ga-*` 是 `el-*` 的布局与默认值。`ga-table` 排列 `el-table` 与 `el-pagination`，列仍为 `el-table-column`。[Element Plus Table](https://element-plus.org/zh-CN/component/table.html)。
- 管理端控制器位于 `app/admin/controller/`。JSON 外壳：[app/common/ApiResponse.php](../app/common/ApiResponse.php)。鉴权：[app/common/controller/AdminController.php](../app/common/controller/AdminController.php)。[ThinkPHP 8](https://doc.thinkphp.cn/v8_0/index.html)。

## 2. 文档随组件走（Docs co-shipped）

`ga-*` 文档包含：作用、推荐使用场景、props/events/插槽、最小示例、已使用场景、逃生舱。路径与 `web/src/components/` 对齐。清单见 [README.md](README.md#源码目录)。

## 3. 可选封装（Escape-hatch first）

页面可不使用 `ga-*`，改用 `el-*`、`fetch` 或 `get`/`post`。控制器可不继承 `AdminController`。写法：[escape-hatches.md](escape-hatches.md)。
