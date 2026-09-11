# 三条硬规则

GlassAdmin 承诺透明：自定义层只组合约定，不发明第二套语法。下面三条是硬规则，后续文档与代码都应能对上。

## 1. 薄封装（Thin wrap）

自定义层只组合约定，不隐藏 Element Plus / ThinkPHP 的行为。源码应能映射到官方文档。

- 前端 `ga-*` 是 `el-*` 的布局与默认值组合，不是另一套组件模型。例如 `ga-table` 把 `el-table` 和 `el-pagination` 排在一起，列仍用 `el-table-column`，行为以 [Element Plus Table](https://element-plus.org/zh-CN/component/table.html) 为准。
- 后端管理端控制器是 ThinkPHP 8 多应用下的普通类：`app/admin/controller/` 里的 action 对应 URL，JSON 外壳在 `app/common/ApiResponse.php`，鉴权在 `app/common/controller/AdminController.php`。请求/响应仍按 [ThinkPHP 8 手册](https://doc.thinkphp.cn/v8_0/index.html) 理解。
- 读不懂封装时，打开源文件对照官方文档，而不是去记框架专有语法。

## 2. 文档随组件走（Docs co-shipped）

每个 `ga-*` 组件在没有「最小示例 + props/events + 何时直接用 Element Plus」之前，都算文档不完整。

当前已有样例文档：

- [`docs/frontend/components/ga-table.md`](frontend/components/ga-table.md)
- [`docs/frontend/components/ga-page.md`](frontend/components/ga-page.md)

其余 `ga-*`（`ga-delete-confirm`、`ga-icon`、`ga-monaco-editor`、`ga-password-input`、`ga-search-bar`、`ga-select-remote`、`ga-status-tag`、`ga-upload`）尚未按此标准写文档，不声称「全部组件都有文档」。

请求层同样需要可核验说明，见 [`docs/frontend/request.md`](frontend/request.md)。

## 3. 逃生舱优先（Escape-hatch first）

任何页面都可以跳过 `ga-*`，直接使用 `el-*`、原生 `fetch` / `request`，或普通 ThinkPHP 控制器。框架不得强迫自己。

仓库里已经有这样的页面与接口：

- 系统配置页 [`web/src/views/system/config/index.vue`](../web/src/views/system/config/index.vue) 用 `el-card` + `el-table`，不用 `ga-page` / `ga-table`。
- Token 续期 [`web/src/pinia/admin/token.ts`](../web/src/pinia/admin/token.ts) 用原生 `fetch`，不走 axios 拦截器。
- RSA 公钥 [`app/admin/controller/system/Security.php`](../app/admin/controller/system/Security.php) 继承 `BaseController`，不走管理端登录/RBAC 基类。

具体绕过方式见 [`docs/escape-hatches.md`](escape-hatches.md)。
