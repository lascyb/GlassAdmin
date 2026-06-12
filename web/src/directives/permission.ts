import { hasPermission } from '@/utils/permission'
import type { Directive, DirectiveBinding } from 'vue'

/**
 * applyPermission 无权限时隐藏元素（display:none，保留 DOM 便于调试）
 */
function applyPermission(el: HTMLElement, binding: DirectiveBinding<string | string[]>) {
    const allowed = hasPermission(binding.value)
    el.style.display = allowed ? '' : 'none'
}

/** vPermission 前端按钮权限指令，值：单个 path 或 path 数组（任一命中即显示） */
export const vPermission: Directive<HTMLElement, string | string[]> = {
    mounted(el, binding) {
        applyPermission(el, binding)
    },
    updated(el, binding) {
        applyPermission(el, binding)
    },
}

export default vPermission
