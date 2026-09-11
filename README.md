# GlassAdmin

基于 **Vue 3 + ThinkPHP 8 + Element Plus + TypeScript** 的后台框架，目标是代码可核对、封装可绕过，而不是再学一套框架语法。

> 没有黑魔法，只有能对照源码和官方文档的薄封装。  
> 学 GlassAdmin，优先学的是 Vue 3 和 ThinkPHP 8。

## 承诺与证据

每条产品承诺都指向仓库里的文件。尚未做到的会写明范围，而不是用「全部」带过。

| 承诺 | 证据 |
|---|---|
| 薄封装：自定义层组合约定，不隐藏 Element Plus / ThinkPHP | [docs/principles.md](docs/principles.md)（规则 1）；`ga-table` 源码 [web/src/components/ga-table/index.vue](web/src/components/ga-table/index.vue) 内嵌的是 `el-table` + `el-pagination` |
| 源码能映射到官方文档 | 组件文档写明对应 EP 手册；后端是 TP8 多应用控制器 [app/admin/controller/](app/admin/controller/) + JSON 外壳 [app/common/ApiResponse.php](app/common/ApiResponse.php) |
| 逃生舱：任何页面可跳过 `ga-*`，用 `el-*`、原生 `fetch` 或普通 TP 控制器 | [docs/escape-hatches.md](docs/escape-hatches.md)；配置页已不用 `ga-page`/`ga-table`：[web/src/views/system/config/index.vue](web/src/views/system/config/index.vue)；Token 续期用 `fetch`：[web/src/pinia/admin/token.ts](web/src/pinia/admin/token.ts) |
| 请求层可核对、可绕过 | [docs/frontend/request.md](docs/frontend/request.md) ← [web/src/apis/request.ts](web/src/apis/request.ts) |
| 样例组件文档（最小示例 + props/events + 何时用 EP） | [ga-table](docs/frontend/components/ga-table.md)、[ga-page](docs/frontend/components/ga-page.md) |

**尚未声称的：** 并非所有 `ga-*` 都有文档。已覆盖 `ga-table`、`ga-page` 与请求层；其余见 [docs/README.md](docs/README.md) 清单（`ga-search-bar`、`ga-upload`、`ga-icon` 等）。缺文档的组件仍可直接读源码或改用 `el-*`。

原则全文：[docs/principles.md](docs/principles.md)。文档索引：[docs/README.md](docs/README.md)。

## 技术栈

| 层 | 选型 | 本仓库位置 |
|---|---|---|
| 前端 | Vue 3、TypeScript、Vite、Element Plus、Pinia、axios | [web/](web/)（依赖见 [web/package.json](web/package.json)） |
| 后端 | PHP ≥ 8.3、ThinkPHP 8 多应用、think-orm | [app/](app/)、[composer.json](composer.json)（`topthink/framework ^8.0`） |
| 管理端 API | 应用名 `admin`，控制器在 `app/admin/controller/` | 例：[app/admin/controller/system/auth/Admin.php](app/admin/controller/system/auth/Admin.php) |
| UI | Element Plus；`ga-*` 为可选薄封装 | [web/src/components/](web/src/components/)；`el-*` 由 Vite 按需注册，`ga-*` 须显式 import（[web/vite.config.ts](web/vite.config.ts)） |

前端接口基址 `VITE_API_BASE_URL` **不含** `/admin`，调用路径自带应用前缀，如 `admin/system.auth.Admin/list`。说明：[web/README.md](web/README.md)。

## 许可证

Apache-2.0，见 [LICENSE](LICENSE)。
