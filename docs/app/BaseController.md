# BaseController

源码：[app/BaseController.php](../../app/BaseController.php)

ThinkPHP 控制器基类：持有 `$request` / `$app`，提供 `validate()`，以及委托 [ApiResponse](common/ApiResponse.md) 的 `ok` / `fail` / `paginate` / `rsaKeyInvalid`。`initialize()` 默认为空。

管理端登录门闩在子类 [AdminController](common/controller/AdminController.md)。登录接口继承 [BaseLogin](admin/controller/system/account/login/BaseLogin.md)，不走 `AdminController`。

不使用本类：普通 PHP 类 + `json()`，见 [escape-hatches.md](../escape-hatches.md)。
