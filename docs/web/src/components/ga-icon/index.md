# ga-icon

源码：[web/src/components/ga-icon/index.vue](../../../../../web/src/components/ga-icon/index.vue)  
解析：[parse.ts](../../../../../web/src/components/ga-icon/parse.ts)。选择器见 [picker.md](picker.md)。

## 作用

按存储串渲染图标：`el:` Element Plus 图标、`url:` 图片地址、`local:` `src/assets/icons` 下无后缀文件名。无前缀时视为 el 组件名。`el` / 缺失且 `fallback` 时显示 `StarFilled`。

```ts
import GaIcon from '@/components/ga-icon/index.vue'
```

## 推荐使用场景

- 数据里存的是 `el:` / `local:` / `url:` 存储串（菜单、规则、快捷方式），需要同一套渲染。
- 图标固定为某个 Element Plus 组件：直接 `<el-icon>`，不必经本组件。

## Props / 事件 / 插槽

| 属性 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `icon` | `string \| null` | — | 存储串 |
| `fallback` | `boolean` | `true` | 无图标时是否显示 `StarFilled` |
| `urlSize` | `number` | — | url/local 图片边长 px；不传则用 `--el-menu-icon-width`（默认 18px） |

无事件、无插槽。

## 最小示例

```vue
<ga-icon icon="el:User" />
<ga-icon icon="local:add_child" :url-size="18" />
<ga-icon icon="url:https://example.com/a.png" :fallback="false" />
```

## 已使用场景

- 布局：[web/src/layouts/components/header/module.vue](../../../../../web/src/layouts/components/header/module.vue)、[menus/MenuTree.vue](../../../../../web/src/layouts/components/menus/MenuTree.vue)、[aside/double.vue](../../../../../web/src/layouts/components/aside/double.vue)、[error/no-permission.vue](../../../../../web/src/layouts/components/error/no-permission.vue)
- 登录：[web/src/views/login.vue](../../../../../web/src/views/login.vue)
- 工作台：[ShortcutPanel.vue](../../../../../web/src/views/system/workbench/ShortcutPanel.vue)、[ShortcutCustomizeDrawer.vue](../../../../../web/src/views/system/workbench/ShortcutCustomizeDrawer.vue)、[RecentPanel.vue](../../../../../web/src/views/system/workbench/RecentPanel.vue)
- 权限：[rule/frontend.vue](../../../../../web/src/views/system/auth/rule/frontend.vue)、[AssignFrontendsDialog.vue](../../../../../web/src/views/system/auth/rule/AssignFrontendsDialog.vue)、[role/edit.vue](../../../../../web/src/views/system/auth/role/edit.vue)
- 选择器内部：[picker.vue](../../../../../web/src/components/ga-icon/picker.vue)

## 逃生舱

```vue
<el-icon><User /></el-icon>
<img src="..." width="18" height="18" alt="" />
```

见 [escape-hatches.md](../../../../escape-hatches.md)。
