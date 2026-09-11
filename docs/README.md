# 文档

框架文档索引。仓库目录树与路径说明见根 [README.md](../README.md#目录)。

## 文档文件

```
docs/
├── README.md                          # 本索引
├── principles.md                      # 薄封装、文档随组件、可选封装
├── escape-hatches.md                  # 不使用 ga-* / AdminController / axios 封装
└── frontend/
    ├── request.md                     # web/src/apis/request.ts
    └── components/
        ├── ga-table.md
        └── ga-page.md
```

| 路径 | 作用 |
|---|---|
| [principles.md](principles.md) | 三条约定 |
| [escape-hatches.md](escape-hatches.md) | 前端 `el-*` / `fetch`；后端普通 ThinkPHP 控制器 |
| [frontend/request.md](frontend/request.md) | axios 实例、信封、`skipUnwrap`、`fetch` |
| [frontend/components/ga-table.md](frontend/components/ga-table.md) | props/slots/`load`；`el-table` 等价写法 |
| [frontend/components/ga-page.md](frontend/components/ga-page.md) | props/slots；`el-card` 等价写法 |

## 与源码对应的目录（摘录）

```
app/admin/controller/                  # 管理端 API
app/common/ApiResponse.php             # {code,msg,data}
app/common/controller/AdminController.php
web/src/apis/request.ts                # → docs/frontend/request.md
web/src/apis/envelope.ts
web/src/components/ga-table/           # → docs/frontend/components/ga-table.md
web/src/components/ga-page/            # → docs/frontend/components/ga-page.md
web/src/components/                    # 其余 ga-* 仅源码
web/src/views/system/config/           # el-card + el-table，未用 ga-*
web/src/pinia/admin/token.ts           # 续期用 fetch
web/vite.config.ts                     # ga-* 不自动注册
```

完整树：[README.md](../README.md#目录)。

## `ga-*` 文档

已写：`ga-table`、`ga-page`。请求层：`request.ts`。

未写（[web/src/components/](../web/src/components/)）：

- `ga-delete-confirm`
- `ga-icon`（含 picker）
- `ga-monaco-editor`
- `ga-password-input`
- `ga-search-bar`
- `ga-select-remote`
- `ga-status-tag`
- `ga-upload`（`image.vue` / `import.vue`）
