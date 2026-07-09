/**
 * CRUD 一键生成契约
 *
 * 输入：CrudFieldSchema[]（见 schemas/）
 * 主实体输出文件（以 example/data 为范本）：
 *   后端
 *   - app/admin/model/{module}/{Entity}.php
 *   - app/admin/controller/{module}/{Entity}.php
 *   - app/admin/validate/{module}/{Entity}.php
 *   前端
 *   - web/src/apis/{module}/{entity}.ts
 *   - web/src/views/{module}/{entity}/index.vue
 *   - web/src/views/{module}/{entity}/edit.vue
 *   - web/src/views/{module}/{entity}/constants.ts（有 enum/静态 options 时）
 *
 * 主实体 API 约定：
 *   create  新建（POST）
 *   read    详情（GET）
 *   list    分页列表（GET）
 *   update  更新（PUT）
 *   delete  删除（DELETE）
 *
 * 关联依赖（belongs_to / belongs_to_many）不随主实体生成，
 * 需已有独立 Model + Controller.list（供 ga-select-remote）。
 *
 * 金样自检：见 ./GOLDEN_CHECKLIST.ts（P0-5）
 * 金样输入：见 ./schemas/example-data.ts
 */

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

/** RenderType 表单渲染类型 */
export type RenderType =
    | 'input'
    | 'textarea'
    | 'number'
    | 'switch'
    | 'radio'
    | 'select'
    | 'remote_select'
    | 'multi_select'
    | 'remote_multi_select'
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

/** CrudFieldSchema CRUD 字段元数据（代码生成输入） */
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
    /** optionsKey 远程地址（example.Category/list）或 constants 静态键名 */
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
