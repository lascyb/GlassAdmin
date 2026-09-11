# 文档

文档路径与源码对齐：`docs/` + 仓库相对路径，扩展名改为 `.md`（`index.vue` → `index.md`）。项目级说明仍在本目录根。

| 路径 | 作用 |
|---|---|
| [structure.md](structure.md) | 源码树、路径说明、模块表 |
| [principles.md](principles.md) | 薄封装、文档随组件、可选封装 |
| [escape-hatches.md](escape-hatches.md) | 不使用 `ga-*` / `AdminController` / axios 封装 |
| [web/src/apis/request.md](web/src/apis/request.md) | `web/src/apis/request.ts` |
| [web/src/apis/envelope.md](web/src/apis/envelope.md) | `web/src/apis/envelope.ts` |
| [web/src/components/ga-table/index.md](web/src/components/ga-table/index.md) | `ga-table` |
| [web/src/components/ga-page/index.md](web/src/components/ga-page/index.md) | `ga-page` |
| [web/src/components/ga-delete-confirm/index.md](web/src/components/ga-delete-confirm/index.md) | `ga-delete-confirm` |
| [web/src/components/ga-icon/index.md](web/src/components/ga-icon/index.md) | `ga-icon` |
| [web/src/components/ga-icon/picker.md](web/src/components/ga-icon/picker.md) | `ga-icon` 选择器 |
| [web/src/components/ga-monaco-editor/index.md](web/src/components/ga-monaco-editor/index.md) | `ga-monaco-editor` |
| [web/src/components/ga-password-input/index.md](web/src/components/ga-password-input/index.md) | `ga-password-input` |
| [web/src/components/ga-search-bar/index.md](web/src/components/ga-search-bar/index.md) | `ga-search-bar` |
| [web/src/components/ga-select-remote/index.md](web/src/components/ga-select-remote/index.md) | `ga-select-remote` |
| [web/src/components/ga-status-tag/index.md](web/src/components/ga-status-tag/index.md) | `ga-status-tag` |
| [web/src/components/ga-upload/image.md](web/src/components/ga-upload/image.md) | `ga-upload/image` |
| [web/src/components/ga-upload/import.md](web/src/components/ga-upload/import.md) | `ga-upload/import` |
| [app/common/ApiResponse.md](app/common/ApiResponse.md) | JSON 信封 |
| [app/BaseController.md](app/BaseController.md) | 控制器基类 |
| [app/common/controller/AdminController.md](app/common/controller/AdminController.md) | 登录门闩 / RBAC |
| [app/admin/controller/system/account/login/BaseLogin.md](app/admin/controller/system/account/login/BaseLogin.md) | 登录 / 刷新 |
| [app/admin/controller/system/Security.md](app/admin/controller/system/Security.md) | RSA 公钥 |

快速入门见根 [README.md](../README.md)。
