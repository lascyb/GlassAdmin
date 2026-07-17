import type { FormRenderType } from './form-render'
import type { ListRenderType } from './list-render'
import type { RelationType } from './relation'
import type { SearchRenderType } from './search-render'

/**
 * CrudFieldColumnConfig MySQL 列定义参数
 * 用途：对应 CREATE TABLE 单列：类型 / 长度 / NULL / DEFAULT / AUTO_INCREMENT / 键等
 */
export type CrudFieldColumnConfig = {
    /** enabled 是否生成 MySQL 列 {true:生成,false:不生成} */
    enabled?: boolean
    /** type MySQL 列数据类型（可手输，常用见 FIELD_TYPE_LIST） */
    type: string
    /** length varchar/char 长度；也可作整型显示宽度（少用） */
    length?: number
    /** precision decimal 总位数 M */
    precision?: number
    /** scale decimal 小数位 D */
    scale?: number
    /** unsigned 无符号（整型） */
    unsigned?: boolean
    /** nullable 是否允许 NULL；false 为 NOT NULL；省略由生成器按类型给默认 */
    nullable?: boolean
    /** default 默认值；null 表示 DEFAULT NULL */
    default?: string | number | null
    /** autoIncrement AUTO_INCREMENT（与 primary 独立） */
    autoIncrement?: boolean
    /** primary PRIMARY KEY（与 autoIncrement 独立） */
    primary?: boolean
    /** unique UNIQUE 索引 */
    unique?: boolean
    /** comment 列注释；省略时用字段 label */
    comment?: string
}

/**
 * CrudFieldRelationConfig ORM 关联配置（非 MySQL 列参数）
 * 用途：Model 关联、中间表；belongs_to_many 可无主表物理列
 */
export type CrudFieldRelationConfig = {
    /** enabled 是否配置 ORM 关联 {true:配置,false:不配置} */
    enabled?: boolean
    /** type 关联类型 */
    type: RelationType
    /** pivotTable belongs_to_many 中间表 */
    pivotTable?: string
    /** foreignKey 中间表指向主实体的外键 */
    foreignKey?: string
    /** relatedKey 中间表指向关联实体的外键 */
    relatedKey?: string
}

/**
 * CrudFieldListConfig 列表列渲染配置
 * 用途：index.vue 表格列；有此对象则显示该列
 */
export type CrudFieldListConfig = {
    /** enabled 是否显示该列 {true:显示,false:隐藏} */
    enabled?: boolean
    /** render 列渲染类型，默认 text */
    render?: ListRenderType
    /** width 列宽 */
    width?: number | string
    /** minWidth 最小列宽 */
    minWidth?: number | string
    /** relationProp 关联展示字段（如 category.name） */
    relationProp?: string
}

/**
 * CrudFieldSearchConfig 列表搜索渲染配置
 * 用途：index.vue 搜索栏；有此对象则参与搜索
 * 枚举/远程数据见 field.ext
 */
export type CrudFieldSearchConfig = {
    /** enabled 是否参与搜索 {true:参与,false:不参与} */
    enabled?: boolean
    /** keyword 是否参与顶部关键词搜索 {true:参与,false:不参与} */
    keyword?: boolean
    /** advanced 是否生成高级筛选项 {true:生成,false:不生成} */
    advanced?: boolean
    /** render 高级搜索控件；省略时按 form 对应控件的 inference.search 推断 */
    render?: SearchRenderType
    /** match 文本搜索匹配方式 {fuzzy:模糊,exact:精确} */
    match?: 'fuzzy' | 'exact'
}

/**
 * CrudFieldFormConfig 新增/编辑表单字段渲染配置
 * 用途：edit.vue；enabled=false 不进表单
 * 各 render 专属参数见 FormFieldRenderParam（form-field-params.ts）
 * 枚举/远程数据见 field.ext
 */
export type CrudFieldFormConfig = {
    /** enabled 是否进表单 {true:进表单,false:不进表单} */
    enabled?: boolean
    /** render 表单控件类型 */
    render: FormRenderType
    /** required 是否必填 */
    required?: boolean
    /** numberMin number 最小值 */
    numberMin?: number
    /** numberMax number 最大值 */
    numberMax?: number
    /** numberStep number 步进 */
    numberStep?: number
    /** maxLength 输入框最大长度（UI，可与 column.length 不同） */
    maxLength?: number
}

/** CrudFieldEnumExt 枚举扩展参数 */
export type CrudFieldEnumExt = {
    /** key 指向 config.options 的键名 */
    key?: string
}

/** CrudFieldRemoteExt 远程扩展参数 */
export type CrudFieldRemoteExt = {
    /** api 远程 list 接口（如 test.ft.Text/list） */
    api?: string
    /** valueField 选项 value 字段 */
    valueField?: string
    /** labelField 选项 label 字段 */
    labelField?: string
    /** table 关联实体表名（无前缀） */
    table?: string
}

/**
 * CrudFieldExtConfig 字段扩展参数（与 prop 同级）
 * 用途：枚举 options、远程数据源等跨层共享配置
 */
export type CrudFieldExtConfig = {
    /** enum 枚举配置 */
    enum?: CrudFieldEnumExt
    /** remote 远程配置 */
    remote?: CrudFieldRemoteExt
}

/** isLayerEnabled 层级 enabled 是否开启（省略视为 true） */
export const isLayerEnabled = (cfg: { enabled?: boolean }): boolean => cfg.enabled !== false

/** CrudFieldSchema CRUD 字段元数据（代码生成输入） */
export type CrudFieldSchema = {
    /** prop 字段名（表单/列属性） */
    prop: string
    /** label 中文标签 */
    label: string
    /** uiType UI 字段模板类型（左栏选择值，层开关不丢失） */
    uiType?: string
    /** ext 扩展参数（枚举/远程等，与 prop 同级） */
    ext?: CrudFieldExtConfig
    /** column MySQL 列定义；enabled=false 不生成列 */
    column: CrudFieldColumnConfig
    /** relation ORM 关联；enabled=false 不配置关联 */
    relation: CrudFieldRelationConfig
    /** list 列表列配置；enabled=false 不显示列 */
    list: CrudFieldListConfig
    /** search 搜索配置；enabled=false 不参与搜索 */
    search: CrudFieldSearchConfig
    /** form 表单配置；enabled=false 不进表单 */
    form: CrudFieldFormConfig
}
