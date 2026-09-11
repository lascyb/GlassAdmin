# 登录与鉴权

管理端：密码/OAuth 登录签发 opaque token；受保护接口经 `AdminController` 校验 Bearer、RBAC、限流。

## 关键路径

| 路径 | 作用 |
|---|---|
| [app/admin/controller/system/account/login/Password.php](../../app/admin/controller/system/account/login/Password.php) | `POST …/Password/login`；RSA 解密用户名密码 |
| [app/admin/controller/system/account/login/Refresh.php](../../app/admin/controller/system/account/login/Refresh.php) | `POST …/Refresh/token`；`refresh_token` 换发 |
| [app/admin/controller/system/account/login/BaseLogin.php](../../app/admin/controller/system/account/login/BaseLogin.php) | 登录基类：限流 + `issueLoginOk` |
| [app/admin/controller/system/account/login/oauth2/Feishu.php](../../app/admin/controller/system/account/login/oauth2/Feishu.php) | 飞书 OAuth |
| [app/admin/controller/system/Security.php](../../app/admin/controller/system/Security.php) | `GET …/Security/rsaPublicKey` |
| [app/common/controller/AdminController.php](../../app/common/controller/AdminController.php) | 登录门闩：Bearer、RBAC、限流 |
| [app/admin/service/AdminTokenService.php](../../app/admin/service/AdminTokenService.php) | access / refresh 存 Cache（非 JWT） |
| [app/admin/service/AdminRbacService.php](../../app/admin/service/AdminRbacService.php) | path + method 是否在角色允许的 backend api 内 |
| [app/admin/service/auth/AdminAuthPayload.php](../../app/admin/service/auth/AdminAuthPayload.php) | `adminId`、`roleIds`；`isSuper()` 为 `role_id=1` |
| [web/src/views/login.vue](../../web/src/views/login.vue) | 密码登录、飞书跳转 |
| [web/src/pinia/admin/token.ts](../../web/src/pinia/admin/token.ts) | 存 token；续期 `fetch` |

## 登录

`BaseLogin` 继承 `BaseController`，不走 `AdminController`。中间件仅 `ApiThrottle`。`issueLoginOk` 签发双令牌并返回：

`access_token`、`refresh_token`、`expire_at`、`refresh_expire_at`、`user`。

TTL 来自 `.env`：`ACCESS_TOKEN_TTL`、`REFRESH_TOKEN_TTL_SESSION`、`REFRESH_TOKEN_TTL_REMEMBER`（[AdminTokenService.php](../../app/admin/service/AdminTokenService.php)）。

密码登录（[login.vue](../../web/src/views/login.vue)）：

```ts
await post('admin/system.account.login.Password/login', {
  username, password, remember,
}, { encryptFields: ['username', 'password'] })
```

刷新（[token.ts](../../web/src/pinia/admin/token.ts)）：`POST admin/system.account.login.Refresh/token`，body `refresh_token`。RSA 公钥：`GET admin/system.Security/rsaPublicKey`。

## AdminController 门闩

`initialize()`（OPTIONS 直接返回）：

1. action 不在 `$noLoginActions`：读取 `Authorization: Bearer …`，`AdminTokenService::authenticateAccess`；失败则 HTTP 401。
2. action 不在 `$loginOnlyActions`，且非超级管理员：`AdminRbacService::isApiAllowed`；失败则 HTTP 403。
3. `ApiThrottle::enforce`。

子类：

| 属性 | 含义 |
|---|---|
| `$noLoginActions` | 所列 action 不登录 |
| `$loginOnlyActions` | 仅登录、跳过 RBAC；`'*'` 表示全部 action |

[Profile.php](../../app/admin/controller/system/account/Profile.php) 将 `read`、`update` 等列入 `$loginOnlyActions`。`ok` / `paginate` 在成功响应上写操作日志。

RBAC：角色经前端规则绑定 backend api（`system_auth_role_rule`、`system_auth_rule_api`）。超级管理员（`role_id=1`）跳过接口集合检查。

## 逃生舱

| 场景 | 写法 | 对照 |
|---|---|---|
| 公开接口（无登录） | 继承 `BaseController`，自挂中间件 | [Security.php](../../app/admin/controller/system/Security.php) |
| 登录/刷新 | 继承 `BaseLogin` / `BaseController` | [BaseLogin.php](../../app/admin/controller/system/account/login/BaseLogin.php) |
| 需登录、不走 RBAC | `$loginOnlyActions` | [Profile.php](../../app/admin/controller/system/account/Profile.php) |
| 不使用框架基类 | 普通类 + `json()` | [app/index/controller/Index.php](../../app/index/controller/Index.php) |

不继承 `AdminController` 即不经过上述门闩。侧栏与接口权限仍由 `system_auth_rule` 配置，与是否使用 `ga-*` 无关。
