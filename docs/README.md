# 文档

文档路径与源码对齐：`docs/` + 仓库相对路径，扩展名改为 `.md`（`index.vue` → `index.md`）。项目级说明仍在本目录根。每个 `ga-*` 文档含：作用、推荐使用场景、Props/事件/插槽、最小示例、已使用场景、逃生舱。

快速入门见根 [README.md](../README.md)。校验镜像与「已使用场景」链接：

```bash
node tools/check-docs.mjs
# 或 composer docs:check
# 或 cd web && pnpm docs:check
```

推送到 `main`，或向 `main` 提 PR 时，[.github/workflows/docs-check.yml](../.github/workflows/docs-check.yml) 跑同一条命令。

## 源码目录

不含 `vendor/`、`web/node_modules/`、`runtime/`、`dist/`。有独立 `.md` 的节点加后缀「描述文档」。业务页未使用的 `ga-*` 标 **暂无引用** / **脚手架/预留**，并集中在组件列表末尾。

管理端 URL：`admin/{点分控制器}/{action}`（`config/app.php` `with_route=false`）。`ga-*` 须显式 import（`web/vite.config.ts` 中 `Components({ dirs: [] })`）。

- `GlassAdmin/`
  - `README.md`
  - `LICENSE`
  - `composer.json` — PHP 依赖
  - `think` — ThinkPHP CLI
  - `.example.env` — 后端环境变量模板
  - `config/` — ThinkPHP 全局配置
    - `app.php`
    - `admin_login.php`：[描述文档](config/admin_login.md)
    - `database.php`
    - `middleware.php`
  - `app/` — PHP 多应用
    - `BaseController.php`：[描述文档](app/BaseController.md)
    - `Request.php`：[描述文档](app/Request.md)
    - `middleware.php`
    - `admin/` — 管理端（URL 前缀 `/admin`）
      - `controller/`
        - `system/`
          - `Attachment.php`：[描述文档](app/admin/controller/system/Attachment.md)
          - `Config.php`：[描述文档](app/admin/controller/system/Config.md)
          - `Security.php`：[描述文档](app/admin/controller/system/Security.md)
          - `account/login/`
            - `BaseLogin.php`：[描述文档](app/admin/controller/system/account/login/BaseLogin.md)（Password / Refresh / Feishu）
          - `auth/`
            - `Admin.php`：[描述文档](app/admin/controller/system/auth/Admin.md)
            - `Role.php`：[描述文档](app/admin/controller/system/auth/Role.md)
            - `Rule.php`：[描述文档](app/admin/controller/system/auth/Rule.md)
      - `model/`
      - `service/`
        - `auth/`
          - `AdminLoginGuardService.php`：[描述文档](app/admin/service/auth/AdminLoginGuardService.md)
          - `LoginGuardException.php`：[描述文档](app/admin/service/auth/LoginGuardException.md)
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
    - `crud/`：[描述文档](extend/crud/index.md)
    - `mcp/`
  - `public/` — HTTP 入口
    - `index.php`
    - `static/`
    - `storage/`
  - `route/`
  - `view/`
  - `tools/`
    - `check-docs.mjs` — 文档守门（见上）
  - `docs/` — 本目录
    - `README.md` — 本页
    - `principles.md`：[描述文档](principles.md)
    - `escape-hatches.md`：[描述文档](escape-hatches.md)
    - `deploy/nginx.md`：[描述文档](deploy/nginx.md)
  - `web/` — Vue 3 管理端
    - `package.json`
    - `vite.config.ts`
    - `README.md` — `VITE_API_BASE_URL`（不含 `/admin`）
    - `src/`
      - `main.ts`
      - `apis/`
        - `request.ts`：[描述文档](web/src/apis/request.md)
        - `envelope.ts`：[描述文档](web/src/apis/envelope.md)
        - `dev/curd.ts`：[描述文档](web/src/apis/dev/curd.md)
      - `components/`
        - `ga-table/`：[描述文档](web/src/components/ga-table/index.md)
        - `ga-page/`：[描述文档](web/src/components/ga-page/index.md)
        - `ga-icon/`
          - `index.vue`：[描述文档](web/src/components/ga-icon/index.md)
          - `picker.vue`：[描述文档](web/src/components/ga-icon/picker.md)
        - `ga-password-input/`：[描述文档](web/src/components/ga-password-input/index.md)
        - `ga-search-bar/`：[描述文档](web/src/components/ga-search-bar/index.md)
        - `ga-status-tag/`：[描述文档](web/src/components/ga-status-tag/index.md)
        - `ga-upload/image.vue`：[描述文档](web/src/components/ga-upload/image.md)
        - 脚手架/预留（业务页暂无引用）
          - `ga-delete-confirm/`：**暂无引用** · [描述文档](web/src/components/ga-delete-confirm/index.md)
          - `ga-monaco-editor/`：**暂无引用** · [描述文档](web/src/components/ga-monaco-editor/index.md)
          - `ga-select-remote/`：**脚手架/预留**（CRUD 生成会写入）· [描述文档](web/src/components/ga-select-remote/index.md)
          - `ga-upload/import.vue`：**暂无引用** · [描述文档](web/src/components/ga-upload/import.md)
      - `views/`
        - `dev/curd/`：[描述文档](web/src/views/dev/curd/index.md)
      - `layouts/`：[描述文档](web/src/layouts/index.md)
      - `pinia/`
        - `admin/token.ts`：[描述文档](web/src/pinia/admin/token.md)
        - `layout/`：[描述文档](web/src/pinia/layout/index.md)
        - `security/rsa.ts`：[描述文档](web/src/pinia/security/rsa.md)
      - `router/`
      - `composables/`
        - `usePaginatedList.ts`：[描述文档](web/src/composables/usePaginatedList.md)
        - `useTreeCrud.ts`：[描述文档](web/src/composables/useTreeCrud.md)
      - `directives/`
        - `auth.ts`：[描述文档](web/src/directives/auth.md)
      - `theme/`
