<script setup lang="ts">
import { useSecurityRsa } from '@/pinia/security/rsa'
import type { RsaEncryptedPayload } from '@/composables/types'
import { ref } from 'vue'

const props = withDefaults(
    defineProps<{
        /** modelValue 明文密码 */
        modelValue?: string
        /** placeholder 占位符 */
        placeholder?: string
        /** disabled 禁用 */
        disabled?: boolean
        /** showPassword 是否显示切换明文 */
        showPassword?: boolean
        /** clearable 可清空 */
        clearable?: boolean
        /** autocomplete 自动完成属性 */
        autocomplete?: string
    }>(),
    {
        modelValue: '',
        showPassword: true,
        clearable: true,
        autocomplete: 'new-password',
    },
)

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const rsa = useSecurityRsa()
const inputRef = ref<{ focus: () => void } | null>(null)

const onInput = (val: string) => emit('update:modelValue', val)

/**
 * getEncryptedPayload 将当前明文 RSA 加密，空串返回空 payload
 */
const getEncryptedPayload = async (): Promise<RsaEncryptedPayload> => {
    const plain = props.modelValue?.trim() ?? ''
    if (!plain) return {}
    const enc = await rsa.encrypt(plain)
    if (!enc.serial || !enc.texts[0]) {
        throw new Error('获取加密公钥失败')
    }
    return {
        password: enc.texts[0],
        headers: { 'X-RSA-Serial': enc.serial },
    }
}

defineExpose({ getEncryptedPayload, focus: () => inputRef.value?.focus() })
</script>

<template>
    <el-input
        ref="inputRef"
        :model-value="modelValue"
        type="password"
        :placeholder="placeholder"
        :disabled="disabled"
        :show-password="showPassword"
        :clearable="clearable"
        :autocomplete="autocomplete"
        @update:model-value="onInput"
    />
</template>
