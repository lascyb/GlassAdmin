# AdminController

源码：[app/common/controller/AdminController.php](../../../../app/common/controller/AdminController.php)

## 作用

需登录的管理端 API 基类。`initialize()`（OPTIONS 直接返回）：

1. action 不在 `$noLoginActions`：读 `Authorization: Bearer …`，`AdminTokenService::authenticateAccess`；失败 HTTP 401。
2. action 不在 `$loginOnlyActions`，且非超级管理员（`role_id=1`）：`AdminRbacService::isApiAllowed`；失败 HTTP 403。
3. `ApiThrottle::enforce`。

RBAC 只在本方法里执行。不继承本类的控制器不做 RBAC 检查。

`ok` / `paginate` 在成功响应上写操作日志。信封见 [ApiResponse](../ApiResponse.md)。

相关：[AdminTokenService.php](../../../../app/admin/service/AdminTokenService.php)（opaque token，Cache，非 JWT）、[AdminRbacService.php](../../../../app/admin/service/AdminRbacService.php)（path + method 是否在角色允许的 backend api 内）、[AdminAuthPayload.php](../../../../app/admin/service/auth/AdminAuthPayload.php)。

## 推荐使用场景

- 管理端接口需要登录 + RBAC + 限流。
- 已登录但不查角色：仍继承本类，把 action 写入 `$loginOnlyActions`（如 Profile）。
- 公开接口不要继承本类（[Security](../../admin/controller/system/Security.md)）；登录/刷新用 [BaseLogin](../../admin/controller/system/account/login/BaseLogin.md)。

## 约定

| 属性 | 含义 |
|---|---|
| `$noLoginActions` | 所列 action 不登录 |
| `$loginOnlyActions` | 仅登录、跳过 RBAC；`'*'` 表示全部 action |

[Profile.php](../../../../app/admin/controller/system/account/Profile.php) 将 `read`、`update`、`changePassword` 等列入 `$loginOnlyActions`。改密不再依赖按钮规则 `changePassword`（规则 67 仍可保留），而由生效登录方式是否含 `password` 控制。RBAC：角色经前端规则绑定 backend api（`system_auth_role_rule`、`system_auth_rule_api`）。

登录签发见 [BaseLogin](../../admin/controller/system/account/login/BaseLogin.md)，不经过本类。

## 已使用场景

`extends AdminController`：

- [system/auth/Admin.php](../../../../app/admin/controller/system/auth/Admin.php)、[Role.php](../../../../app/admin/controller/system/auth/Role.php)、[Rule.php](../../../../app/admin/controller/system/auth/Rule.php)
- [system/account/Profile.php](../../../../app/admin/controller/system/account/Profile.php)、[Menu.php](../../../../app/admin/controller/system/account/Menu.php)
- [system/Config.php](../../../../app/admin/controller/system/Config.php)、[Attachment.php](../../../../app/admin/controller/system/Attachment.php)
- [system/workbench/Shortcut.php](../../../../app/admin/controller/system/workbench/Shortcut.php)
- [dashboard/Usage.php](../../../../app/admin/controller/dashboard/Usage.php)、[Server.php](../../../../app/admin/controller/dashboard/Server.php)
- [dev/curd/Index.php](../../../../app/admin/controller/dev/curd/Index.php)、[History.php](../../../../app/admin/controller/dev/curd/History.php)、[Remote.php](../../../../app/admin/controller/dev/curd/Remote.php)

不继承本类：登录族 [BaseLogin](../../admin/controller/system/account/login/BaseLogin.md)、[Security.php](../../../../app/admin/controller/system/Security.php)。

## 逃生舱

公开接口用 `BaseController`，登录用 `BaseLogin`，跳过 RBAC 用 `$loginOnlyActions`。表见 [escape-hatches.md](../../../escape-hatches.md)。不继承本类即不做 RBAC 检查。
