# 请求层 `request.ts`

源码：[`web/src/apis/request.ts`](../../web/src/apis/request.ts)  
信封：[`web/src/apis/envelope.ts`](../../web/src/apis/envelope.ts)  
站点入口：[`web/README.md`](../../web/README.md)（`VITE_API_BASE_URL`）

这是 axios 的薄封装，不是另一套客户端。导出的 `get` / `post` / `put` / `patch` / `del` 参数与 axios 同名方法一致，`config` 原样透传。

---

## 它实际做了什么

1. `axios.create`：`baseURL` 来自 `VITE_API_BASE_URL`（去掉尾斜杠；未配置则为空，走相对路径），`timeout` 30s，`withCredentials: false`。
2. 请求拦截：若 `useToken().token` 有值，写 `Authorization: Bearer …`；若 `config.encryptFields` 有字段，对 JSON body 做 RSA 并写 `X-RSA-Serial`。
3. 查询参数：`paramsSerializer` 跳过 `undefined` / `null` / `''`。
4. 响应：默认把 `{code,msg,data}` 解包为 `data`；`code !== 0` 抛 `ApiError` 并 `ElMessage.error`（可用 `silent` / `skipUnwrap` 关掉）。
5. HTTP 401：刷新 access 后重放一次；失败则清登录态并跳转 `login`。
6. 业务码 `40001`（RSA 密钥无效）：刷新公钥后重放一次。
7. `FormData`：`post` 时把该次 `timeout` 设为 `0`（上传不走 30s 默认超时）。

`web/src/apis/dev/curd.ts` 只是业务 API 模块（CRUD 生成器），与请求层无耦合；列表页一般直接 `import { get, post } from '@/apis/request'`，或经 `web/src/apis/system/**` 再调 `get`/`post`。

---

## 信封

后端统一形状（[`app/common/ApiResponse.php`](../../app/common/ApiResponse.php)）：

```ts
type ApiEnvelope<T> = { code: number; msg: string; data: T }
```

- 成功：`code === 0`，`get<T>()` 的返回值是 `data`（类型 `T`），不是整个信封。
- 失败：抛 `ApiError`（`code` / `msg` / `data` / `shown`）。请求层弹过提示后会把 `shown = true`，页面 `catch` 里应调用 `showError(e)`，避免再弹一次。

分页接口的 `data` 一般为 `{ list, total, page, limit }`，例如管理员列表。

非信封响应（如 `blob`）不会走 `unwrap`，原样返回。

---

## 最小用法

管理员分页列表，与 [`web/src/apis/system/auth/admin.ts`](../../web/src/apis/system/auth/admin.ts) 相同：

```ts
import { get, post, del, showError } from '@/apis/request'

type AdminListRes = {
  list: { id: number; username: string }[]
  total: number
  page: number
  limit: number
}

const res = await get<AdminListRes>('admin/system.auth.Admin/list', {
  params: { page: 1, limit: 20, keyword: 'adm' },
})
// res.list / res.total —— 已解包

await post<{ id: number }>(
  'admin/system.auth.Admin/save',
  { username: 'demo', password: 'secret', role_ids: [1], status: 1 },
  { encryptFields: ['password'] }, // 明文交给拦截器 RSA
)

await del('admin/system.auth.Admin/delete', { params: { id: 2 } })
```

对应控制器：[`app/admin/controller/system/auth/Admin.php`](../../app/admin/controller/system/auth/Admin.php)。

登录页直接 `post`，不经过 `apis/system` 模块：[`web/src/views/login.vue`](../../web/src/views/login.vue) → `admin/system.account.login.Password/login`，`encryptFields: ['username', 'password']`。

URL 写法：`{应用}/{点分控制器}/{action}`。应用名是 `admin`，**不要**再拼进 `VITE_API_BASE_URL`。

---

## `AxiosRequestConfig` 扩展

声明在 `request.ts` 的 `declare module 'axios'` 中：

| 字段 | 作用 |
|---|---|
| `encryptFields?: string[]` | 需 RSA 的 body 字段路径；多级用 `","`，如 `'user,password'` |
| `skipUnwrap?: boolean` | 返回完整信封，且不因业务码非 0 抛错 |
| `silent?: boolean` | 不自动 `ElMessage`（轮询、后台预取） |
| `_retried` / `_rsaRetried` / `_rsaPlainData` | 拦截器内部重放标记，调用方不要设 |

CRUD 生成接口使用了 `silent` + `timeout: 0`：[`web/src/apis/dev/curd.ts`](../../web/src/apis/dev/curd.ts) 的 `generate()`。仪表盘可传 `silent` 以免轮询刷屏：[`web/src/apis/dashboard.ts`](../../web/src/apis/dashboard.ts) 的 `fetchServerOverview`。

导出：`http`（axios 实例）、`get`/`post`/`put`/`patch`/`del`、`showError`、`ApiError`、`unwrap`。

---

## 逃生舱：不用封装也可以请求

### 仍用 `http`，自己处理信封

```ts
import { http } from '@/apis/request'

const { data } = await http.get('admin/system.auth.Admin/list', {
  params: { page: 1, limit: 20 },
  skipUnwrap: true,
})
if (data.code !== 0) {
  throw new Error(data.msg)
}
```

### 原生 `fetch`

仓库先例：[`web/src/pinia/admin/token.ts`](../../web/src/pinia/admin/token.ts)。

```ts
import { unwrap, type ApiEnvelope } from '@/apis/envelope'
import { useToken } from '@/pinia/admin/token'

const base = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')
const token = useToken().token
const res = await fetch(`${base}/admin/system.auth.Admin/list?page=1&limit=20`, {
  headers: token ? { Authorization: `Bearer ${token}` } : {},
})
const json = (await res.json()) as ApiEnvelope<AdminListRes>
const data = unwrap(json)
```

自行 `fetch` 时：**没有** 401 自动刷新、**没有** RSA `encryptFields`、**没有** 空参数过滤。需要这些行为就用 `get`/`post`，或自己实现。

更完整的绕过方式见 [`docs/escape-hatches.md`](../escape-hatches.md)。
