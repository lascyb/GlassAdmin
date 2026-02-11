# SelectRemote 远程搜索选择器组件

一个基于 Element Plus 的远程搜索选择器组件，支持分页、自定义数据转换、初始化选项等功能。

## 功能特性

- ✅ **远程搜索**：支持通过接口动态加载选项数据
- ✅ **分页支持**：数据量大时自动显示分页器
- ✅ **防抖优化**：搜索输入自动防抖，减少请求频率
- ✅ **灵活的数据转换**：支持自定义数据转换函数，适配不同后端接口
- ✅ **初始化选项**：支持通过 `initOptions` 直接传入选项，避免不必要的请求
- ✅ **自定义请求**：支持自定义请求函数，满足特殊业务需求
- ✅ **自动回显**：组件挂载时自动根据 `v-model` 值加载对应选项

## 使用方法

### 基础用法

```vue

<template>
  <select-remote
      v-model="selectedValue"
      remote-url="/api/users"
      placeholder="请选择用户"></select-remote>
</template>

<script setup lang="ts">
  import {ref} from 'vue'

  const selectedValue = ref(null)
</script>
```

### 自定义选项字段名

当后端返回的数据字段名不是 `value` 和 `label` 时，可以通过 `props` 配置：

```vue

<select-remote
    v-model="selectedValue"
    remote-url="/api/users"
    :props="{ value: 'id', label: 'username' }"></select-remote>
```

### 使用 POST 请求

默认使用 GET 请求，可以通过 `method` 属性切换为 POST：

```vue

<select-remote
    v-model="selectedValue"
    remote-url="/api/users"
    method="POST"></select-remote>
```

### 自定义数据转换

当后端返回的数据格式不符合默认解析规则时，可以使用 `transform` 函数自定义转换：

```vue

<template>
  <select-remote
      v-model="selectedValue"
      remote-url="/api/users"
      :transform="transformData"></select-remote>
</template>

<script setup lang="ts">
  const transformData = (response: any) => {
    // 自定义转换逻辑
    return {
      list: response.result?.items || [],
      total: response.result?.total || 0
    }
  }
</script>
```

### 传递额外筛选条件

通过 `filters` 属性传递额外的请求参数：

```vue

<select-remote
    v-model="selectedValue"
    remote-url="/api/users"
    :filters="{ status: 1, department: 'IT' }"></select-remote>
```

实际请求参数会包含：

```json
{
  "filters": {
    "status": 1,
    "department": "IT"
  },
  "keyword": "搜索关键词",
  "page": 1,
  "pageSize": 10
}
```

### 使用初始化选项（编辑回显）

在编辑场景下，如果已知选项数据，可以直接传入 `initOptions`，避免额外的请求：

```vue

<select-remote
    v-model="form.pid"
    remote-url="/api/roles"
    :init-options="{ value: 20, label: '管理员' }"></select-remote>
```

### 自定义请求函数

当需要特殊处理请求逻辑时（如添加认证、拦截器等），可以使用 `request-fn`：

```vue

<template>
  <select-remote
      v-model="selectedValue"
      remote-url="/api/users"
      :request-fn="customRequest"></select-remote>
</template>

<script setup lang="ts">
  import {customAuthRequest} from '@/utils/api'

  const customRequest = async (url: string, params: Record<string, any>) => {
    // 自定义请求逻辑
    return await customAuthRequest(url, params)
  }
</script>
```

### 完整示例

```vue

<template>
  <el-form-item label="上级角色" prop="pid">
    <select-remote
        v-model="form.pid"
        remote-url="/api/roles"
        method="POST"
        :props="{ value: 'id', label: 'name' }"
        :filters="{ status: 1 }"
        :init-options="initRole"
        :request-fn="roleRequestFn"
        placeholder="选择上级角色"></select-remote>
  </el-form-item>
</template>

<script setup lang="ts">
  import {ref} from 'vue'
  import {roleRequestFn} from '@/apis/role'

  const form = ref({
    pid: 20
  })

  const initRole = ref({
    id: 20,
    name: '管理员'
  })
</script>
```

## el-select 封装说明

本组件是对 Element Plus 的 `el-select` 组件的封装，在保留 `el-select` 原有功能的基础上，增强了远程搜索、分页等能力。

### 封装的 el-select 属性

组件内部固定设置了以下 `el-select` 属性，这些属性**不可通过 props 覆盖**：

| 属性               | 值              | 说明              |
|------------------|----------------|-----------------|
| `filterable`     | `true`         | 启用搜索功能          |
| `remote`         | `true`         | 启用远程搜索          |
| `remote-method`  | `remoteMethod` | 绑定远程搜索方法        |
| `loading`        | `loading`      | 绑定加载状态（组件内部管理）  |
| `value-on-clear` | `undefined`    | 清除时值为 undefined |
| `debounce`       | `300`          | 防抖时间 300ms      |

