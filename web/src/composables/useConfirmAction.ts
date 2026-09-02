import { showError } from '@/apis/request'
import { ElMessage, ElMessageBox } from 'element-plus'

export type ConfirmActionOptions = {
    /** title 确认框标题 */
    title?: string
    /** successMsg 成功提示，默认「操作成功」 */
    successMsg?: string
    /** errorMsg 失败提示前缀 */
    errorMsg?: string
    /** onSuccess 成功后回调（如 reload） */
    onSuccess?: () => void | Promise<void>
}

/**
 * useConfirmAction ElMessageBox.confirm → action → ElMessage → 可选 onSuccess reload
 */
export function useConfirmAction(defaults: ConfirmActionOptions = {}) {
    /**
     * confirm 弹出确认框并执行 action
     */
    const confirm = async (
        message: string,
        action: () => void | Promise<unknown>,
        options: ConfirmActionOptions = {},
    ) => {
        const opts = { ...defaults, ...options }
        try {
            await ElMessageBox.confirm(message, opts.title ?? '确认', { type: 'warning' })
        } catch {
            return false
        }
        try {
            await action()
            ElMessage.success(opts.successMsg ?? '操作成功')
            if (opts.onSuccess) await opts.onSuccess()
            return true
        } catch (e: unknown) {
            showError(e, opts.errorMsg ?? '操作失败')
            return false
        }
    }

    return { confirm }
}
