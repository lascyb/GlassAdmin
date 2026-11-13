# Admin 模型（登录方式）

源码：[app/admin/model/system/auth/Admin.php](../../../../../../app/admin/model/system/auth/Admin.php)

## 语义

| 概念 | 说明 |
|---|---|
| 系统默认 | `defaultLoginMethods()` ← `admin_default_login_methods`（非法/空 → `[]`，不回退） |
| 账号字段 | `login_methods`：`NULL`=跟随默认；数组=覆盖（`[]`=显式不开通） |
| 生效集合 | `effectiveLoginMethods()` |
| 校验 | `assertAllowsLoginMethod($method)`（登录 / 改密） |

`normalizeLoginMethods` 只保留 `password` / `feishu`。生效集合为空或不含对应方式 → 拒绝登录。
