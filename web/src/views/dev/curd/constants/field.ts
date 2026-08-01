import type { CrudFieldSchema } from '../types/field'
import { isLayerEnabled } from '../types/field'
import type { FieldInference } from '../types/inference'
import type { NamedOption, NamedOptionGroup } from '../types/option'

/** FieldTypeDef 字段模板项定义（UI 初始选择，独立于 form-render） */
type FieldTypeDef = {
    value: string
    label: string
    group: string
    inference: FieldInference // 选择模板时推测 column/relation/list/search/form
    needEnum?: true // 需要本地枚举/选项
    needRemote?: true // 需要远程参数配置
}

/** FIELD_TYPE_DEFS 字段模板定义列表 */
export const FIELD_TYPE_DEFS = [
    {
        value: 'text',
        label: '单行文本',
        group: '文本',
        inference: {
            column: { type: 'varchar', length: 255, nullable: true },
            list: { render: 'text', minWidth: 120 },
            search: { keyword: true },
            form: { render: 'input' },
        },
    },
    {
        value: 'textarea',
        label: '多行文本',
        group: '文本',
        inference: {
            column: { type: 'text', nullable: true },
            list: false,
            search: false,
            form: { render: 'textarea', maxLength: 2000 },
        },
    },
    {
        value: 'password',
        label: '密码',
        group: '文本',
        inference: {
            column: { type: 'varchar', length: 255, nullable: true },
            list: { render: 'text', minWidth: 120 },
            search: { keyword: true },
            form: { render: 'password' },
        },
    },
    {
        value: 'number',
        label: '数字',
        group: '数值',
        inference: {
            column: { type: 'int', nullable: false, default: 0 },
            list: { render: 'number', width: 80 },
            search: { advanced: true, render: 'number_range' },
            form: { render: 'number', numberMin: 0, numberMax: 99999 },
        },
    },
    {
        value: 'amount',
        label: '金额',
        group: '数值',
        inference: {
            column: { type: 'decimal', precision: 12, scale: 2, nullable: false, default: 0 },
            list: { render: 'number', width: 100 },
            search: { advanced: true, render: 'number_range' },
            form: { render: 'number', numberMin: 0, numberMax: 9999999, numberStep: 0.01 },
        },
    },
    {
        value: 'switch',
        label: '开关',
        group: '选择',
        inference: {
            column: { type: 'tinyint', nullable: false, default: 0 },
            list: { render: 'bool', width: 72 },
            search: { advanced: true, render: 'bool' },
            form: { render: 'switch' },
        },
    },
    {
        value: 'radio',
        label: '单选',
        group: '选择',
        needEnum: true,
        inference: {
            column: { type: 'tinyint', nullable: false, default: 1 },
            list: { render: 'enum_tag', width: 88 },
            search: { advanced: true, render: 'select' },
            form: { render: 'radio' },
        },
    },
    {
        value: 'checkbox',
        label: '多选',
        group: '选择',
        needEnum: true,
        inference: {
            column: { type: 'json', nullable: true },
            list: { render: 'tags', minWidth: 160 },
            search: { advanced: true },
            form: { render: 'checkbox' },
        },
    },
    {
        value: 'select',
        label: '下拉单选',
        group: '选择',
        needEnum: true,
        inference: {
            column: { type: 'tinyint', nullable: false, default: 1 },
            list: { render: 'enum_tag', width: 88 },
            search: { advanced: true, render: 'select' },
            form: { render: 'select' },
        },
    },
    {
        value: 'multi_select',
        label: '下拉多选',
        group: '选择',
        needEnum: true,
        inference: {
            column: { type: 'json', nullable: true },
            list: { render: 'tags', minWidth: 160 },
            search: { advanced: true },
            form: { render: 'multi_select' },
        },
    },
    {
        value: 'remote_select',
        label: '远程下拉',
        group: '选择',
        needRemote: true,
        inference: {
            column: { type: 'bigint', unsigned: true, nullable: true, default: null },
            relation: { type: 'belongs_to' },
            list: { render: 'enum_tag', width: 110 },
            search: { advanced: true, render: 'remote_select' },
            form: { render: 'remote_select' },
        },
    },
    {
        value: 'remote_multi_select',
        label: '远程多选',
        group: '选择',
        needRemote: true,
        inference: {
            column: false,
            relation: { type: 'belongs_to_many', needPivot: true },
            list: { render: 'tags', minWidth: 160 },
            search: { advanced: true, render: 'remote_multi_select' },
            form: { render: 'remote_multi_select' },
        },
    },
    {
        value: 'date',
        label: '日期',
        group: '日期时间',
        inference: {
            column: { type: 'date', nullable: true, default: null },
            list: { render: 'text', width: 120 },
            search: { advanced: true, render: 'date_range' },
            form: { render: 'date' },
        },
    },
    {
        value: 'datetime',
        label: '日期时间(时间戳)',
        group: '日期时间',
        inference: {
            column: { type: 'bigint', nullable: true, default: null },
            list: { render: 'datetime', width: 170 },
            search: { advanced: true, render: 'datetime_range' },
            form: { render: 'datetime' },
        },
    },
    {
        value: 'time',
        label: '时间(时间戳)',
        group: '日期时间',
        inference: {
            column: { type: 'bigint', nullable: true, default: null },
            list: { render: 'datetime', width: 170 },
            search: { advanced: true, render: 'datetime_range' },
            form: { render: 'time' },
        },
    },
    {
        value: 'image',
        label: '图片',
        group: '媒体与其它',
        inference: {
            column: { type: 'varchar', length: 255, nullable: true, default: null },
            list: { render: 'image', width: 88 },
            search: false,
            form: { render: 'image_upload' },
        },
    },
    {
        value: 'file',
        label: '文件',
        group: '媒体与其它',
        inference: {
            column: { type: 'varchar', length: 255, nullable: true, default: null },
            list: { render: 'image', width: 88 },
            search: false,
            form: { render: 'file' },
        },
    },
    {
        value: 'color',
        label: '颜色',
        group: '媒体与其它',
        inference: {
            column: { type: 'varchar', length: 255, nullable: true },
            list: { render: 'color', width: 88 },
            search: false,
            form: { render: 'color' },
        },
    },
    {
        value: 'custom',
        label: '自定义',
        group: '高级',
        inference: {
            column: { type: 'varchar', length: 255, nullable: true },
            list: false,
            search: false,
            form: { render: 'custom' },
        },
    },
    {
        value: 'primary_key',
        label: '自增主键',
        group: '系统',
        inference: {
            column: { type: 'bigint', unsigned: true, primary: true, autoIncrement: true, nullable: false },
            list: { render: 'number', width: 80 },
            search: false,
            form: false,
        },
    },
    {
        value: 'uuid',
        label: 'UUID',
        group: '系统',
        inference: {
            column: { type: 'varchar', length: 36, nullable: false, unique: true },
            list: { render: 'text', minWidth: 280 },
            search: { keyword: true },
            form: false,
        },
    },
    {
        value: 'create_time',
        label: '创建时间(时间戳)',
        group: '系统',
        inference: {
            column: { type: 'bigint', nullable: false, default: null },
            list: { render: 'datetime', width: 170 },
            search: { advanced: true, render: 'datetime_range' },
            form: false,
        },
    },
    {
        value: 'update_time',
        label: '更新时间(时间戳)',
        group: '系统',
        inference: {
            column: { type: 'bigint', nullable: false, default: null },
            list: { render: 'datetime', width: 170 },
            search: { advanced: true, render: 'datetime_range' },
            form: false,
        },
    },
] as const satisfies readonly FieldTypeDef[]

