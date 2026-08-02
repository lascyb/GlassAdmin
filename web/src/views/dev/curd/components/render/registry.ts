import {type Component, defineAsyncComponent} from 'vue'
import type {FormNumber} from "@/views/dev/curd/components/render/form/number/index.ts";
import type {FormInput} from "@/views/dev/curd/components/render/form/input/index.ts";


/** FORM_COMPONENTS 表单规范键 → 入参组件 */
export const FORM_COMPONENTS: Record<string, Component> = {
    number: defineAsyncComponent(() => import("./form/number/index.vue")),
}

export type FORM_TYPE = FormNumber | FormInput