### 透传的 el-select 属性

组件支持直接传递 `el-select` 的所有属性（除了组件内部固定设置的属性）。

可以直接传递的属性包括但不限于：

- **基础属性**：`multiple`、`disabled`、`clearable`、`placeholder`、`size`、`collapse-tags`等
- **其他属性**：`reserve-keyword`、`default-first-option`、`popper-class`、`teleported` 等

**注意**：`props` 属性是 `el-select` 的特殊属性，用于配置选项的 `value` 和 `label` 字段名，需要单独传递。

**使用示例：**

```vue
<!-- 多选模式 - 直接传递 multiple 属性 -->
<select-remote
    v-model="selectedValues"
    remote-url="/api/users"
    multiple
    collapse-tags
    :props="{ value: 'id', label: 'name' }"></select-remote>

<!-- 禁用状态 - 直接传递 disabled 属性 -->
<select-remote
    v-model="selectedValue"
    remote-url="/api/users"
    disabled></select-remote>

<!-- 自定义尺寸和样式 - 直接传递属性 -->
<select-remote
    v-model="selectedValue"
    remote-url="/api/users"
    size="large"
    popper-class="custom-select-popper"
    placeholder="请选择用户">
</select-remote>

<!-- 保留搜索关键词 - 直接传递属性 -->
<select-remote
    v-model="selectedValue"
    remote-url="/api/users"
    reserve-keyword
    default-first-option>
</select-remote>
```

### 封装的插槽

组件内部使用了 `el-select` 的以下插槽：

#### 1. `#header` 插槽（头部）

用于显示当前搜索关键词，支持快速清除：

```vue

<template #header v-if="currentQuery">
  当前搜索：
  <el-tag closable @close="cleanQuery">{{ currentQuery }}</el-tag>
</template>
```

**说明**：当有搜索关键词时自动显示，点击标签的关闭按钮可清除搜索条件。

#### 2. `#footer` 插槽（底部）

用于显示分页器（当数据量超过 `pageSize` 时自动显示）：

```vue

<template #footer v-if="shouldShowPagination">
  <el-pagination ...></el-pagination>
</template>
```

**说明**：分页器仅在数据总量超过每页数量时显示，避免小数据量时的 UI 冗余。

### 选项渲染

组件内部使用 `el-option` 循环渲染选项，通过 `v-for` 遍历 `options` 数组：

```vue

<el-select
    :props="props.props"
    ...
>
  <el-option
      v-for="option in options"
      :key="option[valueKey]"
      :label="option[labelKey]"
      :value="option[valueKey]">
  </el-option>
</el-select>
```

**说明**：

- 选项数据通过 `v-for` 循环渲染为 `el-option` 组件
- `valueKey` 和 `labelKey` 通过 `props.value` 和 `props.label` 配置，默认为 `'value'` 和 `'label'`
- `props` 是 `el-select` 的特殊属性，用于配置选项的 `value` 和 `label` 字段名
- 选项数据来自远程接口返回，经过 `transformResponse` 转换后使用
- 通过 `props.value` 和 `props.label` 可以自定义选项字段名，默认为 `'value'` 和 `'label'`

### 与原生 el-select 的区别

| 特性     | 原生 el-select   | SelectRemote 组件 |
|--------|----------------|-----------------|
| 选项数据来源 | 静态 `el-option` | 动态远程接口          |
| 搜索功能   | 本地过滤           | 远程搜索（带防抖）       |
| 分页支持   | 不支持            | 自动分页（数据量大时显示）   |
| 数据转换   | 无              | 支持多种格式自动转换      |
| 初始化回显  | 需手动处理          | 自动根据 v-model 加载 |
| 请求管理   | 需手动实现          | 内置请求逻辑          |
| 搜索提示   | 无              | 显示当前搜索关键词       |

### 注意事项

1. **不可覆盖的属性**：`filterable`、`remote`、`remote-method`、`loading`、`value-on-clear`、`debounce` 这些属性由组件内部管理，无法通过
   `props` 覆盖。

2. **插槽使用**：组件内部已使用 `#header` 和 `#footer` 插槽，外部无法再使用这两个插槽。

3. **选项渲染**：选项通过组件内部逻辑动态渲染，外部无法直接使用 `el-option` 插槽。

4. **其他插槽**：`el-select` 的其他插槽（如 `prefix`、`empty` 等）目前未封装，如需使用可能需要扩展组件。

## API 参数

