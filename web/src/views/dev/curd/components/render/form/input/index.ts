import type {Base} from "@/views/dev/curd/components/render/form/base.ts";
export const typeOptions = ["text","textarea","number","password","email","search","tel","url"]
export type FormInput = Base & {
    /** type 输入类型 */
    type: 'text' | 'textarea' | 'number' | 'password' | 'email' | 'search' | 'tel' | 'url'
    /** maxlength 最大输入长度 */
    maxLength?: number
    /** minlength 最小输入长度 */
    minLength?: number
    /** showWordLimit 是否显示统计字数, 只在 type 为 'text' 或 'textarea' 的时候生效 */
    showWordLimit: boolean
    /** placeholder 输入框占位文本 */
    placeholder?: string
    /** 默认值 */
    default?: number
}

export const defaultValue: FormInput= {
    enabled: true,
    required: false,
    type: 'text',
    maxLength: undefined,
    minLength: undefined,
    showWordLimit: false,
    default: undefined
};