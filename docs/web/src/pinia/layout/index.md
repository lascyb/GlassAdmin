# layout pinia

源码目录：[web/src/pinia/layout/](../../../../../web/src/pinia/layout/)

- [setting.ts](../../../../../web/src/pinia/layout/setting.ts) `useLayoutSetting` — 容器、侧栏宽、主题色、`showAllMenus`
- [menus.ts](../../../../../web/src/pinia/layout/menus.ts) `useLayoutMenus` — 菜单树、`component` 索引、`hasBtnForView`
- [header.ts](../../../../../web/src/pinia/layout/header.ts)、[menusToRoutes.ts](../../../../../web/src/pinia/layout/menusToRoutes.ts)、[findMenuByPath.ts](../../../../../web/src/pinia/layout/findMenuByPath.ts)
- [index.ts](../../../../../web/src/pinia/layout/index.ts) `useLayout` **空实现**，不要用

壳如何挂载见 [layouts/index.md](../../layouts/index.md)。

## 作用

把「布局偏好」和「当前账号菜单」从 Vue 组件里拆出来。菜单 `init` 后生成路由并给 `v-auth` 查按钮。

## 推荐使用场景

- 改侧栏/主题：`useLayoutSetting`
- 查当前页按钮：`useLayoutMenus`（或 `v-auth`）
- 不要把业务列表状态放进这些 store

## 要点

`LayoutContainer`：`'Default' | 'Double' | 'Module'`。`showAllMenus` 为 true 时 `fetchMenus` 拉全量（含未授权，便于预览）。`applySystemDefaults()` 用 [Config runtime](../../../../app/admin/controller/system/Config.md) 做首次填充（`seededFromSystem`）。

## 已使用场景

- [web/src/layouts/index.vue](../../../../../web/src/layouts/index.vue)
- [web/src/directives/auth.ts](../../../../../web/src/directives/auth.ts)
- [web/src/layouts/setting/index.vue](../../../../../web/src/layouts/setting/index.vue)

## 逃生舱

不用这些 store：页面自己管菜单/主题。见 [escape-hatches.md](../../../../escape-hatches.md)。
