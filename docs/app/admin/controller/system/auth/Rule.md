# Rule（权限规则）

源码：[app/admin/controller/system/auth/Rule.php](../../../../../../app/admin/controller/system/auth/Rule.php)  
前端：[web/src/apis/system/auth/rule.ts](../../../../../../web/src/apis/system/auth/rule.ts)、[views/.../rule/](../../../../../../web/src/views/system/auth/rule/)

## 作用

权限规则树：`scope=backend|frontend`。前端 view/route/btn 可绑定 backend api（`system_auth_rule_api`）。侧栏与 `v-auth` 吃前端树；接口 RBAC 吃绑定后的 backend api。

## 推荐使用场景

- 维护菜单、按钮、后端 API 节点及其绑定。
- 生成器写规则见 [extend/crud](../../../../../extend/crud/index.md)。

## 要点

| 方法 | 说明 |
|---|---|
| `tree` | `scope` 必填 `backend` / `frontend` |
| `read` / `save` / `update` / `delete` / `sort` | 节点 CRUD；删时处理子节点 |
| `bindApis` | 前端节点绑定 backend 规则 |
| `assignRoles` / `roles` | 规则侧看角色 |
| `assignFrontends` / `frontends` | 后端 API 反绑前端 |
| `apiList` | 后端 API 列表 |

## 已使用场景

- [web/src/views/system/auth/rule/frontend.vue](../../../../../../web/src/views/system/auth/rule/frontend.vue)、[backend.vue](../../../../../../web/src/views/system/auth/rule/backend.vue)、[edit.vue](../../../../../../web/src/views/system/auth/rule/edit.vue)

## 逃生舱

不配规则则仅超管可进（`role_id=1` 跳过 RBAC）。见 [AdminController](../../../../common/controller/AdminController.md)、[escape-hatches.md](../../../../../escape-hatches.md)。
