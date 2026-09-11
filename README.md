# GlassAdmin

Vue 3 + ThinkPHP 8 + Element Plus + TypeScript 后台框架。封装为可选约定，不另设一套语法。

## 理念

- 薄封装：`ga-*` 与控制器只组合约定，行为以 Vue / ThinkPHP / Element Plus 官方文档为准。[约定](docs/principles.md)
- 可绕过：页面可用 `el-*` / `fetch`；接口可不继承 `AdminController`。[可选封装](docs/escape-hatches.md)
- 可核对：文档链到本仓库源码；组件文档清单见 [源码目录](docs/structure.md#模块)

## 快速入门

- PHP ≥ 8.3，扩展 `openssl`、`pdo_mysql`（[composer.json](composer.json)）。`composer install`。
- 后端 `.env`：模板 [.example.env](.example.env)（`DB_*` 等）。安装向导也会写入。
- 库表：[database/install/schema.sql](database/install/schema.sql)、[data.sql](database/install/data.sql)。未安装时入口重定向 `/install`，向导执行上述 SQL。
- HTTP 入口：[public/index.php](public/index.php)；站点根目录指向 `public/`。
- 前端 `web/`，pnpm（[web/pnpm-lock.yaml](web/pnpm-lock.yaml)）：`pnpm install`，`pnpm dev`，`pnpm build`（[web/package.json](web/package.json)）。
- `VITE_API_BASE_URL` 为站点入口，**不含** `/admin`。[web/README.md](web/README.md)。

## 文档

- [文档索引](docs/README.md)
- [源码目录](docs/structure.md)
- [约定](docs/principles.md)
- [可选封装](docs/escape-hatches.md)
- 前端：[request.ts](docs/frontend/request.md)、[ga-table](docs/frontend/components/ga-table.md)、[ga-page](docs/frontend/components/ga-page.md)
- 后端：[JSON 信封](docs/backend/api-response.md)、[登录 / RBAC](docs/backend/auth.md)

## 许可证

Apache-2.0，见 [LICENSE](LICENSE)。
