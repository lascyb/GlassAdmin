# Admin 模型（登录方式）

源码：[app/admin/model/system/auth/Admin.php](../../../../../../app/admin/model/system/auth/Admin.php)

## 登录方式

| 概念 | 说明 |
|---|---|
| 系统默认 | 配置键 `admin_default_login_methods`（`multi_select`） |
| 账号字段 | `login_methods` JSON，`NULL`=跟随默认 |
| 生效集合 | `effectiveLoginMethods()` / `resolveEffectiveLoginMethods()` |
| 已知方式 | `password`、`feishu` |

`normalizeLoginMethods` 只保留已知项；**允许空数组，不回退双开**。空集合下 `assertAllowsLoginMethod` 一律失败，禁止登录。

账号 CRUD 保存显式集合时须 ≥1 项合法方式；系统默认配置不做空值拦截，若生效集合为空则登录拒绝。
