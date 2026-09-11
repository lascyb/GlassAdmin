# 可选封装

页面与接口可不使用 `get`/`post`、`ga-*`、`AdminController`。约定：[principles.md](principles.md)。

| 场景 | 推荐写法 | 对照文件 |
|---|---|---|
| 页面不用 `ga-page` / `ga-table` | `el-card` / `el-table`；不 import `ga-*` | [web/src/views/system/config/index.vue](../web/src/views/system/config/index.vue) |
| 只要表、不要 `ga-table` 分页壳 | `el-table` | [web/src/views/dashboard/usage.vue](../web/src/views/dashboard/usage.vue) |
| 嵌入弹窗不用 `ga-page` | 外壳用 `div` | [web/src/views/system/auth/admin/index.vue](../web/src/views/system/auth/admin/index.vue)（`shellIs`） |
| 列表请求 | `get` / `post`（axios 同签名，默认解包） | [request.md](web/src/apis/request.md) |
| 不解包信封 | `http` + `skipUnwrap: true` | [request.md](web/src/apis/request.md) |
| 不用 axios 封装 | 原生 `fetch` | [web/src/pinia/admin/token.ts](../web/src/pinia/admin/token.ts) |
| 公开接口、无登录/RBAC | 继承 `BaseController` | [Security.md](app/admin/controller/system/Security.md) |
| 登录/刷新 | 继承 `BaseLogin`（`BaseController`） | [BaseLogin.md](app/admin/controller/system/account/login/BaseLogin.md) |
| 需登录、跳过 RBAC | `$loginOnlyActions` | [AdminController.md](app/common/controller/AdminController.md) |
| 不用 `ok()` / `fail()` | `return json([...])` | [ApiResponse.md](app/common/ApiResponse.md) |

`ga-*` 须显式 import（[web/vite.config.ts](../web/vite.config.ts) `dirs: []`）。`el-*` 仍按需注册。组件文档见 [README.md](README.md)。

## 前端请求

```ts
import { get, http } from '@/apis/request'

const res = await get('admin/system.auth.Admin/list', { params: { page: 1, limit: 20 } })

const { data } = await http.get('admin/system.auth.Admin/list', {
  params: { page: 1, limit: 20 },
  skipUnwrap: true,
})
```

`fetch` 续期（[token.ts](../web/src/pinia/admin/token.ts)）：

```ts
const base = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')
await fetch(`${base}/admin/system.account.login.Refresh/token`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ refresh_token: rt }),
})
```

`VITE_API_BASE_URL` 不含 `/admin`。[web/README.md](../web/README.md)。

## 后端控制器

`AdminController` 门闩见 [AdminController.md](app/common/controller/AdminController.md)。公开接口示例（[Security.php](../app/admin/controller/system/Security.php)）：

```php
class Security extends BaseController
{
    protected array $middleware = [ApiThrottle::class];

    public function rsaPublicKey(): Response
    {
        return $this->ok(['serial' => '...', 'public_key' => '...', 'expire_at' => 0]);
    }
}
```

普通类：

```php
namespace app\admin\controller;

class Ping
{
    public function index()
    {
        return json(['code' => 0, 'msg' => 'ok', 'data' => ['pong' => true]]);
    }
}
```

路径 `/admin/Ping/index`（[config/app.php](../config/app.php) `with_route=false`）。不使用封装不等于绕过 `system_auth_rule` 已配置的权限。
