# 可选封装

框架提供 `get`/`post`、`ga-*`、`AdminController`。页面与接口可不使用上述封装。路径均来自本仓库。

约定：[docs/principles.md](principles.md)。

---

## 前端：不使用 `ga-*`

[`web/vite.config.ts`](../web/vite.config.ts) 中 `unplugin-vue-components` 的 `dirs: []`：不自动注册 `src/components`。`ga-*` 须显式 `import`。`el-*` 仍由 Element Plus resolver 按需注册。

[`web/src/views/system/config/index.vue`](../web/src/views/system/config/index.vue) 使用 `el-card` + `el-table`，未引用 `GaPage` / `GaTable`。

```vue
<template>
  <div class="p-3">
    <el-card>
      <el-table :data="configs" border stripe>
        <el-table-column prop="key" label="键名" />
      </el-table>
    </el-card>
  </div>
</template>
```

[`web/src/views/dashboard/usage.vue`](../web/src/views/dashboard/usage.vue) 使用 `ga-page`，表体为 `el-table`，未使用 `ga-table`。

[`web/src/views/system/auth/admin/index.vue`](../web/src/views/system/auth/admin/index.vue) 在嵌入弹窗时将外壳换为 `div`（`shellIs`）。

`el-table` 与分页替代 `ga-table`：[ga-table.md](frontend/components/ga-table.md#改用-el-table-与-get)。`el-card` 替代 `ga-page`：[ga-page.md](frontend/components/ga-page.md#改用-el-card)。

---

## 前端：请求

### 使用 `get` / `post`

参数与 axios 同名方法一致。默认解开 `{code,msg,data}` 的 `data`。源码：[`web/src/apis/request.ts`](../web/src/apis/request.ts)。说明：[request.md](frontend/request.md)。

```ts
import { get } from '@/apis/request'
import type { AdminListRes } from '@/apis/system/auth/admin'

const res = await get<AdminListRes>('admin/system.auth.Admin/list', {
  params: { page: 1, limit: 20 },
})
```

后端：[`app/admin/controller/system/auth/Admin.php`](../app/admin/controller/system/auth/Admin.php) 的 `list()`。URL：多应用 `admin` + 点分控制器 `system.auth.Admin` + action `list`。

### 使用同一 axios 实例，不解包

```ts
import { http } from '@/apis/request'

const { data } = await http.get('admin/system.auth.Admin/list', {
  params: { page: 1, limit: 20 },
  skipUnwrap: true, // 完整 { code, msg, data }；业务码非 0 不抛错
})
```

`http` 仍附加 `Authorization: Bearer …`、过滤空查询参数、HTTP 401 刷新重放。仅跳过 `unwrap`。

### 原生 `fetch`

[`web/src/pinia/admin/token.ts`](../web/src/pinia/admin/token.ts) 的 `doRefreshAccessToken` 使用 `fetch`，避免与 axios 拦截器循环依赖。

```ts
const base = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')
const res = await fetch(`${base}/admin/system.account.login.Refresh/token`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ refresh_token: rt }),
})
const json = await res.json() // { code, msg, data }
```

鉴权头：`Authorization: Bearer ${useToken().token}`。信封可用 [`web/src/apis/envelope.ts`](../web/src/apis/envelope.ts) 的 `unwrap`，或判断 `code === 0`。

`VITE_API_BASE_URL` 不含 `/admin`。[web/README.md](../web/README.md)。

---

## 后端：不使用 `AdminController`

[`app/common/controller/AdminController.php`](../app/common/controller/AdminController.php) 在 `initialize()` 中执行 Bearer 鉴权、RBAC、限流。子类可覆盖：

- `$noLoginActions`：所列 action 不登录
- `$loginOnlyActions`：仅登录、跳过 RBAC（`'*'` 表示全部 action）

[`app/admin/controller/system/account/Profile.php`](../app/admin/controller/system/account/Profile.php) 将 `read` / `update` 等列入 `$loginOnlyActions`。

### 继承 `BaseController`

[`app/admin/controller/system/Security.php`](../app/admin/controller/system/Security.php) 继承 `BaseController`，仅使用限流中间件。

```php
namespace app\admin\controller\system;

use app\BaseController;
use app\common\middleware\ApiThrottle;
use think\Response;

class Security extends BaseController
{
    protected array $middleware = [
        ApiThrottle::class,
    ];

    public function rsaPublicKey(): Response
    {
        return $this->ok([
            'serial'     => '...',
            'public_key' => '...',
            'expire_at'  => 0,
        ]);
    }
}
```

`ok()` / `fail()` / `paginate()` 定义于 [`app/BaseController.php`](../app/BaseController.php)，委托 [`app/common/ApiResponse.php`](../app/common/ApiResponse.php)。成功 `code === 0`，形状 `{code,msg,data}`。分页 `data` 为 `{list,total,page,limit}`。

登录基类 [`app/admin/controller/system/account/login/BaseLogin.php`](../app/admin/controller/system/account/login/BaseLogin.php) 亦继承 `BaseController`。

### 不继承 `BaseController`

ThinkPHP 控制器可为普通类，使用 `json()`。[`app/index/controller/Index.php`](../app/index/controller/Index.php) 未继承 GlassAdmin 基类。

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

路径：`/admin/Ping/index`（[`config/app.php`](../config/app.php) 中 `with_route` 为 `false`，使用 PATH_INFO）。前端 URL：`admin/Ping/index`，与 `admin/system.auth.Admin/list` 相同规则。

---

## 对照

| 封装 | 不使用时 |
|---|---|
| `ga-page` / `ga-table` | 不 import；使用 `el-card` / `el-table` |
| `usePaginatedList` | `ref` + `get(...)` |
| `get` / `post` | `fetch`，或自建 `axios.create` |
| `AdminController` 登录/RBAC | 继承 `BaseController`，或设置 `$noLoginActions` |
| `ok()` / `fail()` | `return json([...])` |

侧栏菜单与接口 RBAC 由 `system_auth_rule` 配置，与是否使用 `ga-*` 无关。不使用封装不等于绕过已配置的权限。
