import type { FormRenderType } from '../types/form-render'
import type { FieldInferenceForm } from '../types/inference'
import type { NamedOption, NamedOptionGroup } from '../types/option'

/** FormRenderFormDefaults 表单控件默认参数（不含 render，不联动其它层） */
type FormRenderFormDefaults = Omit<FieldInferenceForm, 'render'>

/** FormRenderDef 表单渲染项定义（契约 + UI） */
export type FormRenderDef = {
    key?: string
    value: string
    label: string
    group?: string
    needEnum?: true // 需要本地枚举/选项
    needRemote?: true // 需要远程参数配置
    formDefaults?: FormRenderFormDefaults // 切换表单控件时仅写回 form 层默认参数
}

/** FORM_RENDER_DEFS 表单渲染定义列表 */
export const FORM_RENDER_DEFS = [
    { key: 'INPUT', value: 'input', label: '单行输入', group: '文本' },
    { key: 'TEXTAREA', value: 'textarea', label: '多行文本', group: '文本', formDefaults: { maxLength: 2000 } },
    { key: 'PASSWORD', value: 'password', label: '密码', group: '文本' },
    {
        key: 'NUMBER',
        value: 'number',
        label: '数字',
        group: '数值',
        formDefaults: { numberMin: 0, numberMax: 99999 },
    },
    { key: 'SWITCH', value: 'switch', label: '开关', group: '选择' },
    { key: 'RADIO', value: 'radio', label: '单选', group: '选择', needEnum: true },
    { key: 'SELECT', value: 'select', label: '下拉单选', group: '选择', needEnum: true },
    { key: 'MULTI_SELECT', value: 'multi_select', label: '下拉多选', group: '选择', needEnum: true },
    { key: 'CHECKBOX', value: 'checkbox', label: '多选', group: '选择', needEnum: true },
    { key: 'REMOTE_SELECT', value: 'remote_select', label: '远程下拉', group: '选择', needRemote: true },
    {
        key: 'REMOTE_MULTI_SELECT',
        value: 'remote_multi_select',
        label: '远程多选',
        group: '选择',
        needRemote: true,
    },
    { key: 'DATE', value: 'date', label: '日期', group: '日期时间' },
    { key: 'DATETIME', value: 'datetime', label: '日期时间', group: '日期时间' },
    { key: 'TIME', value: 'time', label: '时间', group: '日期时间' },
    { key: 'IMAGE', value: 'image', label: '图片', group: '媒体与其它' },
    { key: 'FILE', value: 'file', label: '文件', group: '媒体与其它' },
    { key: 'COLOR', value: 'color', label: '颜色', group: '媒体与其它' },
    { key: 'READONLY', value: 'readonly', label: '只读' },
    { key: 'HIDDEN', value: 'hidden', label: '隐藏' },
    { key: 'CUSTOM', value: 'custom', label: '自定义', group: '高级' },
] as const satisfies readonly FormRenderDef[]

const FORM_GROUP_ORDER = ['文本', '数值', '选择', '日期时间', '媒体与其它', '高级'] as const

const FORM_SCHEMA_DEFS = FORM_RENDER_DEFS.filter((d) => 'key' in d)

const FORM_UI_DEFS = FORM_RENDER_DEFS.filter((d) => 'group' in d)

/** formRenderDefOf 取表单控件完整定义 */
export const formRenderDefOf = (render: FormRenderType): FormRenderDef | undefined =>
    FORM_SCHEMA_DEFS.find((d) => d.value === render) as FormRenderDef | undefined

/** formDefaultsOf 取表单控件 form 层默认参数 */
export const formDefaultsOf = (render: FormRenderType): FormRenderFormDefaults | undefined =>
    formRenderDefOf(render)?.formDefaults

/** FORM_RENDER 表单控件渲染类型 */
export const FORM_RENDER = Object.fromEntries(
    FORM_SCHEMA_DEFS.map((d) => [d.key, d.value]),
) as {
    [K in Extract<(typeof FORM_RENDER_DEFS)[number], { key: string }>['key']]: Extract<
        (typeof FORM_RENDER_DEFS)[number],
        { key: K }
    >['value']
}

/** FORM_RENDER_TYPE_LABEL 表单控件中文名 */
export const FORM_RENDER_TYPE_LABEL = Object.fromEntries(
    FORM_SCHEMA_DEFS.map((d) => [d.value, d.label]),
) as Record<FormRenderType, string>

/** FORM_RENDER_OPTIONS 表单控件选项 */
export const FORM_RENDER_OPTIONS = FORM_SCHEMA_DEFS.map(({ value, label }) => ({ value, label }))

/** FORM_CONTROL_DEFS 表单控件 UI 定义 */
export const FORM_CONTROL_DEFS = FORM_UI_DEFS

/** FORM_CONTROL_GROUPS 表单控件选项（分组） */
export const FORM_CONTROL_GROUPS: NamedOptionGroup[] = FORM_GROUP_ORDER.map((label) => ({
    label,
    options: FORM_UI_DEFS.filter((d) => 'group' in d && d.group === label).map(({ value, label: l }) => ({
        value,
        label: l,
    })),
})).filter((g) => g.options.length > 0)

/** FORM_CONTROL_OPTIONS 扁平表单控件选项 */
export const FORM_CONTROL_OPTIONS: NamedOption[] = FORM_UI_DEFS.map(({ value, label }) => ({ value, label }))

/** FORM_CONTROL_NEED_ENUM 需要本地枚举/选项的表单控件 */
export const FORM_CONTROL_NEED_ENUM = FORM_UI_DEFS.filter((d) => 'needEnum' in d).map((d) => d.value)

/** FORM_CONTROL_NEED_REMOTE 需要远程参数配置的表单控件 */
export const FORM_CONTROL_NEED_REMOTE = FORM_UI_DEFS.filter((d) => 'needRemote' in d).map((d) => d.value)
