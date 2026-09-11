# Admin（管理员账号）

源码：[app/admin/controller/system/auth/Admin.php](../../../../../../app/admin/controller/system/auth/Admin.php)  
前端：[web/src/apis/system/auth/admin.ts](../../../../../../web/src/apis/system/auth/admin.ts)、[views/.../admin/index.vue](../../../../../../web/src/views/system/auth/admin/index.vue)

## 作用

后台管理员 CRUD。密码 RSA 解密后 bcrypt。继承 [AdminController](../../../../common/controller/AdminController.md)。

## 推荐使用场景

- 账号列表/增删改；角色多对多（`role_ids`）。
- 登录签发不在本类，见 [BaseLogin](../account/login/BaseLogin.md)。

## 要点

| 方法 | HTTP | 说明 |
|---|---|---|
| `list` | GET | 分页；`keyword`/`username`/`nickname`/`status`/`oauth_provider`/`role_id` |
| `read` | GET | 详情 + 角色 |
| `save` / `update` | POST / PUT | 明文最短 6；`encryptFields` 由前端加密 |
| `delete` | DELETE | |

## 已使用场景

- [web/src/views/system/auth/admin/index.vue](../../../../../../web/src/views/system/auth/admin/index.vue)、[edit.vue](../../../../../../web/src/views/system/auth/admin/edit.vue)

## 逃生舱

直接改 `system_auth_admin` 表（不推荐）。接口层见 [escape-hatches.md](../../../../../escape-hatches.md)。
