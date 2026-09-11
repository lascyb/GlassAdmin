# 登录（BaseLogin / Password / Refresh）

源码：

- [BaseLogin.php](../../../../../../../app/admin/controller/system/account/login/BaseLogin.php)
- [Password.php](../../../../../../../app/admin/controller/system/account/login/Password.php)
- [Refresh.php](../../../../../../../app/admin/controller/system/account/login/Refresh.php)
- [oauth2/Feishu.php](../../../../../../../app/admin/controller/system/account/login/oauth2/Feishu.php)

## 作用

`BaseLogin` 继承 [BaseController](../../../../../BaseController.md)，**不**走 [AdminController](../../../../../common/controller/AdminController.md)。中间件仅 `ApiThrottle`。`issueLoginOk` 签发双令牌并返回 `access_token`、`refresh_token`、`expire_at`、`refresh_expire_at`、`user`。

TTL：`.env` 的 `ACCESS_TOKEN_TTL`、`REFRESH_TOKEN_TTL_SESSION`、`REFRESH_TOKEN_TTL_REMEMBER`（[AdminTokenService.php](../../../../../../../app/admin/service/AdminTokenService.php)）。

| URL | 作用 |
|---|---|
| `POST admin/system.account.login.Password/login` | RSA 解密用户名密码后登录 |
| `POST admin/system.account.login.Refresh/token` | body `refresh_token` 换发 |
| `GET admin/system.account.login.oauth2.Feishu/authorize` | 飞书 OAuth |
| `GET admin/system.Security/rsaPublicKey` | 公钥，见 [Security.md](../../Security.md) |

前端：[web/src/views/login.vue](../../../../../../../web/src/views/login.vue)、[web/src/pinia/admin/token.ts](../../../../../../../web/src/pinia/admin/token.ts)（续期用 `fetch`）。

## 推荐使用场景

- 签发/刷新令牌的接口：必须能在未登录时调用，因此不要继承 `AdminController`。
- 其它公开只读接口（如公钥）用 [Security](../../Security.md) 那种 `BaseController` + 限流即可，不必经 `BaseLogin`。

## 最小示例

```ts
await post('admin/system.account.login.Password/login', {
  username, password, remember,
}, { encryptFields: ['username', 'password'] })
```

## 已使用场景

`extends BaseLogin`：

- [Password.php](../../../../../../../app/admin/controller/system/account/login/Password.php)
- [Refresh.php](../../../../../../../app/admin/controller/system/account/login/Refresh.php)
- [oauth2/Feishu.php](../../../../../../../app/admin/controller/system/account/login/oauth2/Feishu.php)

前端调用：[web/src/views/login.vue](../../../../../../../web/src/views/login.vue)、[web/src/pinia/admin/token.ts](../../../../../../../web/src/pinia/admin/token.ts)。

## 逃生舱

登录接口本身已不继承 `AdminController`。其它公开接口见 [Security.md](../../Security.md)、[escape-hatches.md](../../../../../../escape-hatches.md)。
