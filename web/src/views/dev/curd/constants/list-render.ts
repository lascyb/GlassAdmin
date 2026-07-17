import type { ListRenderType } from '../types/list-render'
import type { FieldType } from '../types/mysql-field-type'
import type { NamedOption, NamedOptionGroup } from '../types/option'
import { FIELD_TYPE } from './mysql-field-type'

/** ListRenderDef 列表列渲染项定义（契约 + UI） */
type ListRenderDef = {
    key: string
    value: string
    label: string
    group: string
    fieldType: FieldType // 建议 MySQL 类型
    needEnum?: true // 需要枚举/选项
    needRelationProp?: true // 需要关联展示字段
}

/** LIST_RENDER_DEFS 列表列渲染定义列表 */
export const LIST_RENDER_DEFS = [
    { key: 'TEXT', value: 'text', label: '文本', group: '基础', fieldType: FIELD_TYPE.VARCHAR },
    { key: 'NUMBER', value: 'number', label: '数字', group: '基础', fieldType: FIELD_TYPE.INT },
    { key: 'BOOL', value: 'bool', label: '布尔', group: '基础', fieldType: FIELD_TYPE.TINYINT },
    {
        key: 'STATUS',
        value: 'status',
        label: '状态',
        group: '状态与标签',
        fieldType: FIELD_TYPE.TINYINT,
        needEnum: true,
    },
    {
        key: 'TAG',
        value: 'tag',
        label: '标签',
        group: '状态与标签',
        fieldType: FIELD_TYPE.TINYINT,
        needEnum: true,
        needRelationProp: true,
    },
    {
        key: 'TAGS',
        value: 'tags',
        label: '多标签',
        group: '状态与标签',
        fieldType: FIELD_TYPE.JSON,
        needEnum: true,
        needRelationProp: true,
    },
    {
        key: 'DATETIME',
        value: 'datetime',
        label: '日期时间',
        group: '日期时间',
        fieldType: FIELD_TYPE.BIGINT,
    },
    { key: 'IMAGE', value: 'image', label: '图片', group: '媒体与其它', fieldType: FIELD_TYPE.VARCHAR },
    { key: 'COLOR', value: 'color', label: '颜色', group: '媒体与其它', fieldType: FIELD_TYPE.VARCHAR },
] as const satisfies readonly ListRenderDef[]

const LIST_GROUP_ORDER = ['基础', '状态与标签', '日期时间', '媒体与其它'] as const

/** LIST_RENDER 列表列渲染类型 */
export const LIST_RENDER = Object.fromEntries(LIST_RENDER_DEFS.map((d) => [d.key, d.value])) as {
    [K in (typeof LIST_RENDER_DEFS)[number]['key']]: Extract<
        (typeof LIST_RENDER_DEFS)[number],
        { key: K }
    >['value']
}

/** LIST_RENDER_TYPE_LABEL 列表列渲染类型中文名 */
export const LIST_RENDER_TYPE_LABEL = Object.fromEntries(
    LIST_RENDER_DEFS.map((d) => [d.value, d.label]),
) as Record<ListRenderType, string>

/** LIST_RENDER_OPTIONS 列表渲染选项 */
export const LIST_RENDER_OPTIONS = LIST_RENDER_DEFS.map(({ value, label }) => ({ value, label }))

/** LIST_RENDER_FIELD_TYPE 列表列渲染 → 建议 MySQL 类型 */
export const LIST_RENDER_FIELD_TYPE = Object.fromEntries(
    LIST_RENDER_DEFS.map((d) => [d.value, d.fieldType]),
) as Record<ListRenderType, FieldType>

/** LIST_COLUMN_RENDER_DEFS 列渲染 UI 定义（同 LIST_RENDER_DEFS） */
export const LIST_COLUMN_RENDER_DEFS = LIST_RENDER_DEFS

/** LIST_COLUMN_RENDER_GROUPS 列渲染类型选项（分组） */
export const LIST_COLUMN_RENDER_GROUPS: NamedOptionGroup[] = LIST_GROUP_ORDER.map((label) => ({
    label,
    options: LIST_RENDER_DEFS.filter((d) => d.group === label).map(({ value, label: l }) => ({
        value,
        label: l,
    })),
})).filter((g) => g.options.length > 0)

/** LIST_COLUMN_RENDER_OPTIONS 扁平列渲染类型选项 */
export const LIST_COLUMN_RENDER_OPTIONS: NamedOption[] = LIST_RENDER_DEFS.map(({ value, label }) => ({
    value,
    label,
}))

/** LIST_RENDER_NEED_ENUM 需要枚举/选项的列渲染 */
export const LIST_RENDER_NEED_ENUM = LIST_RENDER_DEFS.filter((d) => 'needEnum' in d).map((d) => d.value)
