# 源码目录

不含 `vendor/`、`web/node_modules/`、`runtime/`、`dist/`。

## 目录树

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
| `app/admin/controller/` | 管理端控制器。URL：`admin/{点分控制器}/{action}` | [auth.md](backend/auth.md) |
| `app/admin/controller/system/auth/Admin.php` | 管理员 CRUD | [request.md](frontend/request.md)、[ga-table.md](frontend/components/ga-table.md) |
| `app/admin/controller/system/account/login/` | 登录、刷新令牌 | [auth.md](backend/auth.md) |
| `app/admin/controller/system/Security.php` | RSA 公钥；继承 `BaseController` | [auth.md](backend/auth.md)、[escape-hatches.md](escape-hatches.md) |
| `app/common/ApiResponse.php` | JSON `{code,msg,data}`；分页 `{list,total,page,limit}` | [api-response.md](backend/api-response.md) |
| `app/BaseController.php` | `ok` / `fail` / `paginate` | [api-response.md](backend/api-response.md) |
| `app/common/controller/AdminController.php` | Bearer 鉴权、RBAC、限流 | [auth.md](backend/auth.md) |
| `app/admin/service/AdminTokenService.php` | opaque access / refresh（Cache） | [auth.md](backend/auth.md) |
| `app/admin/service/AdminRbacService.php` | 接口是否命中角色允许的 backend api | [auth.md](backend/auth.md) |
| `config/app.php` | 多应用；`with_route=false`（PATH_INFO） | [escape-hatches.md](escape-hatches.md) |
| `database/install/` | 安装用 `schema.sql`、`data.sql` | — |
| `public/index.php` | HTTP 入口 | — |
| `web/src/apis/request.ts` | axios：Token、解包信封、RSA、401 刷新 | [request.md](frontend/request.md) |
| `web/src/apis/envelope.ts` | `ApiEnvelope`、`unwrap`、`ApiError` | [request.md](frontend/request.md)、[api-response.md](backend/api-response.md) |
| `web/src/components/ga-table/` | `el-table` + `el-pagination` | [ga-table.md](frontend/components/ga-table.md) |
| `web/src/components/ga-page/` | 内边距 + `el-card` | [ga-page.md](frontend/components/ga-page.md) |
| `web/src/components/` | 其余 `ga-*` | — |
| `web/src/views/` | 页面 | [escape-hatches.md](escape-hatches.md) |
| `web/src/pinia/admin/token.ts` | 登录令牌；续期用 `fetch` | [request.md](frontend/request.md) |
| `web/src/layouts/` | 管理端壳 | — |
| `web/vite.config.ts` | `el-*` 按需注册；`ga-*` 须显式 import | [ga-table.md](frontend/components/ga-table.md) |
| `web/README.md` | `VITE_API_BASE_URL`（不含 `/admin`） | [web/README.md](../web/README.md) |
| `docs/` | 框架文档 | [README.md](README.md) |
| `docs/principles.md` | 三条约定 | [principles.md](principles.md) |

## 模块

| 模块 | 状态 | 链接 |
|---|---|---|
| 约定 | 已写 | [principles.md](principles.md) |
| 可选封装 | 已写 | [escape-hatches.md](escape-hatches.md) |
| JSON 信封 | 已写 | [backend/api-response.md](backend/api-response.md) |
| 登录 / RBAC | 已写 | [backend/auth.md](backend/auth.md) |
| 请求层 `request.ts` | 已写 | [frontend/request.md](frontend/request.md) |
| `ga-table` | 已写 | [frontend/components/ga-table.md](frontend/components/ga-table.md) |
| `ga-page` | 已写 | [frontend/components/ga-page.md](frontend/components/ga-page.md) |
| `ga-delete-confirm` | 源码 | [web/src/components/ga-delete-confirm/](../web/src/components/ga-delete-confirm/) |
| `ga-icon` | 源码 | [web/src/components/ga-icon/](../web/src/components/ga-icon/) |
| `ga-monaco-editor` | 源码 | [web/src/components/ga-monaco-editor/](../web/src/components/ga-monaco-editor/) |
| `ga-password-input` | 源码 | [web/src/components/ga-password-input/](../web/src/components/ga-password-input/) |
| `ga-search-bar` | 源码 | [web/src/components/ga-search-bar/](../web/src/components/ga-search-bar/) |
| `ga-select-remote` | 源码 | [web/src/components/ga-select-remote/](../web/src/components/ga-select-remote/) |
| `ga-status-tag` | 源码 | [web/src/components/ga-status-tag/](../web/src/components/ga-status-tag/) |
| `ga-upload` | 源码 | [web/src/components/ga-upload/](../web/src/components/ga-upload/) |
