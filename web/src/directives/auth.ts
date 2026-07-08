import { hasPermission } from '@/utils/permission'
import type { Directive, DirectiveBinding } from 'vue'

/**
 * applyAuth 无权限时隐藏元素（display:none，保留 DOM 便于调试）
 */
function applyAuth(el: HTMLElement, binding: DirectiveBinding<string | string[]>) {
    const allowed = hasPermission(binding.value)
    el.style.display = allowed ? '' : 'none'
}

/** vAuth 前端按钮权限指令，值：单个 path 或 path 数组（任一命中即显示） */
export const vAuth: Directive<HTMLElement, string | string[]> = {
    mounted(el, binding) {
        applyAuth(el, binding)
    },
    updated(el, binding) {
        applyAuth(el, binding)
    },
}

export default vAuth
