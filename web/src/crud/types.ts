/** FieldType 数据库字段类型 */
export type FieldType =
    | 'pk'
    | 'string'
    | 'text'
    | 'int'
    | 'decimal'
    | 'bool'
    | 'enum'
    | 'timestamp'
    | 'date'
    | 'json'
    | 'image'

/** RenderType 表单/列表渲染类型 */
export type RenderType =
    | 'input'
    | 'textarea'
    | 'number'
    | 'switch'
    | 'radio'
    | 'select'
    | 'multi_select'
    | 'date'
    | 'datetime'
    | 'color'
    | 'image'
    | 'readonly'
    | 'hidden'

/** ListRenderType 列表列渲染类型 */
export type ListRenderType =
    | 'text'
    | 'number'
    | 'status'
    | 'tag'
    | 'tags'
    | 'color'
    | 'image'
    | 'datetime'
    | 'bool'

/** RelationType 关联类型 */
export type RelationType = 'none' | 'belongs_to' | 'belongs_to_many'

/** SearchMode 搜索参与方式 */
export type SearchMode = false | true | 'keyword' | 'advanced'

/** CrudFieldSchema CRUD 字段元数据（代码生成模板） */
export type CrudFieldSchema = {
    /** prop 字段名（表单/列属性） */
    prop: string
    /** label 中文标签 */
    label: string
    /** fieldType 数据库字段类型 */
    fieldType: FieldType
    /** renderType 表单渲染类型 */
    renderType: RenderType
    /** listRender 列表列渲染类型 */
    listRender?: ListRenderType
    /** relationType 关联类型 */
    relationType?: RelationType
    /** relationProp 关联展示字段（如 category.name） */
    relationProp?: string
    /** optionsKey 下拉选项来源（options 接口或 constants 键名） */
    optionsKey?: string
    /** statusMapKey constants 中 StatusMap 键名 */
    statusMapKey?: string
    /** list 是否显示在列表 */
    list?: boolean
    /** form 是否显示在表单（默认 true） */
    form?: boolean
    /** search 是否参与搜索 */
    search?: SearchMode
    /** width 列表列宽 */
    width?: number | string
    /** minWidth 列表最小列宽 */
    minWidth?: number | string
    /** required 表单必填 */
    required?: boolean
    /** placeholder 占位符 */
    placeholder?: string
    /** maxLength 最大长度 */
    maxLength?: number
    /** numberMin number 最小值 */
    numberMin?: number
    /** numberMax number 最大值 */
    numberMax?: number
    /** numberStep number 步进 */
    numberStep?: number
    /** decimalPlaces decimal 小数位 */
    decimalPlaces?: number
}

/** CrudOptionsPayload 表单 options 接口通用结构 */
export type CrudOptionsPayload = Record<string, Array<{ id?: number; value?: number | string; label: string; name?: string }>>
