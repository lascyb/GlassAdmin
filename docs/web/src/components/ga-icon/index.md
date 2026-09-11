# ga-icon

源码：[web/src/components/ga-icon/index.vue](../../../../../web/src/components/ga-icon/index.vue)  
解析：[parse.ts](../../../../../web/src/components/ga-icon/parse.ts)。选择器见 [picker.md](picker.md)。

## 作用

按存储串渲染图标：`el:` Element Plus 图标、`url:` 图片地址、`local:` `src/assets/icons` 下无后缀文件名。无前缀时视为 el 组件名。`el` / 缺失且 `fallback` 时显示 `StarFilled`。

```ts
import GaIcon from '@/components/ga-icon/index.vue'
```

用法：[web/src/layouts/components/header/module.vue](../../../../../web/src/layouts/components/header/module.vue) 等。

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

## 逃生舱

```vue
<el-icon><User /></el-icon>
<img src="..." width="18" height="18" alt="" />
```

见 [escape-hatches.md](../../../../escape-hatches.md)。
