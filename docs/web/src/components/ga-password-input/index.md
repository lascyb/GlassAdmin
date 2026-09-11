# ga-password-input

源码：[web/src/components/ga-password-input/index.vue](../../../../../web/src/components/ga-password-input/index.vue)

## 作用

`el-input type="password"` 薄封装。暴露 `getEncryptedPayload()`：用 [useSecurityRsa](../../../../../web/src/pinia/security/rsa.ts) 加密当前明文，返回 `{ password, headers: { 'X-RSA-Serial' } }`（空串返回 `{}`）。另暴露 `focus()`。

列表保存更常见的是把明文交给 `post(..., { encryptFields: ['password'] })`，由 [request.ts](../../apis/request.md) 拦截器加密。本组件用于表单内密码框。

```ts
import GaPasswordInput from '@/components/ga-password-input/index.vue'
```

用法：[web/src/views/system/auth/admin/edit.vue](../../../../../web/src/views/system/auth/admin/edit.vue)、[profile.vue](../../../../../web/src/views/system/account/profile.vue)。

## Props / 事件 / 插槽

| 属性 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `modelValue` | `string` | `''` | 明文；配合 `update:modelValue` |
| `placeholder` | `string` | — | 占位 |
| `disabled` | `boolean` | — | 禁用 |
| `showPassword` | `boolean` | `true` | 显示明文切换 |
| `clearable` | `boolean` | `true` | 可清空 |
| `autocomplete` | `string` | `'new-password'` | 自动完成 |

| 事件 | 载荷 |
|---|---|
| `update:modelValue` | `string` |

无插槽。

## 最小示例

```vue
<script setup lang="ts">
import GaPasswordInput from '@/components/ga-password-input/index.vue'
const password = ref('')
</script>
<template>
  <ga-password-input v-model="password" placeholder="至少 6 位" />
</template>
```

## 逃生舱

```vue
<el-input v-model="password" type="password" show-password autocomplete="new-password" />
```

RSA 交给 `post(url, { password }, { encryptFields: ['password'] })`。见 [escape-hatches.md](../../../../escape-hatches.md)。
