# GlassAdmin

Vue 3 + ThinkPHP 8 + Element Plus + TypeScript 后台框架。

## 目录

源码树（不含 `vendor/`、`web/node_modules/`、`runtime/`、`dist/`）。

```
GlassAdmin/
├── README.md
├── LICENSE
├── composer.json                 # PHP 依赖
├── think                         # ThinkPHP CLI
├── .example.env                  # 后端环境变量模板
├── config/                       # ThinkPHP 全局配置
│   ├── app.php
│   ├── database.php
│   └── middleware.php
├── app/                          # PHP 多应用
│   ├── BaseController.php
│   ├── middleware.php
│   ├── admin/                    # 管理端应用（URL 前缀 /admin）
│   │   ├── controller/
│   │   ├── model/
│   │   ├── service/
│   │   ├── validate/
│   │   └── command/
│   ├── common/                   # 跨应用公共代码
│   │   ├── ApiResponse.php
│   │   ├── controller/AdminController.php
│   │   └── middleware/
│   ├── index/                    # 默认入口应用
│   ├── install/                  # 安装向导
│   └── api/                      # API 应用
├── database/
│   ├── install/                  # schema.sql、data.sql
│   ├── migrations/
│   ├── phinx/
│   └── *.sql
├── extend/
│   ├── crud/                     # CRUD 生成器
│   └── mcp/
├── public/                       # HTTP 入口
│   ├── index.php
│   ├── static/
│   └── storage/
├── route/
├── view/
├── tools/
├── docs/                         # 框架文档
└── web/                          # Vue 3 管理端
    ├── package.json
    ├── vite.config.ts
    ├── README.md
    └── src/
        ├── main.ts
        ├── apis/
        ├── components/           # ga-* 组件
        ├── views/
        ├── layouts/
        ├── pinia/
        ├── router/
        ├── composables/
        ├── directives/
        └── theme/
```

## 目录说明

| 路径 | 作用 | 文档 |
|---|---|---|
| `app/admin/controller/` | 管理端控制器。URL：`admin/{点分控制器}/{action}` | [auth.md](docs/backend/auth.md) |
| `app/admin/controller/system/auth/Admin.php` | 管理员 CRUD | [request.md](docs/frontend/request.md)、[ga-table.md](docs/frontend/components/ga-table.md) |
| `app/admin/controller/system/account/login/` | 登录、刷新令牌 | [auth.md](docs/backend/auth.md) |
| `app/admin/controller/system/Security.php` | RSA 公钥；继承 `BaseController` | [auth.md](docs/backend/auth.md)、[escape-hatches.md](docs/escape-hatches.md) |
| `app/common/ApiResponse.php` | JSON `{code,msg,data}`；分页 `{list,total,page,limit}` | [api-response.md](docs/backend/api-response.md) |
| `app/BaseController.php` | `ok` / `fail` / `paginate` | [api-response.md](docs/backend/api-response.md) |
| `app/common/controller/AdminController.php` | Bearer 鉴权、RBAC、限流 | [auth.md](docs/backend/auth.md) |
| `app/admin/service/AdminTokenService.php` | opaque access / refresh（Cache） | [auth.md](docs/backend/auth.md) |
| `app/admin/service/AdminRbacService.php` | 接口是否命中角色允许的 backend api | [auth.md](docs/backend/auth.md) |
| `config/app.php` | 多应用；`with_route=false`（PATH_INFO） | [escape-hatches.md](docs/escape-hatches.md) |
| `database/install/` | 安装用 `schema.sql`、`data.sql` | — |
| `public/index.php` | HTTP 入口 | — |
| `web/src/apis/request.ts` | axios：Token、解包信封、RSA、401 刷新 | [request.md](docs/frontend/request.md) |
| `web/src/apis/envelope.ts` | `ApiEnvelope`、`unwrap`、`ApiError` | [request.md](docs/frontend/request.md)、[api-response.md](docs/backend/api-response.md) |
| `web/src/components/ga-table/` | `el-table` + `el-pagination` | [ga-table.md](docs/frontend/components/ga-table.md) |
| `web/src/components/ga-page/` | 内边距 + `el-card` | [ga-page.md](docs/frontend/components/ga-page.md) |
| `web/src/components/` | 其余 `ga-*` | — |
| `web/src/views/` | 页面 | [escape-hatches.md](docs/escape-hatches.md) |
| `web/src/pinia/admin/token.ts` | 登录令牌；续期用 `fetch` | [request.md](docs/frontend/request.md) |
| `web/src/layouts/` | 管理端壳 | — |
| `web/vite.config.ts` | `el-*` 按需注册；`ga-*` 须显式 import | [ga-table.md](docs/frontend/components/ga-table.md) |
| `web/README.md` | `VITE_API_BASE_URL`（不含 `/admin`） | [web/README.md](web/README.md) |
| `docs/` | 框架文档 | [docs/README.md](docs/README.md) |
| `docs/principles.md` | 三条约定 | [principles.md](docs/principles.md) |

