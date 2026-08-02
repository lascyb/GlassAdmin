import type {Base} from "@/views/dev/curd/components/render/form/base.ts";

export type FormNumber = Base & {
    /** min 最小值 */
    min?: number
    /** max 最大值 */
    max?: number
    /** step 步进 */
    step?: number
    /** 精度 */
    precision?: number
    /** 默认值 */
    default?: number
}

export const defaultValue: FormNumber= {
    enabled: true,
    required: false,
    max: Number.MAX_SAFE_INTEGER,
    min: Number.MIN_SAFE_INTEGER,
    precision: undefined,
    step: 1,
    default: undefined
};