# ga-password-input

源码：[web/src/components/ga-password-input/index.vue](../../../../../web/src/components/ga-password-input/index.vue)

## 作用

`el-input type="password"` 薄封装。暴露 `getEncryptedPayload()`：用 [useSecurityRsa](../../../../../web/src/pinia/security/rsa.ts) 加密当前明文，返回 `{ password, headers: { 'X-RSA-Serial' } }`（空串返回 `{}`）。另暴露 `focus()`。

```ts
import GaPasswordInput from '@/components/ga-password-input/index.vue'
```

## 推荐使用场景

- 表单里的密码框，且可能调用 `getEncryptedPayload()` 在提交前加密。
- 多数保存接口仍走 `post(..., { encryptFields: ['password'] })`（见 [request.md](../../apis/request.md)）；本组件只负责输入，不必强行用 `getEncryptedPayload`。
- 不需要封装：`<el-input type="password">`。

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

## 已使用场景

- [web/src/views/system/auth/admin/edit.vue](../../../../../web/src/views/system/auth/admin/edit.vue)
- [web/src/views/system/account/profile.vue](../../../../../web/src/views/system/account/profile.vue)

登录页未使用本组件（[login.vue](../../../../../web/src/views/login.vue) 自行 `el-input` + `encryptFields`）。

## 逃生舱

`<el-input type="password">`；RSA 用 `encryptFields`。见 [escape-hatches.md](../../../../escape-hatches.md)。
