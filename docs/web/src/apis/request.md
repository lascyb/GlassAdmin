# request.ts

源码：[web/src/apis/request.ts](../../../../web/src/apis/request.ts)  
信封：[envelope.md](envelope.md) ← [web/src/apis/envelope.ts](../../../../web/src/apis/envelope.ts)  
站点入口：[web/README.md](../../../../web/README.md)（`VITE_API_BASE_URL`）

axios 封装。`get` / `post` / `put` / `patch` / `del` 参数与 axios 同名方法一致，`config` 原样透传。

## 作用

1. `axios.create`：`baseURL` 为 `VITE_API_BASE_URL`（去尾斜杠；未配置则为空，相对路径），`timeout` 30s，`withCredentials: false`。
2. 请求拦截：`useToken().token` 有值时写 `Authorization: Bearer …`；`config.encryptFields` 有值时对 JSON body 做 RSA，并写 `X-RSA-Serial`。
3. 查询参数：`paramsSerializer` 跳过 `undefined` / `null` / `''`。
4. 响应：默认将 `{code,msg,data}` 解包为 `data`；`code !== 0` 抛 `ApiError` 并 `ElMessage.error`（`silent` / `skipUnwrap` 可关）。
5. HTTP 401：刷新 access 后重放一次；失败则清登录态并跳转 `login`。
6. 业务码 `40001`：刷新公钥后重放一次。
7. `FormData`：`post` 将该次 `timeout` 设为 `0`。

[web/src/apis/dev/curd.ts](../../../../web/src/apis/dev/curd.ts) 是 CRUD 生成器业务 API，与本层无耦合。

后端信封：[ApiResponse.php](../../../app/common/ApiResponse.md)。

## 约定

成功 `code === 0`，`get<T>()` 返回 `data`。失败抛 `ApiError`。请求层已提示时 `shown = true`，页面 `catch` 调用 `showError(e)`。分页 `data` 一般为 `{ list, total, page, limit }`。非信封（如 `blob`）不经 `unwrap`。

URL：`{应用}/{点分控制器}/{action}`，如 `admin/system.auth.Admin/list`。应用名 `admin` 不写入 `VITE_API_BASE_URL`。

`AxiosRequestConfig` 扩展：

| 字段 | 作用 |
|---|---|
| `encryptFields?: string[]` | 需 RSA 的 body 字段路径；多级用 `","` |
| `skipUnwrap?: boolean` | 返回完整信封；业务码非 0 不抛错 |
| `silent?: boolean` | 不自动 `ElMessage` |
| `_retried` / `_rsaRetried` / `_rsaPlainData` | 拦截器内部标记，调用方不设置 |

导出：`http`、`get`/`post`/`put`/`patch`/`del`、`showError`、`ApiError`、`unwrap`。

## 最小示例

与 [web/src/apis/system/auth/admin.ts](../../../../web/src/apis/system/auth/admin.ts) 相同：

```ts
import { get, post, del } from '@/apis/request'

const res = await get<{ list: { id: number }[]; total: number }>(
  'admin/system.auth.Admin/list',
  { params: { page: 1, limit: 20 } },
)

await post<{ id: number }>(
  'admin/system.auth.Admin/save',
  { username: 'demo', password: 'secret', role_ids: [1], status: 1 },
  { encryptFields: ['password'] },
)

await del('admin/system.auth.Admin/delete', { params: { id: 2 } })
```

登录页直接 `post`：[web/src/views/login.vue](../../../../web/src/views/login.vue) → `admin/system.account.login.Password/login`。

## 逃生舱

```ts
import { http } from '@/apis/request'
const { data } = await http.get('admin/system.auth.Admin/list', {
  params: { page: 1, limit: 20 },
  skipUnwrap: true,
})
```

原生 `fetch`：[web/src/pinia/admin/token.ts](../../../../web/src/pinia/admin/token.ts)。`fetch` 无 401 刷新、无 `encryptFields`、无空参数过滤。

见 [escape-hatches.md](../../../escape-hatches.md)。
