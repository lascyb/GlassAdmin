import { ElMessage } from 'element-plus'
import { ref } from 'vue'

/**
 * useDialogForm 弹窗表单通用状态：show、loading、saving、open/close、submit 包装
 */
export function useDialogForm() {
    const show = ref(false)
    const loading = ref(false)
    const saving = ref(false)
    const id = ref<number | undefined>()

    /** open 打开弹窗，传入 id 表示编辑 */
    const open = (editId?: number) => {
        id.value = editId
        show.value = true
    }

    /** close 关闭弹窗 */
    const close = () => {
        show.value = false
    }

    /**
     * submit 包装保存逻辑：saving 态 + 错误提示
     */
    const submit = async (fn: () => void | Promise<void>, errorMsg = '保存失败') => {
        saving.value = true
        try {
            await fn()
        } catch (e: unknown) {
            ElMessage.error(e instanceof Error ? e.message : errorMsg)
            throw e
        } finally {
            saving.value = false
        }
    }

    return { show, loading, saving, id, open, close, submit }
}
