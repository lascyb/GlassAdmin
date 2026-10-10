# v-auth

源码：[web/src/directives/auth.ts](../../../../web/src/directives/auth.ts)  
注册：[web/src/main.ts](../../../../web/src/main.ts) `app.directive('auth', vAuth)`。

## 作用

按**当前页菜单的 type=btn 子节点**隐藏 DOM（`display:none`，节点仍在，便于调试）。权限数据来自 [useLayoutMenus](../pinia/layout/index.md)，不是后端二次校验。

`__gaView` 由 [web/vite-plugin-ga-view.ts](../../../../web/vite-plugin-ga-view.ts) 注入到 `src/views/**/*.vue`（[vite.config.ts](../../../../web/vite.config.ts) 的 `gaViewPlugin()`）。无权限时按这个 key 反查菜单。

`hasPermission(btn, scope?, instance?)` 可在脚本里同样判断。

## 推荐使用场景

- 工具栏/行内按钮要随前端规则里的 `path`（如 `add`、`del`）显隐。
- 需要自定义显示逻辑：`v-if="hasPermission('add')"`，或干脆不渲染。
- 后端 RBAC 仍走 [AdminController](../../../app/common/controller/AdminController.md)；本指令只管 UI。

## 要点

写法（`parseAuth`）：

| 写法 | 含义 |
|---|---|
| `v-auth:add` | arg 为按钮 path |
| `v-auth="'add'"` | 值为按钮 path |
| `v-auth:refresh="scope"` | arg 为 btn，值为 scope |
| `v-auth="{ btn, scope }"` | 对象 |

`scope`：以 `/` 开头按菜单 path；其它非空按 `component`（`hasBtnForView`）；省略则向上找 `__gaView`，再退回路由 path。无 `btn` 时视为有权限。

## 已使用场景

- [web/src/views/system/auth/admin/index.vue](../../../../web/src/views/system/auth/admin/index.vue)（`v-auth:add` 等）
- [web/src/views/system/auth/role/index.vue](../../../../web/src/views/system/auth/role/index.vue)
- [web/src/views/system/auth/rule/frontend.vue](../../../../web/src/views/system/auth/rule/frontend.vue)、[backend.vue](../../../../web/src/views/system/auth/rule/backend.vue)
- [web/src/views/system/config/index.vue](../../../../web/src/views/system/config/index.vue)
- [web/src/views/system/attachment/index.vue](../../../../web/src/views/system/attachment/index.vue)
- [web/src/views/system/account/profile.vue](../../../../web/src/views/system/account/profile.vue)

## 逃生舱

不挂指令，用 `v-if` + `hasPermission`，或按角色自己分支。见 [escape-hatches.md](../../../escape-hatches.md)。
