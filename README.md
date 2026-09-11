# GlassAdmin

Vue 3 + ThinkPHP 8 + Element Plus + TypeScript 后台框架。封装为可选约定，行为以 Vue / ThinkPHP / Element Plus 官方文档为准。

Apache-2.0，见 [LICENSE](LICENSE)。

## 目录

```
GlassAdmin/
├── README.md
├── LICENSE
├── composer.json                 # PHP 依赖（ThinkPHP 8、think-orm、think-multi-app）
├── think                         # ThinkPHP CLI 入口
├── .example.env                  # 后端环境变量模板
├── config/                       # ThinkPHP 全局配置
│   ├── app.php                   # 多应用；default_app=index；with_route=false
│   ├── database.php
│   └── middleware.php
├── app/                          # PHP 多应用
│   ├── BaseController.php        # ok/fail/paginate → ApiResponse
│   ├── middleware.php
│   ├── admin/                    # 管理端 API（URL 前缀 /admin）
│   │   ├── controller/           # dashboard、dev/curd、system（auth/account/…）
│   │   ├── model/
│   │   ├── service/              # Token、RBAC、RSA、操作日志
│   │   ├── validate/
│   │   └── command/              # CRUD 生成、RSA 密钥、MCP
│   ├── common/                   # 跨应用
│   │   ├── ApiResponse.php       # JSON {code,msg,data}
│   │   ├── controller/AdminController.php  # 登录 + RBAC + 限流
│   │   └── middleware/           # CORS、ApiThrottle
│   ├── index/                    # 默认入口应用
│   ├── install/                  # 安装向导
│   └── api/                      # API 应用（占位）
├── database/
│   ├── install/                  # schema.sql、data.sql
│   ├── migrations/
│   ├── phinx/
│   └── *.sql                     # 增量脚本
├── extend/
│   ├── crud/                     # CRUD 代码生成
│   └── mcp/
├── public/                       # HTTP 入口
│   ├── index.php
│   ├── static/
│   └── storage/
├── route/
├── view/
├── tools/
├── docs/                         # 框架文档（本仓库说明）
│   ├── README.md
│   ├── principles.md
│   ├── escape-hatches.md
│   └── frontend/
└── web/                          # Vue 3 管理端
    ├── package.json
    ├── vite.config.ts            # el-* 按需注册；ga-* 须显式 import
    ├── README.md                 # VITE_API_BASE_URL
    └── src/
        ├── main.ts
        ├── apis/                 # request.ts、envelope.ts、业务 API
        ├── components/           # ga-* 可选封装
        ├── views/                # 页面（dashboard、system、dev）
        ├── layouts/              # 壳：侧栏、顶栏、主视口
        ├── pinia/                # admin/token、layout、security/rsa
        ├── router/
        ├── composables/
        ├── directives/           # v-auth
        └── theme/
```

不含 `vendor/`、`web/node_modules/`、构建产物。

## 目录说明

