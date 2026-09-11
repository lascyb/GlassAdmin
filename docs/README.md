# 文档

文档路径与源码对齐：`docs/` + 仓库相对路径，扩展名改为 `.md`（`index.vue` → `index.md`）。项目级说明仍在本目录根。每个 `ga-*` 文档含：作用、推荐使用场景、Props/事件/插槽、最小示例、已使用场景、逃生舱。

快速入门见根 [README.md](../README.md)。

## 项目级

| 路径 | 作用 |
|---|---|
| [principles.md](principles.md) | 薄封装、文档随组件、可选封装 |
| [escape-hatches.md](escape-hatches.md) | 不使用 `ga-*` / `AdminController` / axios 封装 |

其余说明挂在下方源码树对应节点上。

## 源码目录

不含 `vendor/`、`web/node_modules/`、`runtime/`、`dist/`。有独立 `.md` 的节点加后缀「描述文档」并链到该说明（路径相对本文件）。无独立文档的节点不加链接。

管理端 URL：`admin/{点分控制器}/{action}`（`config/app.php` `with_route=false`）。`ga-*` 须显式 import（`web/vite.config.ts` 中 `Components({ dirs: [] })`）；`el-*` 仍按需注册。

- `GlassAdmin/`
  - `README.md`
  - `LICENSE`
  - `composer.json` — PHP 依赖
  - `think` — ThinkPHP CLI
  - `.example.env` — 后端环境变量模板
  - `config/` — ThinkPHP 全局配置
    - `app.php`
    - `database.php`
    - `middleware.php`
  - `app/` — PHP 多应用
    - `BaseController.php`：[描述文档](app/BaseController.md)
    - `middleware.php`
    - `admin/` — 管理端（URL 前缀 `/admin`）
      - `controller/`
        - `system/`
          - `Security.php`：[描述文档](app/admin/controller/system/Security.md)
          - `account/login/`
            - `BaseLogin.php`：[描述文档](app/admin/controller/system/account/login/BaseLogin.md)（Password / Refresh / Feishu）
      - `model/`
      - `service/`
      - `validate/`
      - `command/`
    - `common/` — 跨应用公共代码
      - `ApiResponse.php`：[描述文档](app/common/ApiResponse.md)
      - `controller/AdminController.php`：[描述文档](app/common/controller/AdminController.md)
      - `middleware/`
    - `index/` — 默认入口应用
    - `install/` — 安装向导
    - `api/` — API 应用
  - `database/`
    - `install/` — `schema.sql`、`data.sql`
    - `migrations/`
    - `phinx/`
    - `*.sql`
  - `extend/`
    - `crud/` — CRUD 生成器
    - `mcp/`
  - `public/` — HTTP 入口
    - `index.php`
    - `static/`
    - `storage/`
  - `route/`
  - `view/`
  - `tools/`
  - `docs/` — 框架文档（本目录；路径镜像源码）
    - `README.md` — 本页
    - `principles.md`：[描述文档](principles.md)
    - `escape-hatches.md`：[描述文档](escape-hatches.md)
  - `web/` — Vue 3 管理端
    - `package.json`
    - `vite.config.ts`
    - `README.md` — `VITE_API_BASE_URL`（不含 `/admin`）
    - `src/`
      - `main.ts`
      - `apis/`
        - `request.ts`：[描述文档](web/src/apis/request.md)
        - `envelope.ts`：[描述文档](web/src/apis/envelope.md)
      - `components/`
        - `ga-table/`：[描述文档](web/src/components/ga-table/index.md)
        - `ga-page/`：[描述文档](web/src/components/ga-page/index.md)
        - `ga-delete-confirm/`：[描述文档](web/src/components/ga-delete-confirm/index.md)
        - `ga-icon/`
          - `index.vue`：[描述文档](web/src/components/ga-icon/index.md)
          - `picker.vue`：[描述文档](web/src/components/ga-icon/picker.md)
        - `ga-monaco-editor/`：[描述文档](web/src/components/ga-monaco-editor/index.md)
        - `ga-password-input/`：[描述文档](web/src/components/ga-password-input/index.md)
        - `ga-search-bar/`：[描述文档](web/src/components/ga-search-bar/index.md)
        - `ga-select-remote/`：[描述文档](web/src/components/ga-select-remote/index.md)
        - `ga-status-tag/`：[描述文档](web/src/components/ga-status-tag/index.md)
        - `ga-upload/`
          - `image.vue`：[描述文档](web/src/components/ga-upload/image.md)
          - `import.vue`：[描述文档](web/src/components/ga-upload/import.md)
      - `views/`
      - `layouts/`
      - `pinia/`
      - `router/`
      - `composables/`
      - `directives/`
      - `theme/`
