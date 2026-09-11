# ga-search-bar

源码：[web/src/components/ga-search-bar/index.vue](../../../../../web/src/components/ga-search-bar/index.vue)

## 作用

列表工具栏：关键词 `el-input`、查询/刷新按钮、可选高级搜索（`advanced` 插槽 + 展开按钮）。不发请求，只 `emit`。重置会清空 keyword，再 `reset` + `search`。

```ts
import GaSearchBar from '@/components/ga-search-bar/index.vue'
```

用法：[web/src/views/system/auth/admin/index.vue](../../../../../web/src/views/system/auth/admin/index.vue)、[attachment/index.vue](../../../../../web/src/views/system/attachment/index.vue)。

## Props / 事件 / 插槽

| 绑定 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `v-model:keyword` | `string` | `''` | 关键词 |

| 属性 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `loading` | `boolean` | `false` | 刷新按钮禁用 |
| `keywordPlaceholder` | `string` | `'关键词'` | 占位 |
| `showKeyword` | `boolean` | `true` | 是否显示关键词框与查询按钮 |
| `showToggle` | `boolean` | `true` | 是否显示展开高级搜索 |

| 事件 | 时机 |
|---|---|
| `search` | 查询、回车、重置后 |
| `refresh` | 刷新按钮 |
| `reset` | 重置（先清空 keyword） |

| 插槽 | 用途 |
|---|---|
| `actions` | 左侧操作（如新增） |
| `advanced` | 高级条件；有此插槽才出现展开按钮与查询/重置表单 |

## 最小示例

```vue
<ga-search-bar
  v-model:keyword="keyword"
  :loading="loading"
  keyword-placeholder="用户名 / 昵称"
  @search="search"
  @refresh="load"
  @reset="resetAdvanced"
>
  <template #advanced>
    <el-form-item label="状态">
      <el-select v-model="status" clearable />
    </el-form-item>
  </template>
  <template #actions>
    <el-button type="primary">新增</el-button>
  </template>
</ga-search-bar>
```

## 逃生舱

自行排 `el-input` + `el-button`，或 `el-form inline`。见 [escape-hatches.md](../../../../escape-hatches.md)。