## 模块

| 模块 | 状态 | 链接 |
|---|---|---|
| 约定 | 已写 | [docs/principles.md](docs/principles.md) |
| 可选封装 | 已写 | [docs/escape-hatches.md](docs/escape-hatches.md) |
| JSON 信封 | 已写 | [docs/backend/api-response.md](docs/backend/api-response.md) |
| 登录 / RBAC | 已写 | [docs/backend/auth.md](docs/backend/auth.md) |
| 请求层 `request.ts` | 已写 | [docs/frontend/request.md](docs/frontend/request.md) |
| `ga-table` | 已写 | [docs/frontend/components/ga-table.md](docs/frontend/components/ga-table.md) |
| `ga-page` | 已写 | [docs/frontend/components/ga-page.md](docs/frontend/components/ga-page.md) |
| `ga-delete-confirm` | 源码 | [web/src/components/ga-delete-confirm/](web/src/components/ga-delete-confirm/) |
| `ga-icon` | 源码 | [web/src/components/ga-icon/](web/src/components/ga-icon/) |
| `ga-monaco-editor` | 源码 | [web/src/components/ga-monaco-editor/](web/src/components/ga-monaco-editor/) |
| `ga-password-input` | 源码 | [web/src/components/ga-password-input/](web/src/components/ga-password-input/) |
| `ga-search-bar` | 源码 | [web/src/components/ga-search-bar/](web/src/components/ga-search-bar/) |
| `ga-select-remote` | 源码 | [web/src/components/ga-select-remote/](web/src/components/ga-select-remote/) |
| `ga-status-tag` | 源码 | [web/src/components/ga-status-tag/](web/src/components/ga-status-tag/) |
| `ga-upload` | 源码 | [web/src/components/ga-upload/](web/src/components/ga-upload/) |

## 快速开始

- PHP ≥ 8.3，扩展 `openssl`、`pdo_mysql`（[composer.json](composer.json)）。依赖：`composer install`。
- 后端环境：项目根 `.env`，模板 [.example.env](.example.env)（`DB_*`、`ACCESS_TOKEN_TTL` 等）。安装向导也会写入 `.env`。
- 库表：[database/install/schema.sql](database/install/schema.sql)、[data.sql](database/install/data.sql)。未安装时入口重定向 `/install`（[app/index/controller/Index.php](app/index/controller/Index.php)），向导执行上述 SQL。
- HTTP 入口：[public/index.php](public/index.php)；站点根目录指向 `public/`。
- 前端目录 `web/`，包管理器为 pnpm（[web/pnpm-lock.yaml](web/pnpm-lock.yaml)）。脚本：`pnpm install`，`pnpm dev`（`vite`），`pnpm build`（[web/package.json](web/package.json)）。
- `VITE_API_BASE_URL` 为站点入口（协议+主机或反代前缀），**不含** `/admin`。未配置则用相对路径。[web/README.md](web/README.md)。

## 许可证

Apache-2.0，见 [LICENSE](LICENSE)。
