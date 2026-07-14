import { FORM_RENDER } from './types'

/** FormFieldParamBase 表单字段公共参数 */
type FormFieldParamBase = {
    /** required 是否必填 */
    required?: boolean
}

/** FormInputFieldParam input 单行输入参数 */
export type FormInputFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.INPUT
    /** placeholder 占位符 */
    placeholder?: string
    /** maxLength 输入框最大长度（UI，可与 column.length 不同） */
    maxLength?: number
}

/** FormTextareaFieldParam textarea 多行文本参数 */
export type FormTextareaFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.TEXTAREA
    /** placeholder 占位符 */
    placeholder?: string
    /** maxLength 最大长度 */
    maxLength?: number
}

/** FormNumberFieldParam number 数字输入参数 */
export type FormNumberFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.NUMBER
    /** placeholder 占位符 */
    placeholder?: string
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
    /** statusMapKey constants 中 StatusMap 键名 */
    statusMapKey?: string
}

/** FormSelectFieldParam select 下拉选择参数 */
export type FormSelectFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.SELECT
    /** statusMapKey constants 中 StatusMap 键名 */
    statusMapKey?: string
    /** placeholder 占位符 */
    placeholder?: string
}

/** FormRemoteSelectFieldParam remote_select 远程下拉参数 */
export type FormRemoteSelectFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.REMOTE_SELECT
    /** optionsKey 远程 list API（如 example.Category/list） */
    optionsKey?: string
    /** placeholder 占位符 */
    placeholder?: string
}

/** FormRemoteMultiSelectFieldParam remote_multi_select 远程多选参数 */
export type FormRemoteMultiSelectFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.REMOTE_MULTI_SELECT
    /** optionsKey 远程 list API（如 example.Tag/list） */
    optionsKey?: string
    /** placeholder 占位符 */
    placeholder?: string
}

/** FormMultiSelectFieldParam multi_select 静态多选参数 */
export type FormMultiSelectFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.MULTI_SELECT
    /** optionsKey constants 中静态选项键名 */
    optionsKey?: string
    /** placeholder 占位符 */
    placeholder?: string
}

/** FormDateFieldParam date 日期选择参数 */
export type FormDateFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.DATE
    /** placeholder 占位符 */
    placeholder?: string
}

/** FormDatetimeFieldParam datetime 日期时间选择参数 */
export type FormDatetimeFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.DATETIME
    /** placeholder 占位符 */
    placeholder?: string
}

/** FormColorFieldParam color 颜色选择参数 */
export type FormColorFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.COLOR
}

/** FormImageFieldParam image 图片上传参数 */
export type FormImageFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.IMAGE
}

/** FormReadonlyFieldParam readonly 只读展示参数 */
export type FormReadonlyFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.READONLY
    /** placeholder 占位符 */
    placeholder?: string
    /** maxLength 最大长度 */
    maxLength?: number
}

/** FormHiddenFieldParam hidden 隐藏字段参数 */
export type FormHiddenFieldParam = FormFieldParamBase & {
    /** render 表单控件类型 */
    render: typeof FORM_RENDER.HIDDEN
}

/**
 * FormFieldRenderParam 表单渲染参数联合类型
 * 用途：edit.vue 各控件生成配置；按 render 判别字段收窄为对应参数类型
 */
export type FormFieldRenderParam =
    | FormInputFieldParam
    | FormTextareaFieldParam
    | FormNumberFieldParam
    | FormSwitchFieldParam
    | FormRadioFieldParam
    | FormSelectFieldParam
    | FormRemoteSelectFieldParam
    | FormRemoteMultiSelectFieldParam
    | FormMultiSelectFieldParam
    | FormDateFieldParam
    | FormDatetimeFieldParam
    | FormColorFieldParam
    | FormImageFieldParam
    | FormReadonlyFieldParam
    | FormHiddenFieldParam

/** FormFieldRenderParamOf 按 render 取对应参数类型 */
export type FormFieldRenderParamOf<R extends FormFieldRenderParam['render']> = Extract<
    FormFieldRenderParam,
    { render: R }
>

/** FormFieldRenderParamMap 按 render 索引的表单参数类型 */
export type FormFieldRenderParamMap = {
    [FORM_RENDER.INPUT]: FormInputFieldParam
    [FORM_RENDER.TEXTAREA]: FormTextareaFieldParam
    [FORM_RENDER.NUMBER]: FormNumberFieldParam
    [FORM_RENDER.SWITCH]: FormSwitchFieldParam
    [FORM_RENDER.RADIO]: FormRadioFieldParam
    [FORM_RENDER.SELECT]: FormSelectFieldParam
    [FORM_RENDER.REMOTE_SELECT]: FormRemoteSelectFieldParam
    [FORM_RENDER.MULTI_SELECT]: FormMultiSelectFieldParam
    [FORM_RENDER.REMOTE_MULTI_SELECT]: FormRemoteMultiSelectFieldParam
    [FORM_RENDER.DATE]: FormDateFieldParam
    [FORM_RENDER.DATETIME]: FormDatetimeFieldParam
    [FORM_RENDER.COLOR]: FormColorFieldParam
    [FORM_RENDER.IMAGE]: FormImageFieldParam
    [FORM_RENDER.READONLY]: FormReadonlyFieldParam
    [FORM_RENDER.HIDDEN]: FormHiddenFieldParam
}