const FIELD_GROUP_ORDER = ['系统', '文本', '数值', '选择', '日期时间', '媒体与其它', '高级'] as const

/** fieldTypeDefOf 取字段模板定义 */
export const fieldTypeDefOf = (type: string): FieldTypeDef | undefined =>
    FIELD_TYPE_DEFS.find((d) => d.value === type)

/** matchSystemFieldType 系统字段（form:false）精确匹配 */
const matchSystemFieldType = (value: string, field: CrudFieldSchema): boolean => {
    if (!isLayerEnabled(field.column)) return false
    const p = field.prop.toLowerCase()
    switch (value) {
        case 'primary_key':
            return field.column.primary === true && field.column.autoIncrement === true
        case 'uuid':
            return field.column.type === 'varchar' && field.column.length === 36
        case 'create_time':
            return field.column.type === 'bigint' && !p.includes('update')
        case 'update_time':
            return field.column.type === 'bigint' && p.includes('update')
        default:
            return false
    }
}

/** matchFieldType 从字段 schema 反推 UI 模板类型（form.enabled 不影响匹配，仅看 render/column） */
export const matchFieldType = (field: CrudFieldSchema): string | undefined => {
    const formRender = field.form?.render

    for (const def of FIELD_TYPE_DEFS) {
        const inf = def.inference

        if (inf.form === false) {
            if (isLayerEnabled(field.form)) continue
            if (!matchSystemFieldType(def.value, field)) continue
            return def.value
        }

        if (inf.form?.render !== formRender) continue
        if (def.value === 'primary_key') {
            if (!isLayerEnabled(field.column) || !field.column.primary || !field.column.autoIncrement) continue
        } else if (def.value === 'uuid') {
            if (!isLayerEnabled(field.column) || field.column.type !== 'varchar' || field.column.length !== 36) continue
        } else if (def.value === 'amount') {
            if (!isLayerEnabled(field.column) || field.column.type !== 'decimal') continue
        } else if (def.value === 'number') {
            if (isLayerEnabled(field.column) && field.column.type === 'decimal') continue
            if (isLayerEnabled(field.column) && field.column.primary) continue
        } else if (inf.column !== false && inf.column?.type && isLayerEnabled(field.column)) {
            if (field.column.type !== inf.column.type) continue
        }
        if (def.value === 'create_time' || def.value === 'update_time') {
            const p = field.prop.toLowerCase()
            if (def.value === 'create_time' && p.includes('update')) continue
            if (def.value === 'update_time' && !p.includes('update') && p.includes('create')) continue
        }
        return def.value
    }
    return undefined
}

