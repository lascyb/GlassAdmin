# 登录（BaseLogin / Password / Refresh / Feishu）

源码：

- [BaseLogin.php](../../../../../../../app/admin/controller/system/account/login/BaseLogin.php)
- [Password.php](../../../../../../../app/admin/controller/system/account/login/Password.php)
- [Refresh.php](../../../../../../../app/admin/controller/system/account/login/Refresh.php)
- [oauth2/Feishu.php](../../../../../../../app/admin/controller/system/account/login/oauth2/Feishu.php)

守卫：[AdminLoginGuardService](../../../../service/auth/AdminLoginGuardService.md)、配置 [admin_login](../../../../../../config/admin_login.md)

## 作用

`BaseLogin` 继承 [BaseController](../../../../../BaseController.md)，**不**走 [AdminController](../../../../../common/controller/AdminController.md)。中间件仍有通用 `ApiThrottle`。`issueLoginOk` 签发双令牌并写操作日志 `auth.login.*`。

另有登录防暴破与文件审计（见下）。

令牌 TTL：[`config/admin_login.php`](../../../../../../config/admin_login.php) 的 `access_token_ttl` / `refresh_token_ttl_session` / `refresh_token_ttl_remember`。

| URL | 作用 | 登录守卫 |
|---|---|---|
| `POST admin/system.account.login.Password/login` | RSA 用户名密码 | 账号+IP 且 IP |
| `POST admin/system.account.login.Refresh/token` | refresh 换发 | 仅 IP |
| `POST admin/system.account.login.oauth2.Feishu/login` | 飞书授权码 | 仅 IP |
| `GET admin/system.account.login.oauth2.Feishu/authorize` | 跳转飞书 | 仅 ApiThrottle |
| `GET admin/system.Security/rsaPublicKey` | 公钥 | 见 [Security.md](../../Security.md) |

## 防暴破流程（密码）

1. 取真实 IP（见 [Request.md](../../../../../Request.md)、[nginx.md](../../../../../../deploy/nginx.md)）。
2. IP 冷却中 → 429，不验密。
3. 账号+IP 冷却中 → 429，不验密。
4. 校验账号密码：失败统一「用户名或密码错误」并 `recordFailure(..., reason)`；成功 `recordSuccess`（清计数 + 写 success 日志，均在服务内完成）。
5. 连续失败达 `account_ip_max`（默认 5）进入冷却，时长 `account_ip_lock * 2^(n-max)`，上限 `lock_max`。

飞书 / Refresh：仅 IP 维；`recordIpFailure` / `recordIpSuccess`（控制器不直接写日志）。

**未做**：验证码（`captcha_after` 仅占位）。项目无 captcha 基建。

## 登录文件日志

`runtime/log/login/`，通道 `login`，一行 JSON。锁定期间连刷合并为 `account_locked`/`ip_locked` + 事后 `blocked_summary`。

## 推荐使用场景

签发/刷新令牌的接口须未登录可调，故不继承 `AdminController`。

## 最小示例

```ts
await post('admin/system.account.login.Password/login', {
  username, password, remember,
}, { encryptFields: ['username', 'password'] })
```

## 已使用场景

`extends BaseLogin`：Password / Refresh / Feishu。  
前端：[login.vue](../../../../../../../web/src/views/login.vue)、[token.ts](../../../../../../../web/src/pinia/admin/token.ts)。

## 逃生舱

`config/admin_login.php` 设 `enable=false`。其它公开接口见 [Security.md](../../Security.md)、[escape-hatches.md](../../../../../../escape-hatches.md)。
