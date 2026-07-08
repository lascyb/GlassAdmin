<script setup lang="ts">
import type { GaMonacoEditorProps } from '@/components/ga-monaco-editor/types'
import type { editor } from 'monaco-editor'
import * as monaco from 'monaco-editor'
import { markRaw, onMounted, onUnmounted, shallowRef, useTemplateRef, watch } from 'vue'

const modelValue = defineModel<string>({ required: true })

const props = withDefaults(defineProps<GaMonacoEditorProps>(), {
    theme: 'vs-dark',
    height: '200px',
    options: () => ({}),
    initOptions: () => ({}),
})

const container = useTemplateRef('container')
const instance = shallowRef<editor.IStandaloneCodeEditor>()

/** updateModel 同步编辑器内容到 v-model */
const updateModel = () => {
    if (!instance.value) return
    const value = instance.value.getValue()
    if (value !== modelValue.value) {
        modelValue.value = value
    }
}

/**
 * onMounted 创建单实例编辑器并绑定输入联动
 */
onMounted(() => {
    instance.value = markRaw(
        monaco.editor.create(container.value as HTMLElement, {
            ...props.initOptions,
            ...props.options,
            ...(props.theme && { theme: props.theme }),
            ...(props.language && { language: props.language }),
            value: modelValue.value,
        }),
    )
    instance.value.onDidChangeModelContent(() => updateModel())
})

/**
 * onUnmounted 释放编辑器实例避免内存泄漏
 */
onUnmounted(() => {
    instance.value?.dispose()
})

/**
 * watch props.options 深度监听运行期配置并调用 updateOptions
 */
watch(
    () => props.options,
    (newOptions) => {
        if (instance.value && newOptions) {
            instance.value.updateOptions(newOptions)
        }
    },
    { deep: true },
)

/**
 * watch props.theme 通过 monaco.editor.setTheme 支持全局主题切换
 */
watch(
    () => props.theme,
    (newTheme) => {
        if (newTheme) {
            monaco.editor.setTheme(newTheme)
        }
    },
    { immediate: true },
)

/**
 * watch props.language 通过 setModelLanguage 支持外部语言切换
 */
watch(
    () => props.language,
    (newLanguage) => {
        if (!instance.value || !newLanguage) return
        const model = instance.value.getModel()
        if (model) {
            monaco.editor.setModelLanguage(model, newLanguage)
        }
    },
)

defineExpose({
    /** getInstance 获取底层 Monaco editor 实例 */
    getInstance: () => instance.value,
})
</script>

<template>
    <div ref="container" :style="{ height }" class="w-full" />
</template>
