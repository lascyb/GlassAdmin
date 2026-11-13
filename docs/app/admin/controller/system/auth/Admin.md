# Admin（管理员账号）

源码：[app/admin/controller/system/auth/Admin.php](../../../../../../app/admin/controller/system/auth/Admin.php)  
前端：[web/src/apis/system/auth/admin.ts](../../../../../../web/src/apis/system/auth/admin.ts)、[views/.../admin/index.vue](../../../../../../web/src/views/system/auth/admin/index.vue)

## 作用

后台管理员 CRUD。密码 RSA 解密后 bcrypt。继承 [AdminController](../../../../common/controller/AdminController.md)。

登录方式见模型 [`Admin`](../../../../../../app/admin/model/system/auth/Admin.php)：`login_methods` 为 `null` 跟随系统配置 `admin_default_login_methods`，非空则为账号覆盖；响应附带 `login_methods_effective`。

## 推荐使用场景

- 账号列表/增删改；角色多对多（`role_ids`）。
- 登录签发不在本类，见 [BaseLogin](../account/login/BaseLogin.md)。

## 要点

| 方法 | HTTP | 说明 |
|---|---|---|
| `list` | GET | 分页；`keyword`/`username`/`nickname`/`status`/`oauth_provider`/`role_id`/`exclude_role_id`；含 `login_methods` / `login_methods_effective` |
| `read` | GET | 详情 + 角色 + 登录方式 |
| `save` / `update` | POST / PUT | `login_methods`：`null`=跟随默认；数组=自定义（可 `[]`）；新建时生效含 `password` 须设密码，否则随机占位哈希 |
| `delete` | DELETE | |

编辑自己且角色含超管（`role_id=1`）时，生效登录方式必须保留 `password`。

后台**新建**：生效含 `password` 时须设密码；否则生成随机哈希占位（不可密登）。新建表单不提供勾选飞书（飞书用户多由授权 JIT 创建），编辑时才可开关飞书。

`exclude_role_id` 用 `NOT IN` 子查询排除已拥有该角色的管理员，供角色页「绑定用户」弹窗只列出未绑定的人；可与 `keyword` 等条件叠加，不能与 `role_id` 同时用于相反筛选。

## 已使用场景

- [web/src/views/system/auth/admin/index.vue](../../../../../../web/src/views/system/auth/admin/index.vue)、[edit.vue](../../../../../../web/src/views/system/auth/admin/edit.vue)

## 逃生舱

直接改 `system_auth_admin` 表（不推荐）。接口层见 [escape-hatches.md](../../../../../escape-hatches.md)。
