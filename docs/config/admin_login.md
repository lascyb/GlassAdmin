# admin_login 配置

源码：[config/admin_login.php](../../config/admin_login.php)

## 作用

管理端登录令牌 TTL、防暴破阈值与日志通道名。部署级配置，不进 `system_config` 后台项，也不走 `.env`。

## 默认值

| 键 | 默认 | 说明 |
|---|---|---|
| `access_token_ttl` | `3600` | access_token 缓存秒数 |
| `refresh_token_ttl_session` | `604800` | refresh（未记住我）秒数 |
| `refresh_token_ttl_remember` | `2592000` | refresh（记住我）秒数 |
| `enable` | `true` | 是否启用限流 |
| `account_ip_max` | `5` | 账号+IP 失败达此次数后冷却 |
| `account_ip_lock` | `300` | 首次冷却秒数（之后按失败次数翻倍） |
| `lock_max` | `86400` | 冷却上限秒数 |
| `ip_window` | `600` | IP 失败计数窗口秒数 |
| `ip_max` | `20` | IP 窗口内失败达此次数后冷却 |
| `ip_lock` | `1800` | IP 首次冷却秒数 |
| `captcha_after` | `3` | 验证码阈值占位（未实现） |
| `log_channel` | `login` | 对应 `config/log.php` 通道 |

## 已使用场景

- [AdminTokenService.php](../../app/admin/service/AdminTokenService.php)
- [AdminLoginGuardService](../app/admin/service/auth/AdminLoginGuardService.md)

## 逃生舱

将 `enable` 设为 `false` 关闭限流。改令牌时长直接改本文件对应键。
