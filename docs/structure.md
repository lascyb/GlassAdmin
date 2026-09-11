# 源码目录

不含 `vendor/`、`web/node_modules/`、`runtime/`、`dist/`。`docs/` 下与源码同路径的 `.md` 为对应说明。

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
├── docs/                         # 框架文档（路径镜像源码）
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
| `app/admin/controller/` | 管理端控制器。URL：`admin/{点分控制器}/{action}` | [AdminController.md](app/common/controller/AdminController.md) |
| `app/admin/controller/system/auth/Admin.php` | 管理员 CRUD | [request.md](web/src/apis/request.md)、[ga-table](web/src/components/ga-table/index.md) |
| `app/admin/controller/system/account/login/` | 登录、刷新令牌 | [BaseLogin.md](app/admin/controller/system/account/login/BaseLogin.md) |
| `app/admin/controller/system/Security.php` | RSA 公钥；继承 `BaseController` | [Security.md](app/admin/controller/system/Security.md) |
| `app/common/ApiResponse.php` | JSON `{code,msg,data}` | [ApiResponse.md](app/common/ApiResponse.md) |
| `app/BaseController.php` | `ok` / `fail` / `paginate` | [BaseController.md](app/BaseController.md) |
| `app/common/controller/AdminController.php` | Bearer 鉴权、RBAC、限流 | [AdminController.md](app/common/controller/AdminController.md) |
| `app/admin/service/AdminTokenService.php` | opaque access / refresh（Cache） | [BaseLogin.md](app/admin/controller/system/account/login/BaseLogin.md) |
| `app/admin/service/AdminRbacService.php` | 接口是否命中角色允许的 backend api | [AdminController.md](app/common/controller/AdminController.md) |
| `config/app.php` | 多应用；`with_route=false`（PATH_INFO） | [escape-hatches.md](escape-hatches.md) |
| `database/install/` | 安装用 `schema.sql`、`data.sql` | — |
| `public/index.php` | HTTP 入口 | — |
| `web/src/apis/request.ts` | axios：Token、解包信封、RSA、401 刷新 | [request.md](web/src/apis/request.md) |
| `web/src/apis/envelope.ts` | `ApiEnvelope`、`unwrap`、`ApiError` | [envelope.md](web/src/apis/envelope.md) |
| `web/src/components/ga-table/` | `el-table` + `el-pagination` | [index.md](web/src/components/ga-table/index.md) |
| `web/src/components/ga-page/` | 内边距 + `el-card` | [index.md](web/src/components/ga-page/index.md) |
| `web/src/components/ga-delete-confirm/` | 删除确认 popover | [index.md](web/src/components/ga-delete-confirm/index.md) |
| `web/src/components/ga-icon/` | 图标渲染 / 选择器 | [index.md](web/src/components/ga-icon/index.md)、[picker.md](web/src/components/ga-icon/picker.md) |
| `web/src/components/ga-monaco-editor/` | Monaco 封装 | [index.md](web/src/components/ga-monaco-editor/index.md) |
| `web/src/components/ga-password-input/` | 密码框 | [index.md](web/src/components/ga-password-input/index.md) |
| `web/src/components/ga-search-bar/` | 列表搜索栏 | [index.md](web/src/components/ga-search-bar/index.md) |
| `web/src/components/ga-select-remote/` | 远程下拉 | [index.md](web/src/components/ga-select-remote/index.md) |
| `web/src/components/ga-status-tag/` | 状态标签 | [index.md](web/src/components/ga-status-tag/index.md) |
| `web/src/components/ga-upload/` | 图片上传 / 文件导入 | [image.md](web/src/components/ga-upload/image.md)、[import.md](web/src/components/ga-upload/import.md) |
| `web/src/views/` | 页面 | [escape-hatches.md](escape-hatches.md) |
| `web/src/pinia/admin/token.ts` | 登录令牌；续期用 `fetch` | [request.md](web/src/apis/request.md) |
| `web/src/layouts/` | 管理端壳 | — |
| `web/vite.config.ts` | `el-*` 按需注册；`ga-*` 须显式 import | [ga-table](web/src/components/ga-table/index.md) |
| `web/README.md` | `VITE_API_BASE_URL`（不含 `/admin`） | [web/README.md](../web/README.md) |
| `docs/` | 框架文档 | [README.md](README.md) |
| `docs/principles.md` | 三条约定 | [principles.md](principles.md) |

## 模块

| 模块 | 状态 | 链接 |
|---|---|---|
| 约定 | 已写 | [principles.md](principles.md) |
| 可选封装 | 已写 | [escape-hatches.md](escape-hatches.md) |
| JSON 信封 | 已写 | [app/common/ApiResponse.md](app/common/ApiResponse.md) |
| 登录 / RBAC | 已写 | [AdminController.md](app/common/controller/AdminController.md)、[BaseLogin.md](app/admin/controller/system/account/login/BaseLogin.md) |
| `request.ts` | 已写 | [request.md](web/src/apis/request.md) |
| `envelope.ts` | 已写 | [envelope.md](web/src/apis/envelope.md) |
| `ga-table` | 已写 | [index.md](web/src/components/ga-table/index.md) |
| `ga-page` | 已写 | [index.md](web/src/components/ga-page/index.md) |
| `ga-delete-confirm` | 已写 | [index.md](web/src/components/ga-delete-confirm/index.md) |
| `ga-icon` | 已写 | [index.md](web/src/components/ga-icon/index.md) |
| `ga-icon/picker` | 已写 | [picker.md](web/src/components/ga-icon/picker.md) |
| `ga-monaco-editor` | 已写 | [index.md](web/src/components/ga-monaco-editor/index.md) |
| `ga-password-input` | 已写 | [index.md](web/src/components/ga-password-input/index.md) |
| `ga-search-bar` | 已写 | [index.md](web/src/components/ga-search-bar/index.md) |
| `ga-select-remote` | 已写 | [index.md](web/src/components/ga-select-remote/index.md) |
| `ga-status-tag` | 已写 | [index.md](web/src/components/ga-status-tag/index.md) |
| `ga-upload/image` | 已写 | [image.md](web/src/components/ga-upload/image.md) |
| `ga-upload/import` | 已写 | [import.md](web/src/components/ga-upload/import.md) |
