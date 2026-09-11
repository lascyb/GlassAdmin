# Security

源码：[app/admin/controller/system/Security.php](../../../../../app/admin/controller/system/Security.php)

## 作用

公开接口：`GET admin/system.Security/rsaPublicKey`，返回 `serial`、`public_key`、`expire_at`。继承 [BaseController](../../../BaseController.md)，中间件仅 `ApiThrottle`，无登录/RBAC。前端：[web/src/pinia/security/rsa.ts](../../../../../web/src/pinia/security/rsa.ts)。

## 逃生舱

本文件即「不走 AdminController」的对照实现。见 [AdminController.md](../../../common/controller/AdminController.md)、[escape-hatches.md](../../../../escape-hatches.md)。
