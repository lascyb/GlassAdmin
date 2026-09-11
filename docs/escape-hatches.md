# 逃生舱：如何绕过框架

框架提供约定（`get`/`post`、`ga-*`、`AdminController`），但不强制页面或接口必须走这些约定。下面按本仓库真实路径说明。

相关硬规则见 [`docs/principles.md`](principles.md)。

---

## 前端：跳过 `ga-*`

`web/vite.config.ts` 里 `unplugin-vue-components` 的 `dirs: []`：**不会**自动注册 `src/components`。`ga-*` 必须在页面里显式 `import`。不 import 就不会出现，Element Plus 的 `el-*` 仍由 resolver 按需注册。

系统配置页已经这样做了：[`web/src/views/system/config/index.vue`](../web/src/views/system/config/index.vue) 用 `el-card` + `el-table`，没有 `GaPage` / `GaTable`。

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

仪表盘 [`web/src/views/dashboard/usage.vue`](../web/src/views/dashboard/usage.vue) 用了 `ga-page`，表体仍是裸 `el-table`，不经过 `ga-table`。

管理员列表在嵌入弹窗时同样把外壳换成裸 `div`（见 [`web/src/views/system/auth/admin/index.vue`](../web/src/views/system/auth/admin/index.vue) 的 `shellIs`）。

用 `el-table` + 分页替代 `ga-table` 的完整写法见 [`docs/frontend/components/ga-table.md`](frontend/components/ga-table.md#逃生舱el-table--request)。用 `el-card` 替代 `ga-page` 见 [`docs/frontend/components/ga-page.md`](frontend/components/ga-page.md#逃生舱直接用-el-card)。

---

## 前端：请求层

### 1. 继续用 `get` / `post`（推荐的薄封装）

方法签名与 axios 一致，默认解开 `{code,msg,data}` 的 `data`。源码：[`web/src/apis/request.ts`](../web/src/apis/request.ts)。说明：[`docs/frontend/request.md`](frontend/request.md)。

```ts
import { get } from '@/apis/request'
import type { AdminListRes } from '@/apis/system/auth/admin'

const res = await get<AdminListRes>('admin/system.auth.Admin/list', {
  params: { page: 1, limit: 20 },
})
```

对应后端：[`app/admin/controller/system/auth/Admin.php`](../app/admin/controller/system/auth/Admin.php) 的 `list()`。URL 规则：ThinkPHP 多应用 `admin` + 点分控制器 `system.auth.Admin` + action `list`。

### 2. 用同一个 axios 实例，但不要自动解包

```ts
import { http } from '@/apis/request'

const { data } = await http.get('admin/system.auth.Admin/list', {
  params: { page: 1, limit: 20 },
  skipUnwrap: true, // 返回完整 { code, msg, data }，业务码非 0 也不抛
})
```

`http` 仍会带 `Authorization: Bearer …`、空查询参数过滤、401 刷新重放。只是跳过 `unwrap`。

### 3. 原生 `fetch`（仓库已有）

Token 续期故意不走 axios，避免与拦截器循环依赖：[`web/src/pinia/admin/token.ts`](../web/src/pinia/admin/token.ts) 的 `doRefreshAccessToken`。

```ts
const base = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')
const res = await fetch(`${base}/admin/system.account.login.Refresh/token`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ refresh_token: rt }),
})
const json = await res.json() // { code, msg, data }
```

需要鉴权时自行加头：`Authorization: Bearer ${useToken().token}`（store 见同一文件）。信封解析可复用 [`web/src/apis/envelope.ts`](../web/src/apis/envelope.ts) 的 `unwrap`，也可以自己判断 `code === 0`。

`VITE_API_BASE_URL` **不含** `/admin`，见 [`web/README.md`](../web/README.md)。

---

## 后端：跳过管理端基类

管理端默认基类 [`app/common/controller/AdminController.php`](../app/common/controller/AdminController.php) 在 `initialize()` 里做：Bearer 鉴权、RBAC、限流。子类可覆盖：

- `$noLoginActions`：这些 action 不登录
- `$loginOnlyActions`：只登录、跳过 RBAC（`'*'` 表示全部 action）

个人中心 [`app/admin/controller/system/account/Profile.php`](../app/admin/controller/system/account/Profile.php) 把 `read` / `update` 等放进 `$loginOnlyActions`，就是部分逃生，而不是另写框架。

### 完全不走 `AdminController`

RSA 公钥接口继承 `BaseController`，只用限流中间件：[`app/admin/controller/system/Security.php`](../app/admin/controller/system/Security.php)。

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

`ok()` / `fail()` / `paginate()` 定义在 [`app/BaseController.php`](../app/BaseController.php)，委托 [`app/common/ApiResponse.php`](../app/common/ApiResponse.php)：成功 `code === 0`，形状为 `{code,msg,data}`。分页 `data` 为 `{list,total,page,limit}`。

登录控制器同样不走 `AdminController`：[`app/admin/controller/system/account/login/BaseLogin.php`](../app/admin/controller/system/account/login/BaseLogin.php) 直接继承 `BaseController`。

### 连 `BaseController` 也不用

ThinkPHP 控制器可以是普通类，用 `json()` 自己返回。入口应用已有例子：[`app/index/controller/Index.php`](../app/index/controller/Index.php) 不继承任何 GlassAdmin 基类。

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

访问路径仍是多应用约定：`/admin/Ping/index`（`config/app.php` 里 `with_route` 为 `false`，走默认 PATH_INFO）。前端若要调它，URL 写成 `admin/Ping/index` 即可，与 `admin/system.auth.Admin/list` 同一套规则。

---

## 框架不会拦你的事

| 你想做的 | 做法 |
|---|---|
| 页面不用 `ga-page` / `ga-table` | 不 import，直接写 `el-card` / `el-table` |
| 不用 `usePaginatedList` | 自己 `ref` + `get(...)` |
| 不用 axios 封装 | `fetch`，或自建 `axios.create` |
| 接口不要登录/RBAC | 继承 `BaseController`，或设 `$noLoginActions` |
| 接口不要 JSON 外壳助手 | `return json([...])` |

菜单能不能进侧栏、接口会不会被 RBAC 拦住，是权限配置问题（`system_auth_rule`），不是组件强制。逃生舱保证的是**写代码时可以不用封装**，不是绕过已配置的权限。
