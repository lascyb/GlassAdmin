# GlassAdmin - 透明可视的后台框架

> 如果你厌倦了魔法般的封装，想要看清每一行代码的执行，GlassAdmin 是你的不二之选。
>
> 学习 GlassAdmin，你真正学会的是 Vue 3 和 ThinkPHP 8，而不是某个框架的特定语法。
>
> GlassAdmin 是一个基于 Vue 3 + ThinkPHP 8 + Element Plus + TypeScript 的现代化后台框架，专为追求代码透明度与控制权的开发者设计。

## 理念

- **技术栈原生体验**：ThinkPHP 8、Vue 3、Element Plus、TypeScript。封装不另设语法，行为以官方文档为准。[约定](docs/principles.md)
- **渐进式学习**：先学原生，再碰框架约定。样例文档见 [文档索引](docs/README.md)（并非所有 `ga-*` 都已成文）。
- **逃生舱**：随时可以绕过框架，直接用 `el-*`、原生 `fetch` 或普通 ThinkPHP 控制器。[可选封装](docs/escape-hatches.md)
- **没有黑魔法，只有好代码。** 源码与文档可对照：[源码目录](docs/structure.md)

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
