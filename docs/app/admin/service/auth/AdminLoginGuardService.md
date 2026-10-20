# AdminLoginGuardService（登录守卫）

源码：[AdminLoginGuardService.php](../../../../../../app/admin/service/auth/AdminLoginGuardService.php)、[LoginGuardException.php](../../../../../../app/admin/service/auth/LoginGuardException.php)  
配置：[config/admin_login.php](../../../../../../config/admin_login.php)

## 作用

管理端登录防暴破（ThinkPHP Cache，生产建议 Redis）与登录审计文件日志。不写数据库登录表。

## 限流规则

| 维度 | 阈值配置 | 冷却公式 |
|---|---|---|
| 账号+IP | `account_ip_max`（默认 5） | `min(account_ip_lock * 2^(n-max), lock_max)` |
| 单 IP | `ip_max`（默认 20）/ `ip_window`（默认 600s） | `min(ip_lock * 2^(n-max), lock_max)` |

- 冷却期内再试：计入拦截次数，**不**增加失败次数 `n`；拒绝时抛 `LoginGuardException`（含剩余秒数）。
- 冷却过期后再失败：`n` 继续累加，时长翻倍。
- 密码登录成功：`recordSuccess` 清零该账号+IP；**不清** IP 维。
- Cache 前缀：`admin_login:`。

## 对外方法

| 方法 | 用途 |
|---|---|
| `assertAttemptAllowed($username, $ip)` | 密码入口（含 IP 维） |
| `assertIpAllowed($ip)` | 飞书 / Refresh |
| `recordFailure($username, $ip, $reason, $adminId?)` | 密码失败：写审计 + 两维计数，可能上锁 |
| `recordIpFailure($ip, $reason, $adminId?, $username?)` | 飞书/Refresh 失败：写审计 + IP 计数 |
| `recordSuccess($username, $ip, $adminId?)` | 密码成功：清账号+IP + 写 success |
| `recordIpSuccess($ip, $adminId?, $username?)` | 飞书/Refresh 成功：仅写 success |

无手动解锁接口；冷却到期后自动解除，密码成功会清零该账号+IP。`writeLog` 为私有；业务审计与 `account_locked` / `ip_locked` / `blocked_summary` 均在上述方法内部完成。限流关闭（`enable=false`）时仍写审计，只跳过计数与上锁。

## 登录日志

通道 `login`（[`config/log.php`](../../../../../../config/log.php)），目录 `runtime/log/login/`，按天、保留约 180 天。每行 JSON，字段含 `time`、`username`、`admin_id`、`ip`、`ua`、`result`、`reason`；可选 `blocked_count`、`lock_seconds`。不记录密码或 refresh_token。

`reason`：`success` / `wrong_password` / `no_user` / `disabled` / `oauth_fail` / `refresh_invalid` / `account_locked` / `ip_locked` / `blocked_summary`。`captcha_fail` 预留（项目无验证码）。

锁定期间连刷不逐条写；触发锁写一条锁定原因；锁过期后再请求时写 `blocked_summary`。

## 已使用场景

- [Password.php](../../../../../../app/admin/controller/system/account/login/Password.php)
- [Refresh.php](../../../../../../app/admin/controller/system/account/login/Refresh.php)
- [oauth2/Feishu.php](../../../../../../app/admin/controller/system/account/login/oauth2/Feishu.php)

## 逃生舱

将 `config/admin_login.php` 的 `enable` 设为 `false`，或清理 Cache 中 `admin_login:*` 键。
