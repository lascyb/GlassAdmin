# GlassAdmin - 透明可视的后台框架

> 如果你厌倦了魔法般的封装，想要看清每一行代码的执行，GlassAdmin 是你的不二之选。
>
> 学习 GlassAdmin，你真正学会的是 Vue 3 和 ThinkPHP 8，而不是某个框架的特定语法。
>
> GlassAdmin 是一个基于 Vue 3 + ThinkPHP 8 + Element Plus + TypeScript 的现代化后台框架，专为追求代码透明度与控制权的开发者设计。

## 理念

- **技术栈原生体验**：ThinkPHP 8、Vue 3、Element Plus、TypeScript。封装不另设语法，行为以官方文档为准。[约定](docs/principles.md)
- **渐进式学习**：先学原生，再碰框架约定。`ga-*` 文档见 [文档索引](docs/README.md)（与 `web/src/components/` 同路径）。
- **逃生舱**：随时可以绕过框架，直接用 `el-*`、原生 `fetch` 或普通 ThinkPHP 控制器。[可选封装](docs/escape-hatches.md)
- **没有黑魔法，只有好代码。** 源码与文档可对照：[文档索引](docs/README.md#源码目录)

## 快速入门

PHP ≥ 8.3（`openssl`、`pdo_mysql`，见 [composer.json](composer.json)）。前端在 `web/`，包管理器为 pnpm（[web/pnpm-lock.yaml](web/pnpm-lock.yaml)）。

站点根目录必须指向 [public/](public/)（入口 [public/index.php](public/index.php)）。`topthink/framework` `^8.0` 自带 `php think run`（`RunServer`：默认 `0.0.0.0:8000`，文档根为项目 `public/`，路由脚本是仓库里的 [public/router.php](public/router.php)）。这是开发用内置服务器；生产用你自己的 Web 服务器，文档根同样指向 `public/`。

`VITE_API_BASE_URL` 必须是该 PHP 源站（协议 + 主机 + 端口），**不含** `/admin`（[request.ts](web/src/apis/request.ts)、[web/README.md](web/README.md)）。接口路径自己带 `admin/`。

```bash
composer install
cp .example.env .env
php think run                 # 浏览器打开 http://127.0.0.1:8000
# 另开终端
cd web && pnpm install && pnpm dev
```

未安装且 `app/install` 仍在时，打开源站根路径会进向导：默认应用是 `index`，[Index::index](app/index/controller/Index.php) 执行 `redirect('/install')`。向导跑 [schema.sql](database/install/schema.sql) 与 [data.sql](database/install/data.sql)，并写根 `.env`（`DB_*` 与 `APP_URL`）。同时写 `web/.env.development`（[EnvWriter.php](app/install/service/EnvWriter.php)）：`VITE_API_BASE_URL` 与 `APP_URL` 相同，都是当前请求的 `domain()`（协议 + 主机 + 端口），不含 `/admin`。`domain()` 不含 URL 路径，反代前缀（如 `/backend`）写在 `web/.env.production`。没跑向导就自己建 `web/.env.development`。

生产构建：`cd web && pnpm build`（[web/package.json](web/package.json)）。

## 文档

- [文档索引](docs/README.md)（含源码树）
- [约定](docs/principles.md)
- [可选封装](docs/escape-hatches.md)
- 前端：[request.ts](docs/web/src/apis/request.md)、[ga-table](docs/web/src/components/ga-table/index.md)、[ga-page](docs/web/src/components/ga-page/index.md)、[usePaginatedList](docs/web/src/composables/usePaginatedList.md)、[useTreeCrud](docs/web/src/composables/useTreeCrud.md)（其余 `ga-*` 见 [文档索引](docs/README.md)）
- 后端：[ApiResponse](docs/app/common/ApiResponse.md)、[AdminController](docs/app/common/controller/AdminController.md)、[登录](docs/app/admin/controller/system/account/login/BaseLogin.md)

## 许可证

Apache-2.0，见 [LICENSE](LICENSE)。