| 路径 | 作用 | 文档 |
|---|---|---|
| `app/admin/controller/` | ThinkPHP 8 管理端控制器。URL：`admin/{点分控制器}/{action}` | [逃生舱](docs/escape-hatches.md) |
| `app/admin/controller/system/auth/Admin.php` | 管理员 CRUD 示例 | [request.md](docs/frontend/request.md)、[ga-table.md](docs/frontend/components/ga-table.md) |
| `app/admin/controller/system/Security.php` | RSA 公钥；继承 `BaseController`，无登录 | [逃生舱](docs/escape-hatches.md) |
| `app/common/ApiResponse.php` | JSON 外壳 `{code,msg,data}`；分页 `{list,total,page,limit}` | [request.md](docs/frontend/request.md) |
| `app/common/controller/AdminController.php` | Bearer 鉴权、RBAC、限流；可设 `$noLoginActions` / `$loginOnlyActions` | [逃生舱](docs/escape-hatches.md) |
| `app/BaseController.php` | `ok` / `fail` / `paginate` | [逃生舱](docs/escape-hatches.md) |
| `config/app.php` | 多应用、`with_route=false`（PATH_INFO） | [逃生舱](docs/escape-hatches.md) |
| `database/install/` | 安装库表与内置数据 | — |
| `web/src/apis/request.ts` | axios 实例：Token、解包信封、RSA、401 刷新 | [request.md](docs/frontend/request.md) |
| `web/src/apis/envelope.ts` | `ApiEnvelope`、`unwrap`、`ApiError` | [request.md](docs/frontend/request.md) |
| `web/src/components/ga-table/` | `el-table` + `el-pagination` 布局 | [ga-table.md](docs/frontend/components/ga-table.md) |
| `web/src/components/ga-page/` | 内边距 + `el-card` | [ga-page.md](docs/frontend/components/ga-page.md) |
| `web/src/components/` | 其余 `ga-*`（见下方清单） | 源码；文档未齐 |
| `web/src/views/` | 页面。`system/config` 使用 `el-card`/`el-table`，未用 `ga-page`/`ga-table` | [逃生舱](docs/escape-hatches.md) |
| `web/src/pinia/admin/token.ts` | 登录令牌；续期使用原生 `fetch` | [request.md](docs/frontend/request.md) |
| `web/src/layouts/` | 管理端壳层 | — |
| `web/vite.config.ts` | `Components({ dirs: [] })`：`ga-*` 不自动注册 | [ga-table.md](docs/frontend/components/ga-table.md) |
| `web/README.md` | `VITE_API_BASE_URL`（不含 `/admin`） | [web/README.md](web/README.md) |
| `docs/` | 原则、逃生舱、请求层与样例组件 | [docs/README.md](docs/README.md) |

约定全文：[docs/principles.md](docs/principles.md)。

## 能力与文档

| 能力 | 说明 | 文档 / 源码 |
|---|---|---|
| 薄封装 | `ga-*` 组合 `el-*` 的布局与默认值；不另设组件模型。后端为 ThinkPHP 控制器 | [principles.md](docs/principles.md)；[ga-table/index.vue](web/src/components/ga-table/index.vue) |
| 与官方文档对应 | 表格/分页/卡片行为见 Element Plus；请求与路由见 ThinkPHP 8 | [EP Table](https://element-plus.org/zh-CN/component/table.html)、[TP8](https://doc.thinkphp.cn/v8_0/index.html)；控制器 [app/admin/controller/](app/admin/controller/) |
| 可选封装 | 页面可不 import `ga-*`；请求可用 `fetch`；控制器可不继承 `AdminController` | [escape-hatches.md](docs/escape-hatches.md)；[config/index.vue](web/src/views/system/config/index.vue)；[token.ts](web/src/pinia/admin/token.ts) |
| 请求层 | `get`/`post` 与 axios 同签名；默认解包 `{code,msg,data}` | [request.md](docs/frontend/request.md) ← [request.ts](web/src/apis/request.ts) |
| 样例组件文档 | 最小示例、props/events、改用 Element Plus 的写法 | [ga-table](docs/frontend/components/ga-table.md)、[ga-page](docs/frontend/components/ga-page.md) |

## 技术栈

| 层 | 选型 | 位置 |
|---|---|---|
| 前端 | Vue 3、TypeScript、Vite、Element Plus、Pinia、axios | [web/](web/)、[web/package.json](web/package.json) |
| 后端 | PHP ≥ 8.3、ThinkPHP 8 多应用、think-orm | [app/](app/)、[composer.json](composer.json)（`topthink/framework ^8.0`） |
| 管理端 API | 应用名 `admin` | 例：[Admin.php](app/admin/controller/system/auth/Admin.php) |
| UI | Element Plus；`ga-*` 为可选封装 | [web/src/components/](web/src/components/)；`el-*` 按需注册（[vite.config.ts](web/vite.config.ts)） |

接口基址 `VITE_API_BASE_URL` 不含 `/admin`。调用路径自带应用前缀，如 `admin/system.auth.Admin/list`。[web/README.md](web/README.md)。

## `ga-*` 文档

已写：[ga-table](docs/frontend/components/ga-table.md)、[ga-page](docs/frontend/components/ga-page.md)、[request.ts](docs/frontend/request.md)。

未写（源码在 [web/src/components/](web/src/components/)）：

- `ga-delete-confirm`
- `ga-icon`（含 picker）
- `ga-monaco-editor`
- `ga-password-input`
- `ga-search-bar`
- `ga-select-remote`
- `ga-status-tag`
- `ga-upload`（`image.vue` / `import.vue`）
