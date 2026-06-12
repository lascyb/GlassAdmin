<script setup lang="ts">
const show = defineModel<boolean>({ required: true })

withDefaults(
    defineProps<{
        /** title 弹窗标题 */
        title?: string
        /** loading 内容区骨架屏 */
        loading?: boolean
        /** saving 保存按钮 loading */
        saving?: boolean
        /** width 弹窗宽度 */
        width?: string
        /** confirmText 确认按钮文案 */
        confirmText?: string
        /** cancelText 取消按钮文案 */
        cancelText?: string
        /** destroyOnClose 关闭时销毁 */
        destroyOnClose?: boolean
    }>(),
    {
        title: '',
        loading: false,
        saving: false,
        width: '520px',
        confirmText: '保存',
        cancelText: '取消',
        destroyOnClose: true,
    },
)

const emit = defineEmits<{ confirm: [] }>()

const onConfirm = () => emit('confirm')
</script>

<template>
    <el-dialog
        v-model="show"
        draggable
        :title="title"
        :width="width"
        :destroy-on-close="destroyOnClose"
        lock-scroll
        top="8vh"
    >
        <el-skeleton
            :loading="loading"
            animated
            :rows="5"
            :throttle="{ leading: 300, trailing: 300, initVal: true }"
        >
            <slot />
        </el-skeleton>
        <template #footer>
            <slot name="footer">
                <el-button @click="show = false">{{ cancelText }}</el-button>
                <el-button type="primary" :loading="saving" @click="onConfirm">{{ confirmText }}</el-button>
            </slot>
        </template>
    </el-dialog>
</template>
