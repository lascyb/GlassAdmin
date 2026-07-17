import { FORM_RENDER } from '../constants/form-render'

/** FormRenderType 表单控件渲染类型 */
export type FormRenderType = (typeof FORM_RENDER)[keyof typeof FORM_RENDER]
