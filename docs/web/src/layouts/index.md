# 管理端壳（layouts）

源码入口：[web/src/layouts/index.vue](../../../../web/src/layouts/index.vue)

## 作用

登录后的管理端外壳：拉系统配置与菜单，再按偏好挂三种容器之一。壳只负责视口/侧栏/顶栏；页面边距与卡片由页面或 `ga-page` / `ga-table` 自己管（见 [router-view/main.vue](../../../../web/src/layouts/router-view/main.vue)）。

## 推荐使用场景

- 需要侧栏菜单、顶栏、主题/布局切换的后台页：走本入口。
- 登录页、安装向导等不挂本壳。
- 不要在本文件写业务；容器差异放在 `container/`。

## 要点

1. `useSystemConfig().load()` → `useLayoutSetting().applySystemDefaults()`。
2. `fetchMenus(setting.layout.showAllMenus)` → `useLayoutMenus().init(menus)`。
3. `<component :is="setting.layout.container">`，异步组件名：`Default` / `Double` / `Module`（[container/default.vue](../../../../web/src/layouts/container/default.vue)、[double.vue](../../../../web/src/layouts/container/double.vue)、[module.vue](../../../../web/src/layouts/container/module.vue)）。
4. 偏好与主题：[pinia/layout/setting.ts](../../../../web/src/pinia/layout/setting.ts)；菜单索引：[menus.ts](../../../../web/src/pinia/layout/menus.ts)。面板：[setting/index.vue](../../../../web/src/layouts/setting/index.vue)。
5. 主内容：[router-view/main.vue](../../../../web/src/layouts/router-view/main.vue)；iframe / 外链见 `router-view/iframe/`、`link.vue`。

`pinia/layout/index.ts` 的 `useLayout` 目前为空，不要当入口。

## 已使用场景

路由挂载本组件后即用。菜单数据：[web/src/apis/system/account.ts](../../../../web/src/apis/system/account.ts) `fetchMenus`。

## 逃生舱

自定义路由组件、不 import `layouts/index.vue`。见 [escape-hatches.md](../../../escape-hatches.md)。
