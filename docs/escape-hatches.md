# 可选封装

页面与接口可不使用 `get`/`post`、`ga-*`、`AdminController`。约定：[principles.md](principles.md)。各封装的短对照见对应描述文档「逃生舱」。

`ga-*` 须显式 import（[web/vite.config.ts](../web/vite.config.ts) `dirs: []`）。`el-*` 仍按需注册。

| 场景 | 推荐写法 | 对照 |
|---|---|---|
| 页面不用 `ga-page` / `ga-table` | `el-card` / `el-table`；不 import `ga-*` | [config/index.vue](../web/src/views/system/config/index.vue)、[ga-page](web/src/components/ga-page/index.md)、[ga-table](web/src/components/ga-table/index.md) |
| 只要表、不要分页壳 | `el-table` | [usage.vue](../web/src/views/dashboard/usage.vue) |
| 嵌入弹窗不用 `ga-page` | 外壳用 `div` | [admin/index.vue](../web/src/views/system/auth/admin/index.vue)（`shellIs`） |
| 删除确认不用 `ga-delete-confirm` | `useConfirmAction` / `ElMessageBox` | [ga-delete-confirm](web/src/components/ga-delete-confirm/index.md) |
| 图标不用 `ga-icon` | `el-icon` / `<img>` | [ga-icon](web/src/components/ga-icon/index.md) |
| 远程下拉不用 `ga-select-remote` | `el-select` + `remote-method` | [ga-select-remote](web/src/components/ga-select-remote/index.md) |
| 列表请求 | `get` / `post`（axios 同签名，默认解包） | [request.md](web/src/apis/request.md) |
| 不解包信封 | `http` + `skipUnwrap: true` | [request.md](web/src/apis/request.md) |
| 不用 axios 封装 | 原生 `fetch` | [token.ts](../web/src/pinia/admin/token.ts)、[token.md](web/src/pinia/admin/token.md) |
| 按钮权限不用 `v-auth` | `v-if` + `hasPermission`，或自行不渲染 | [auth.md](web/src/directives/auth.md) |
| 不用布局壳 | 路由不挂 `layouts/index.vue` | [layouts/index.md](web/src/layouts/index.md) |
| 公开接口、无登录/RBAC | 继承 `BaseController` | [Security.md](app/admin/controller/system/Security.md) |
| 登录/刷新 | 继承 `BaseLogin`（`BaseController`） | [BaseLogin.md](app/admin/controller/system/account/login/BaseLogin.md) |
| 需登录、跳过 RBAC | `$loginOnlyActions` | [AdminController.md](app/common/controller/AdminController.md)、[Config.md](app/admin/controller/system/Config.md) |
| 不用 `ok()` / `fail()` | `return json([...])` | [ApiResponse.md](app/common/ApiResponse.md) |

路径 `/admin/{点分控制器}/{action}`（[config/app.php](../config/app.php) `with_route=false`）。不使用封装不等于绕过 `system_auth_rule` 已配置的权限。
