# Role（角色）

源码：[app/admin/controller/system/auth/Role.php](../../../../../../app/admin/controller/system/auth/Role.php)  
前端：[web/src/apis/system/auth/role.ts](../../../../../../web/src/apis/system/auth/role.ts)、[views/.../role/index.vue](../../../../../../web/src/views/system/auth/role/index.vue)

## 作用

权限角色。树只用于组织；**权限不继承**：`assignRules` 全量覆盖角色与**前端**规则的关联。

## 推荐使用场景

- 角色增删改、挂管理员、勾选前端菜单/按钮。
- 后端 API 是否可调由前端规则绑定的 backend api 决定，见 [Rule.md](Rule.md)、[AdminController](../../../../common/controller/AdminController.md)。

## 要点

| 方法 | 说明 |
|---|---|
| `tree` | 角色树 + `admin_count` |
| `read` | 详情 + `rule_ids` |
| `save` / `update` / `delete` | 写角色 |
| `assignRules` | PUT；只保留 `scope=frontend` 的 id；清 RBAC/菜单缓存 |
| `assignAdmins` | PUT；批量**追加**绑定管理员（`admin_ids`），返回 `{added, skipped}`；只补缺失关联，不解除已有角色 |

## assignAdmins 语义

- **追加而非覆盖**：与 `Admin/update` 的 `role_ids` 全量覆盖不同，这里只 `INSERT` 缺失的 `(admin_id, role_id)`，已拥有的跳过，计入 `skipped`。不动管理员其他角色。
- **超管拒绝**：`id=1`（SUPER）等同全量授权，批量绑定会直接提权，接口返回 400；前端按钮同步置灰。
- **生效时机**：`access_token` 的 `role_ids` 是签发快照，目标管理员需刷新 token（`AdminTokenService::rotate` 重新取 `activeRoleIds`）或重新登录才生效。
- 上限单次 1000 人；脏 `admin_id` 先按 `system_auth_admin` 过滤。
- 角色与权限的关联未变，无需清 `AdminRbacService` 缓存。

## 已使用场景

- [web/src/views/system/auth/role/index.vue](../../../../../../web/src/views/system/auth/role/index.vue)、[edit.vue](../../../../../../web/src/views/system/auth/role/edit.vue)

## 逃生舱

不建角色、账号全部 `role_id=1` 超管（仅调试）。见 [escape-hatches.md](../../../../../escape-hatches.md)。
