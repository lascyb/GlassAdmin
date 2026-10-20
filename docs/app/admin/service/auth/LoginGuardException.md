# LoginGuardException

源码：[LoginGuardException.php](../../../../../../app/admin/service/auth/LoginGuardException.php)

## 作用

登录冷却拦截异常。属性：`reason`（`account_locked` / `ip_locked`）、`remainSeconds`。消息为人可读剩余等待时间。

控制器捕获后返回 HTTP 429（`ApiResponse::tooManyRequests`）。

## 已使用场景

- [AdminLoginGuardService](AdminLoginGuardService.md)
- [Password.php](../../../../../../app/admin/controller/system/account/login/Password.php)
- [Refresh.php](../../../../../../app/admin/controller/system/account/login/Refresh.php)
- [oauth2/Feishu.php](../../../../../../app/admin/controller/system/account/login/oauth2/Feishu.php)

## 逃生舱

无。勿在业务里吞掉后继续验密。