/** resolveFieldType 取 UI 字段模板类型（优先 uiType，否则反推） */
export const resolveFieldType = (field: CrudFieldSchema): string | undefined => {
    if (field.uiType && fieldTypeDefOf(field.uiType)) return field.uiType
    return matchFieldType(field)
}

/** FIELD_TYPE_GROUPS 字段类型选项（分组） */
export const FIELD_TYPE_GROUPS: NamedOptionGroup[] = FIELD_GROUP_ORDER.map((label) => ({
    label,
    options: FIELD_TYPE_DEFS.filter((d) => d.group === label).map(({ value, label: l }) => ({
        value,
        label: l,
    })),
})).filter((g) => g.options.length > 0)

/** FIELD_TYPE_OPTIONS 扁平字段类型选项 */
export const FIELD_TYPE_OPTIONS: NamedOption[] = FIELD_TYPE_DEFS.map(({ value, label }) => ({
    value,
    label,
}))

/** FIELD_TYPE_NEED_ENUM 需要本地枚举/选项的字段类型 */
export const FIELD_TYPE_NEED_ENUM = FIELD_TYPE_DEFS.filter((d) => 'needEnum' in d).map((d) => d.value)

/** FIELD_TYPE_NEED_REMOTE 需要远程参数配置的字段类型 */
export const FIELD_TYPE_NEED_REMOTE = FIELD_TYPE_DEFS.filter((d) => 'needRemote' in d).map((d) => d.value)
