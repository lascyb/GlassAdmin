import { FORM_RENDER } from '../constants/form-render'

/** FormFieldParamBase 表单字段公共参数 */
type FormFieldParamBase = {
    /** required 是否必填 */
    required?: boolean
}

/** FormInputFieldParam input 单行输入参数 */
export type FormInputFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.INPUT
    /** maxLength 输入框最大长度（UI，可与 column.length 不同） */
    maxLength?: number
}

/** FormPasswordFieldParam password 密码输入参数 */
export type FormPasswordFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.PASSWORD
    /** maxLength 输入框最大长度（UI，可与 column.length 不同） */
    maxLength?: number
}

/** FormTextareaFieldParam textarea 多行文本参数 */
export type FormTextareaFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.TEXTAREA
    /** maxLength 最大长度 */
    maxLength?: number
}

/** FormNumberFieldParam number 数字输入参数 */
export type FormNumberFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.NUMBER
    /** numberMin 最小值 */
    numberMin?: number
    /** numberMax 最大值 */
    numberMax?: number
    /** numberStep 步进 */
    numberStep?: number
}

/** FormSwitchFieldParam switch 开关参数 */
export type FormSwitchFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.SWITCH
}

/** FormRadioFieldParam radio 单选参数 */
export type FormRadioFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.RADIO
}

/** FormSelectFieldParam select 下拉选择参数 */
export type FormSelectFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.SELECT
}

/** FormCheckboxFieldParam checkbox 多选参数 */
export type FormCheckboxFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.CHECKBOX
}

/** FormRemoteSelectFieldParam remote_select 远程下拉参数 */
export type FormRemoteSelectFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.REMOTE_SELECT
}

/** FormRemoteMultiSelectFieldParam remote_multi_select 远程多选参数 */
export type FormRemoteMultiSelectFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.REMOTE_MULTI_SELECT
}

/** FormMultiSelectFieldParam multi_select 静态多选参数 */
export type FormMultiSelectFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.MULTI_SELECT
}

/** FormDateFieldParam date 日期选择参数 */
export type FormDateFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.DATE
}

/** FormDatetimeFieldParam datetime 日期时间选择参数 */
export type FormDatetimeFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.DATETIME
}

/** FormTimeFieldParam time 时间选择参数 */
export type FormTimeFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.TIME
}

/** FormColorFieldParam color 颜色选择参数 */
export type FormColorFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.COLOR
}

/** FormImageFieldParam image_upload 图片上传参数 */
export type FormImageFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.IMAGE_UPLOAD
}

/** FormFileFieldParam file 文件上传参数 */
export type FormFileFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.FILE
}

/** FormReadonlyFieldParam readonly 只读展示参数 */
export type FormReadonlyFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.READONLY
    /** maxLength 最大长度 */
    maxLength?: number
}

/** FormHiddenFieldParam hidden 隐藏字段参数 */
export type FormHiddenFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.HIDDEN
}

/** FormCustomFieldParam custom 自定义控件参数 */
export type FormCustomFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.CUSTOM
}

/**
 * FormFieldRenderParam 表单渲染参数联合类型
 * 用途：edit.vue 各控件生成配置；按 render 判别字段收窄为对应参数类型
 */
export type FormFieldRenderParam =
    | FormInputFieldParam
    | FormPasswordFieldParam
    | FormTextareaFieldParam
    | FormNumberFieldParam
    | FormSwitchFieldParam
    | FormRadioFieldParam
    | FormSelectFieldParam
    | FormRemoteSelectFieldParam
    | FormRemoteMultiSelectFieldParam
    | FormMultiSelectFieldParam
    | FormCheckboxFieldParam
    | FormDateFieldParam
    | FormDatetimeFieldParam
    | FormTimeFieldParam
    | FormColorFieldParam
    | FormImageFieldParam
    | FormFileFieldParam
    | FormReadonlyFieldParam
    | FormHiddenFieldParam
    | FormCustomFieldParam

/** FormFieldRenderParamOf 按 render 取对应参数类型 */
export type FormFieldRenderParamOf<R extends FormFieldRenderParam['render']> = Extract<
    FormFieldRenderParam,
    { render: R }
>

/** FormFieldRenderParamMap 按 render 索引的表单参数类型 */
export type FormFieldRenderParamMap = {
    [FORM_RENDER.INPUT]: FormInputFieldParam
    [FORM_RENDER.PASSWORD]: FormPasswordFieldParam
    [FORM_RENDER.TEXTAREA]: FormTextareaFieldParam
    [FORM_RENDER.NUMBER]: FormNumberFieldParam
    [FORM_RENDER.SWITCH]: FormSwitchFieldParam
    [FORM_RENDER.RADIO]: FormRadioFieldParam
    [FORM_RENDER.SELECT]: FormSelectFieldParam
    [FORM_RENDER.REMOTE_SELECT]: FormRemoteSelectFieldParam
    [FORM_RENDER.MULTI_SELECT]: FormMultiSelectFieldParam
    [FORM_RENDER.CHECKBOX]: FormCheckboxFieldParam
    [FORM_RENDER.REMOTE_MULTI_SELECT]: FormRemoteMultiSelectFieldParam
    [FORM_RENDER.DATE]: FormDateFieldParam
    [FORM_RENDER.DATETIME]: FormDatetimeFieldParam
    [FORM_RENDER.TIME]: FormTimeFieldParam
    [FORM_RENDER.COLOR]: FormColorFieldParam
    [FORM_RENDER.IMAGE_UPLOAD]: FormImageFieldParam
    [FORM_RENDER.FILE]: FormFileFieldParam
    [FORM_RENDER.READONLY]: FormReadonlyFieldParam
    [FORM_RENDER.HIDDEN]: FormHiddenFieldParam
    [FORM_RENDER.CUSTOM]: FormCustomFieldParam
}
