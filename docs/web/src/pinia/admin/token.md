# useToken

源码：[web/src/pinia/admin/token.ts](../../../../../web/src/pinia/admin/token.ts)  
用户态：[web/src/pinia/admin/index.ts](../../../../../web/src/pinia/admin/index.ts)（`useAdmin`）。

## 作用

opaque access / refresh（**非 JWT**），`persist` 到 `localStorage`（`token`、`refreshToken`、`expireAt`、`refreshExpireAt`）。请求头由 [request.ts](../../apis/request.md) 读 `useToken().token`。

续期用裸 `fetch`：`POST {VITE_API_BASE_URL}/admin/system.account.login.Refresh/token`，再 [unwrap](../../apis/envelope.md)，避免与 axios 拦截器循环依赖。并发共用一次 `refreshInFlight`。

## 推荐使用场景

- 登录成功：`useAdmin().init(...)` 内部 `setTokens`。
- 业务请求继续走 `get`/`post`；不要在页面里重复实现刷新。
- 需要绕过封装时，对照本文件的 `fetch`。

## 要点

| 导出 | 含义 |
|---|---|
| `accessValid` / `canRefresh` / `valid` | access 未过期；refresh 可换发；二者之一 |
| `tryRefreshAccessToken` | 立即换发 |
| `tryRefreshAccessTokenIfNeeded` | access 距过期 ≤900s 或已过期时换发；失败且 access 也无效则 `clearToken` |

后端签发：[BaseLogin.md](../../../../app/admin/controller/system/account/login/BaseLogin.md)。

## 已使用场景

- [web/src/apis/request.ts](../../../../../web/src/apis/request.ts)（Bearer、401 刷新）
- [web/src/pinia/admin/index.ts](../../../../../web/src/pinia/admin/index.ts)
- [web/src/views/login.vue](../../../../../web/src/views/login.vue)（登录后 `init`）

## 逃生舱

自己 `fetch` + 判断 `code === 0`。无拦截器则无自动 401 重放、无 `encryptFields`。见 [escape-hatches.md](../../../../escape-hatches.md)。