| 参数            | 说明                                                                | 类型                                                           | 默认值     | 必填 |
|---------------|-------------------------------------------------------------------|--------------------------------------------------------------|---------|----|
| `v-model`     | 绑定值                                                               | `any`                                                        | -       | 是  |
| `remote-url`  | 远程请求地址                                                            | `string`                                                     | -       | 是  |
| `props`       | el-select 的 props 配置，主要用于设置 value 和 label 字段名，也可传递其他 el-select 属性 | `SelectOptionProps`                                          | -       | 否  |
| `method`      | 请求方法                                                              | `'GET' \| 'POST'`                                            | `'GET'` | 否  |
| `transform`   | 响应数据转换函数                                                          | `(response: any) => { list: any[], total: number }`          | -       | 否  |
| `pageSize`    | 每页数量                                                              | `number`                                                     | `10`    | 否  |
| `filters`     | 额外的请求参数，会合并到请求参数中                                                 | `Record<string, any>`                                        | `{}`    | 否  |
| `requestFn`   | 自定义请求函数                                                           | `(url: string, params: Record<string, any>) => Promise<any>` | -       | 否  |
| `initOptions` | 初始化选项，用于回显                                                        | `Record<string, any> \| Record<string, any>[]`               | -       | 否  |

## 请求参数格式

组件会自动构建以下请求参数：

```typescript
{
    filters: {
        // ...filters 属性传入的额外参数
    }
,
    keyword: "搜索关键词",        // 用户输入的搜索关键词
        init_values
:
    "初始值",        // 当 useModelValue=true 时使用
        page
:
    1,                      // 当前页码
        pageSize
:
    10                  // 每页数量
}
```

## 响应数据格式

组件支持多种响应数据格式，按以下优先级解析：

1. **自定义转换函数**：如果提供了 `transform`，优先使用
2. **数组格式**：直接是数组 `[{value: 1, label: '选项1'}]`
3. **list 字段**：`{list: [], total: 100}`
4. **data 字段**：`{data: [], total: 100}`
5. **单个对象**：`{value: 1, label: '选项1'}`（用于根据值查询单个选项）

## 设计思路

### 1. 组件职责划分

组件遵循单一职责原则，专注于：

- 远程数据的获取和展示
- 搜索和分页的交互逻辑
- 数据格式的转换和适配

### 2. 性能优化

- **防抖机制**：搜索输入自动防抖 300ms，减少不必要的请求
- **初始化优化**：支持通过 `initOptions` 直接传入选项，避免编辑场景下的额外请求
- **条件请求**：当已有搜索关键词且新查询为空时，不执行请求，提升UI体验

### 3. 扩展性设计

- **自定义转换函数**：通过 `transform` 支持不同后端接口的数据格式
- **自定义请求函数**：通过 `requestFn` 支持特殊的请求逻辑（认证、拦截等）
- **灵活的字段映射**：通过 `props` 支持自定义选项的 value 和 label 字段名

### 4. 用户体验优化

- **自动回显**：组件挂载时自动根据 `v-model` 值加载对应选项
- **搜索提示**：显示当前搜索关键词，支持快速清除
- **分页显示**：数据量大时自动显示分页器，小数据量时不显示避免 UI 冗余
- **加载状态**：请求过程中显示 loading 状态，提供良好的反馈

### 5. 错误处理

- 请求失败时清空选项列表（初始值加载失败除外）
- 控制台输出错误信息，便于调试
- 优雅降级，不影响组件正常使用

## 注意事项

1. **搜索防抖**：组件内置 300ms 防抖，避免频繁请求
2. **分页参数**：固定使用 `page` 和 `pageSize` 作为分页参数名
3. **搜索参数**：固定使用 `keyword` 作为搜索关键词参数名
4. **初始值查询**：当使用 `useModelValue=true` 时，使用 `init_values` 参数查询
5. **数据格式**：如果后端返回格式特殊，建议使用 `transform` 函数转换

## 常见问题

### Q: 如何修改搜索参数名？

A: 目前搜索参数名固定为 `keyword`，如需修改可以：

1. 使用 `request-fn` 自定义请求函数
2. 在 `request-fn` 中修改参数名后再调用原始请求方法

### Q: 如何修改分页参数名？

A: 分页参数名固定为 `page` 和 `pageSize`，如需修改可以：

1. 使用 `request-fn` 自定义请求函数
2. 在 `request-fn` 中修改参数名后再调用原始请求方法

### Q: 如何支持多选？

A: 组件基于 `el-select`，可以通过直接传递 `multiple` 属性：

```vue

<select-remote
    v-model="selectedValues"
    remote-url="/api/users"
    multiple></select-remote>
```

### Q: 如何禁用组件？

A: 可以通过直接传递 `disabled` 属性：

```vue

<select-remote
    v-model="selectedValue"
    remote-url="/api/users"
    disabled></select-remote>
```

