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

## 已使用场景

- [web/src/views/system/auth/role/index.vue](../../../../../../web/src/views/system/auth/role/index.vue)、[edit.vue](../../../../../../web/src/views/system/auth/role/edit.vue)

## 逃生舱

不建角色、账号全部 `role_id=1` 超管（仅调试）。见 [escape-hatches.md](../../../../../escape-hatches.md)。
