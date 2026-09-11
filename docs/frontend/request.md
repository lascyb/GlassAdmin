# 请求层 `request.ts`

源码：[`web/src/apis/request.ts`](../../web/src/apis/request.ts)  
信封：[`web/src/apis/envelope.ts`](../../web/src/apis/envelope.ts)  
站点入口：[web/README.md](../../web/README.md)（`VITE_API_BASE_URL`）

axios 封装。导出的 `get` / `post` / `put` / `patch` / `del` 参数与 axios 同名方法一致，`config` 原样透传。

---

## 行为

1. `axios.create`：`baseURL` 为 `VITE_API_BASE_URL`（去尾斜杠；未配置则为空，使用相对路径），`timeout` 30s，`withCredentials: false`。
2. 请求拦截：`useToken().token` 有值时写入 `Authorization: Bearer …`；`config.encryptFields` 有值时对 JSON body 做 RSA，并写入 `X-RSA-Serial`。
3. 查询参数：`paramsSerializer` 跳过 `undefined` / `null` / `''`。
4. 响应：默认将 `{code,msg,data}` 解包为 `data`；`code !== 0` 抛 `ApiError` 并 `ElMessage.error`（`silent` / `skipUnwrap` 可关闭）。
5. HTTP 401：刷新 access 后重放一次；失败则清除登录态并跳转 `login`。
6. 业务码 `40001`（RSA 密钥无效）：刷新公钥后重放一次。
7. `FormData`：`post` 将该次 `timeout` 设为 `0`。

[`web/src/apis/dev/curd.ts`](../../web/src/apis/dev/curd.ts) 为 CRUD 生成器业务 API，与请求层无耦合。调用方式：`import { get, post } from '@/apis/request'`，或经 `web/src/apis/system/**`。

---

## 信封

后端形状（[`app/common/ApiResponse.php`](../../app/common/ApiResponse.php)）：

```ts
type ApiEnvelope<T> = { code: number; msg: string; data: T }
```

- 成功：`code === 0`。`get<T>()` 返回值为 `data`（类型 `T`）。
- 失败：抛 `ApiError`（`code` / `msg` / `data` / `shown`）。请求层已提示时将 `shown = true`。页面 `catch` 调用 `showError(e)`，避免重复弹窗。

分页 `data` 一般为 `{ list, total, page, limit }`。

非信封响应（如 `blob`）不经 `unwrap`，原样返回。

---

## 用法

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
// res.list / res.total

await post<{ id: number }>(
  'admin/system.auth.Admin/save',
  { username: 'demo', password: 'secret', role_ids: [1], status: 1 },
  { encryptFields: ['password'] },
)

await del('admin/system.auth.Admin/delete', { params: { id: 2 } })
```

控制器：[`app/admin/controller/system/auth/Admin.php`](../../app/admin/controller/system/auth/Admin.php)。

登录页直接 `post`：[web/src/views/login.vue](../../web/src/views/login.vue) → `admin/system.account.login.Password/login`，`encryptFields: ['username', 'password']`。

URL：`{应用}/{点分控制器}/{action}`。应用名为 `admin`，不写入 `VITE_API_BASE_URL`。

---

## `AxiosRequestConfig` 扩展

声明于 `request.ts` 的 `declare module 'axios'`：

| 字段 | 作用 |
|---|---|
| `encryptFields?: string[]` | 需 RSA 的 body 字段路径；多级用 `","`，如 `'user,password'` |
| `skipUnwrap?: boolean` | 返回完整信封；业务码非 0 不抛错 |
| `silent?: boolean` | 不自动 `ElMessage` |
| `_retried` / `_rsaRetried` / `_rsaPlainData` | 拦截器内部重放标记，调用方不设置 |

[`web/src/apis/dev/curd.ts`](../../web/src/apis/dev/curd.ts) 的 `generate()` 使用 `silent` + `timeout: 0`。[`web/src/apis/dashboard.ts`](../../web/src/apis/dashboard.ts) 的 `fetchServerOverview` 可传 `silent`。

导出：`http`、`get`/`post`/`put`/`patch`/`del`、`showError`、`ApiError`、`unwrap`。

---

## 不使用封装

### `http` + 自行处理信封

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

实现：[web/src/pinia/admin/token.ts](../../web/src/pinia/admin/token.ts)。

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

`fetch` 不包含：401 自动刷新、RSA `encryptFields`、空参数过滤。需要上述行为时使用 `get`/`post`，或自行实现。

完整可选路径：[docs/escape-hatches.md](../escape-hatches.md)。
