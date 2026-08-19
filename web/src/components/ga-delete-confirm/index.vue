<script setup lang="ts">
import { Delete } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { ref } from 'vue'

const props = withDefaults(
    defineProps<{
        /** message 确认文案 */
        message?: string
        /** action 确认后执行（如删除接口） */
        action: () => void | Promise<unknown>
        /** successMsg 成功提示 */
        successMsg?: string
        /** afterSuccess 成功后回调（如 reload） */
        afterSuccess?: () => void | Promise<void>
    }>(),
    {
        message: '确定删除？',
        successMsg: '已删除',
    },
)

const visible = ref(false)
const loading = ref(false)

/** onConfirm 确认删除 */
const onConfirm = async () => {
    loading.value = true
    try {
        await props.action()
        visible.value = false
        ElMessage.success(props.successMsg)
        if (props.afterSuccess) await props.afterSuccess()
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? `删除失败：${e.message}` : '删除失败')
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <span class="inline-flex">
        <el-popover v-model:visible="visible" placement="right" :width="150" trigger="click">
            <p class="mb-3 text-sm leading-5">{{ message }}</p>
            <div class="flex justify-end gap-2">
                <el-button size="small" :disabled="loading" @click="visible = false">取消</el-button>
                <el-button size="small" type="danger" :loading="loading" @click="onConfirm">删除</el-button>
            </div>
            <template #reference>
                <el-button title="删除行" plain type="danger" :icon="Delete" size="small" />
            </template>
        </el-popover>
    </span>
</template>
