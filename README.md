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

PHP ≥ 8.3（`openssl`、`pdo_mysql`，见 [composer.json](composer.json)）。前端在 `web/`，包管理器为 pnpm（[web/pnpm-lock.yaml](web/pnpm-lock.yaml)）。HTTP 入口 [public/index.php](public/index.php)，站点根目录指向 `public/`。`VITE_API_BASE_URL` 为站点入口，**不含** `/admin`（[web/README.md](web/README.md)）。

```bash
composer install
cp .example.env .env          # 填写 DB_* 等；安装向导也会写入
# 库表：database/install/schema.sql 与 data.sql
# 未安装时访问入口会进入 /install 向导（执行上述 SQL）
cd web && pnpm install && pnpm dev
```

生产构建：`cd web && pnpm build`（[web/package.json](web/package.json)）。

## 文档

- [文档索引](docs/README.md)（含源码树）
- [约定](docs/principles.md)
- [可选封装](docs/escape-hatches.md)
- 前端：[request.ts](docs/web/src/apis/request.md)、[ga-table](docs/web/src/components/ga-table/index.md)、[ga-page](docs/web/src/components/ga-page/index.md)（其余 `ga-*` 见 [文档索引](docs/README.md)）
- 后端：[ApiResponse](docs/app/common/ApiResponse.md)、[AdminController](docs/app/common/controller/AdminController.md)、[登录](docs/app/admin/controller/system/account/login/BaseLogin.md)

## 许可证

Apache-2.0，见 [LICENSE](LICENSE)。
