# 文档

仓库源码树与路径说明见根 [README.md](../README.md#目录)。

```
docs/
├── README.md
├── principles.md
├── escape-hatches.md
├── backend/
│   ├── api-response.md
│   └── auth.md
└── frontend/
    ├── request.md
    └── components/
        ├── ga-table.md
        └── ga-page.md
```

| 路径 | 作用 |
|---|---|
| [principles.md](principles.md) | 薄封装、文档随组件、可选封装 |
| [escape-hatches.md](escape-hatches.md) | 不使用 `ga-*` / `AdminController` / axios 封装 |
| [backend/api-response.md](backend/api-response.md) | `{code,msg,data}`；`ok` / `fail` / `paginate` |
| [backend/auth.md](backend/auth.md) | 登录、刷新、RBAC、`AdminController` |
| [frontend/request.md](frontend/request.md) | `web/src/apis/request.ts` |
| [frontend/components/ga-table.md](frontend/components/ga-table.md) | `ga-table` |
| [frontend/components/ga-page.md](frontend/components/ga-page.md) | `ga-page` |

模块状态（含未写文档的 `ga-*`）：[README.md](../README.md#模块)。
