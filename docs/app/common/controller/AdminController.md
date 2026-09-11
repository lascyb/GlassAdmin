# AdminController

源码：[app/common/controller/AdminController.php](../../../../app/common/controller/AdminController.php)

## 作用

需登录的管理端 API 基类。`initialize()`（OPTIONS 直接返回）：

1. action 不在 `$noLoginActions`：读 `Authorization: Bearer …`，`AdminTokenService::authenticateAccess`；失败 HTTP 401。
2. action 不在 `$loginOnlyActions`，且非超级管理员（`role_id=1`）：`AdminRbacService::isApiAllowed`；失败 HTTP 403。
3. `ApiThrottle::enforce`。

`ok` / `paginate` 在成功响应上写操作日志。信封见 [ApiResponse](../ApiResponse.md)。

相关：[AdminTokenService.php](../../../../app/admin/service/AdminTokenService.php)（opaque token，Cache，非 JWT）、[AdminRbacService.php](../../../../app/admin/service/AdminRbacService.php)（path + method 是否在角色允许的 backend api 内）、[AdminAuthPayload.php](../../../../app/admin/service/auth/AdminAuthPayload.php)。

## 约定

| 属性 | 含义 |
|---|---|
| `$noLoginActions` | 所列 action 不登录 |
| `$loginOnlyActions` | 仅登录、跳过 RBAC；`'*'` 表示全部 action |

[Profile.php](../../../../app/admin/controller/system/account/Profile.php) 将 `read`、`update` 等列入 `$loginOnlyActions`。RBAC：角色经前端规则绑定 backend api（`system_auth_role_rule`、`system_auth_rule_api`）。

登录签发见 [BaseLogin](../../admin/controller/system/account/login/BaseLogin.md)，不经过本类。

## 逃生舱

| 场景 | 写法 | 对照 |
|---|---|---|
| 公开接口 | 继承 `BaseController` | [Security.php](../../admin/controller/system/Security.md) |
| 登录/刷新 | `BaseLogin` | [BaseLogin.md](../../admin/controller/system/account/login/BaseLogin.md) |
| 需登录、跳过 RBAC | `$loginOnlyActions` | Profile.php |
| 不用基类 | 普通类 + `json()` | [app/index/controller/Index.php](../../../../app/index/controller/Index.php) |

不继承本类即不经过上述门闩。`system_auth_rule` 仍约束侧栏与已登记接口。
