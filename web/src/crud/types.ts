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
 *
 * 字段契约分层：column（建表）/ relation（关联）/ list / search / form
 * 表单与搜索控件枚举分离；search 可省略 render，按 FORM_TO_SEARCH_RENDER 从 form 推断。
 * JSON samples（extend/crud/samples）与 PHP 生成器均按此分层契约解析。
 *
 * 固定枚举约定：以功能名为前缀的 const 对象（如 LIST_RENDER、FORM_RENDER），
 * 类型由 const 推导；LABEL / OPTIONS / 映射表均引用前缀常量键。
 * 表单各 render 专属参数见 ./form-field-params.ts（FormFieldRenderParam 联合类型）。
 */

/** FIELD_TYPE MySQL 列数据类型 {bigint:Unix秒, tinyint:开关枚举, varchar:短文本路径} */
export const FIELD_TYPE = {
    BIGINT: 'bigint',
    INT: 'int',
    TINYINT: 'tinyint',
    DECIMAL: 'decimal',
    VARCHAR: 'varchar',
    CHAR: 'char',
    TEXT: 'text',
    MEDIUMTEXT: 'mediumtext',
    LONGTEXT: 'longtext',
    DATE: 'date',
    DATETIME: 'datetime',
    TIMESTAMP: 'timestamp',
    JSON: 'json',
} as const

/** FieldType MySQL 列数据类型（CREATE TABLE 类型名） */
export type FieldType = (typeof FIELD_TYPE)[keyof typeof FIELD_TYPE]

/** FIELD_TYPE_LIST MySQL 列类型选项顺序 */
export const FIELD_TYPE_LIST: FieldType[] = [
    FIELD_TYPE.BIGINT,
    FIELD_TYPE.INT,
    FIELD_TYPE.TINYINT,
    FIELD_TYPE.DECIMAL,
    FIELD_TYPE.VARCHAR,
    FIELD_TYPE.CHAR,
    FIELD_TYPE.TEXT,
    FIELD_TYPE.MEDIUMTEXT,
    FIELD_TYPE.LONGTEXT,
    FIELD_TYPE.DATE,
    FIELD_TYPE.DATETIME,
    FIELD_TYPE.TIMESTAMP,
    FIELD_TYPE.JSON,
]

/** FORM_RENDER 表单控件渲染类型 */
export const FORM_RENDER = {
    INPUT: 'input',
    TEXTAREA: 'textarea',
    NUMBER: 'number',
    SWITCH: 'switch',
    RADIO: 'radio',
    SELECT: 'select',
    REMOTE_SELECT: 'remote_select',
    MULTI_SELECT: 'multi_select',
    REMOTE_MULTI_SELECT: 'remote_multi_select',
    DATE: 'date',
    DATETIME: 'datetime',
    COLOR: 'color',
    IMAGE: 'image',
    READONLY: 'readonly',
    HIDDEN: 'hidden',
} as const

/** FormRenderType 表单控件渲染类型 */
export type FormRenderType = (typeof FORM_RENDER)[keyof typeof FORM_RENDER]

/**
 * FORM_RENDER_TYPE_LABEL 表单控件中文名
 * 用途：dev/curd UI 选项展示
 */
export const FORM_RENDER_TYPE_LABEL: Record<FormRenderType, string> = {
    [FORM_RENDER.INPUT]: '单行输入',
    [FORM_RENDER.TEXTAREA]: '多行文本',
    [FORM_RENDER.NUMBER]: '数字',
    [FORM_RENDER.SWITCH]: '开关',
    [FORM_RENDER.RADIO]: '单选',
    [FORM_RENDER.SELECT]: '下拉选择',
    [FORM_RENDER.REMOTE_SELECT]: '远程下拉',
    [FORM_RENDER.MULTI_SELECT]: '多选',
    [FORM_RENDER.REMOTE_MULTI_SELECT]: '远程多选',
    [FORM_RENDER.DATE]: '日期',
    [FORM_RENDER.DATETIME]: '日期时间',
    [FORM_RENDER.COLOR]: '颜色',
    [FORM_RENDER.IMAGE]: '图片',
    [FORM_RENDER.READONLY]: '只读',
    [FORM_RENDER.HIDDEN]: '隐藏',
}

/** FORM_RENDER_OPTIONS 表单控件选项 */
export const FORM_RENDER_OPTIONS = (
    Object.entries(FORM_RENDER_TYPE_LABEL) as [FormRenderType, string][]
).map(([value, label]) => ({ value, label }))

/** SEARCH_RENDER 搜索控件渲染类型 {number_range:数值区间, date_range/datetime_range:时间区间, bool:全部/是/否} */
export const SEARCH_RENDER = {
    INPUT: 'input',
    NUMBER_RANGE: 'number_range',
    SELECT: 'select',
    RADIO: 'radio',
    REMOTE_SELECT: 'remote_select',
    REMOTE_MULTI_SELECT: 'remote_multi_select',
    DATE_RANGE: 'date_range',
    DATETIME_RANGE: 'datetime_range',
    BOOL: 'bool',
} as const

/** SearchRenderType 搜索控件渲染类型（筛选语义，与表单不同） */
export type SearchRenderType = (typeof SEARCH_RENDER)[keyof typeof SEARCH_RENDER]

/**
 * SEARCH_RENDER_TYPE_LABEL 搜索控件中文名
 * 用途：dev/curd UI 选项展示
 */
export const SEARCH_RENDER_TYPE_LABEL: Record<SearchRenderType, string> = {
    [SEARCH_RENDER.INPUT]: '文本',
    [SEARCH_RENDER.NUMBER_RANGE]: '数值区间',
    [SEARCH_RENDER.SELECT]: '下拉选择',
    [SEARCH_RENDER.RADIO]: '单选',
    [SEARCH_RENDER.REMOTE_SELECT]: '远程下拉',
    [SEARCH_RENDER.REMOTE_MULTI_SELECT]: '远程多选',
    [SEARCH_RENDER.DATE_RANGE]: '日期区间',
    [SEARCH_RENDER.DATETIME_RANGE]: '日期时间区间',
    [SEARCH_RENDER.BOOL]: '是/否',
}

/** SEARCH_RENDER_OPTIONS 搜索控件选项 */
export const SEARCH_RENDER_OPTIONS = (
    Object.entries(SEARCH_RENDER_TYPE_LABEL) as [SearchRenderType, string][]
).map(([value, label]) => ({ value, label }))

/** LIST_RENDER 列表列渲染类型 */
export const LIST_RENDER = {
    TEXT: 'text',
    NUMBER: 'number',
    STATUS: 'status',
    TAG: 'tag',
    TAGS: 'tags',
    COLOR: 'color',
    IMAGE: 'image',
    DATETIME: 'datetime',
    BOOL: 'bool',
} as const

/** ListRenderType 列表列渲染类型 */
export type ListRenderType = (typeof LIST_RENDER)[keyof typeof LIST_RENDER]

/**
 * LIST_RENDER_TYPE_LABEL 列表列渲染类型中文名
 * 用途：dev/curd UI 选项展示
 */
export const LIST_RENDER_TYPE_LABEL: Record<ListRenderType, string> = {
    [LIST_RENDER.TEXT]: '文本',
    [LIST_RENDER.NUMBER]: '数字',
    [LIST_RENDER.STATUS]: '状态',
    [LIST_RENDER.TAG]: '标签',
    [LIST_RENDER.TAGS]: '多标签',
    [LIST_RENDER.COLOR]: '颜色',
    [LIST_RENDER.IMAGE]: '图片',
    [LIST_RENDER.DATETIME]: '日期时间',
    [LIST_RENDER.BOOL]: '布尔',
}

/** LIST_RENDER_OPTIONS 列表渲染选项 */
export const LIST_RENDER_OPTIONS = (
    Object.entries(LIST_RENDER_TYPE_LABEL) as [ListRenderType, string][]
).map(([value, label]) => ({ value, label }))

/** SEARCH_MODE 搜索参与方式 {keyword:关键词, advanced:高级筛选} */
export const SEARCH_MODE = {
    KEYWORD: 'keyword',
    ADVANCED: 'advanced',
} as const

/** SearchMode 搜索参与方式 */
export type SearchMode = (typeof SEARCH_MODE)[keyof typeof SEARCH_MODE]

/** SEARCH_MODE_LABEL 搜索方式中文名 */
export const SEARCH_MODE_LABEL: Record<SearchMode, string> = {
    [SEARCH_MODE.KEYWORD]: '关键词',
    [SEARCH_MODE.ADVANCED]: '高级筛选',
}

/** SEARCH_MODE_OPTIONS 搜索方式选项 */
export const SEARCH_MODE_OPTIONS = (
    Object.entries(SEARCH_MODE_LABEL) as [SearchMode, string][]
).map(([value, label]) => ({ value, label }))

/** RELATION_TYPE 关联类型（CRUD 契约支持的 ORM 关联） */
export const RELATION_TYPE = {
    BELONGS_TO: 'belongs_to',
    BELONGS_TO_MANY: 'belongs_to_many',
} as const

/** RelationType 关联类型 */
export type RelationType = (typeof RELATION_TYPE)[keyof typeof RELATION_TYPE]

/** RELATION_TYPE_LABEL 关联类型中文名 */
export const RELATION_TYPE_LABEL: Record<RelationType, string> = {
    [RELATION_TYPE.BELONGS_TO]: '一对一',
    [RELATION_TYPE.BELONGS_TO_MANY]: '多对多',
}

/** RELATION_TYPE_OPTIONS 关联类型选项 */
export const RELATION_TYPE_OPTIONS = (
    Object.entries(RELATION_TYPE_LABEL) as [RelationType, string][]
).map(([value, label]) => ({ value, label }))

/** WRITE_MODE 写盘策略 {skip:已存在跳过, overwrite:覆盖} */
export const WRITE_MODE = {
    SKIP: 'skip',
    OVERWRITE: 'overwrite',
} as const

/** CrudWriteMode 写盘策略 */
export type CrudWriteMode = (typeof WRITE_MODE)[keyof typeof WRITE_MODE]

/** WRITE_MODE_LABEL 写盘策略中文名 */
export const WRITE_MODE_LABEL: Record<CrudWriteMode, string> = {
    [WRITE_MODE.SKIP]: '已存在跳过',
    [WRITE_MODE.OVERWRITE]: '覆盖',
}

/** MENU_LEVEL_TYPE 菜单项类型 {menu:目录, view:页面} */
export const MENU_LEVEL_TYPE = {
    MENU: 'menu',
    VIEW: 'view',
} as const

/** CrudMenuLevelType 菜单项类型 */
export type CrudMenuLevelType = (typeof MENU_LEVEL_TYPE)[keyof typeof MENU_LEVEL_TYPE]

/** MENU_LEVEL_TYPE_LABEL 菜单项类型中文名 */
export const MENU_LEVEL_TYPE_LABEL: Record<CrudMenuLevelType, string> = {
    [MENU_LEVEL_TYPE.MENU]: '菜单',
    [MENU_LEVEL_TYPE.VIEW]: '页面',
}

/** TAG_TYPE Element Plus Tag 类型（StatusMap 展示用） */
export const TAG_TYPE = {
    SUCCESS: 'success',
    INFO: 'info',
    WARNING: 'warning',
    DANGER: 'danger',
    PRIMARY: 'primary',
} as const

/** TagType Element Plus Tag 类型 */
export type TagType = (typeof TAG_TYPE)[keyof typeof TAG_TYPE]

/** TAG_TYPE_LABEL Tag 类型中文名 */
export const TAG_TYPE_LABEL: Record<TagType, string> = {
    [TAG_TYPE.SUCCESS]: 'success',
    [TAG_TYPE.INFO]: 'info',
    [TAG_TYPE.WARNING]: 'warning',
    [TAG_TYPE.DANGER]: 'danger',
    [TAG_TYPE.PRIMARY]: 'primary',
}

/** TAG_TYPE_OPTIONS Tag 类型选项 */
export const TAG_TYPE_OPTIONS = (
    Object.entries(TAG_TYPE_LABEL) as [TagType, string][]
).map(([value, label]) => ({ value, label }))

/**
 * FORM_RENDER_FIELD_TYPE 表单控件 → 建议 MySQL 类型
 * 用途：UI 选控件时推断 column.type；可被显式 column.type 覆盖
 */
export const FORM_RENDER_FIELD_TYPE: Record<FormRenderType, FieldType> = {
    [FORM_RENDER.INPUT]: FIELD_TYPE.VARCHAR,
    [FORM_RENDER.TEXTAREA]: FIELD_TYPE.TEXT,
    [FORM_RENDER.NUMBER]: FIELD_TYPE.INT,
    [FORM_RENDER.SWITCH]: FIELD_TYPE.TINYINT,
    [FORM_RENDER.RADIO]: FIELD_TYPE.TINYINT,
    [FORM_RENDER.SELECT]: FIELD_TYPE.TINYINT,
    [FORM_RENDER.REMOTE_SELECT]: FIELD_TYPE.BIGINT,
    [FORM_RENDER.MULTI_SELECT]: FIELD_TYPE.JSON,
    [FORM_RENDER.REMOTE_MULTI_SELECT]: FIELD_TYPE.BIGINT,
    [FORM_RENDER.DATE]: FIELD_TYPE.DATE,
    [FORM_RENDER.DATETIME]: FIELD_TYPE.BIGINT,
    [FORM_RENDER.COLOR]: FIELD_TYPE.VARCHAR,
    [FORM_RENDER.IMAGE]: FIELD_TYPE.VARCHAR,
    [FORM_RENDER.READONLY]: FIELD_TYPE.VARCHAR,
    [FORM_RENDER.HIDDEN]: FIELD_TYPE.BIGINT,
}

/**
 * LIST_RENDER_FIELD_TYPE 列表列渲染 → 建议 MySQL 类型
 * 用途：仅列展示时的类型提示；以 column.type 为准
 */
export const LIST_RENDER_FIELD_TYPE: Record<ListRenderType, FieldType> = {
    [LIST_RENDER.TEXT]: FIELD_TYPE.VARCHAR,
    [LIST_RENDER.NUMBER]: FIELD_TYPE.INT,
    [LIST_RENDER.STATUS]: FIELD_TYPE.TINYINT,
    [LIST_RENDER.TAG]: FIELD_TYPE.TINYINT,
    [LIST_RENDER.TAGS]: FIELD_TYPE.JSON,
    [LIST_RENDER.COLOR]: FIELD_TYPE.VARCHAR,
    [LIST_RENDER.IMAGE]: FIELD_TYPE.VARCHAR,
    [LIST_RENDER.DATETIME]: FIELD_TYPE.BIGINT,
    [LIST_RENDER.BOOL]: FIELD_TYPE.TINYINT,
}

/**
 * FORM_TO_SEARCH_RENDER 表单控件 → 默认搜索控件
 * 用途：search 省略 render 时推断；无映射则不自动生成 advanced 控件语义
 */
export const FORM_TO_SEARCH_RENDER: Partial<Record<FormRenderType, SearchRenderType>> = {
    [FORM_RENDER.INPUT]: SEARCH_RENDER.INPUT,
    [FORM_RENDER.NUMBER]: SEARCH_RENDER.NUMBER_RANGE,
    [FORM_RENDER.SWITCH]: SEARCH_RENDER.BOOL,
    [FORM_RENDER.RADIO]: SEARCH_RENDER.SELECT,
    [FORM_RENDER.SELECT]: SEARCH_RENDER.SELECT,
    [FORM_RENDER.REMOTE_SELECT]: SEARCH_RENDER.REMOTE_SELECT,
    [FORM_RENDER.REMOTE_MULTI_SELECT]: SEARCH_RENDER.REMOTE_MULTI_SELECT,
    [FORM_RENDER.DATE]: SEARCH_RENDER.DATE_RANGE,
    [FORM_RENDER.DATETIME]: SEARCH_RENDER.DATETIME_RANGE,
}

/**
 * CrudFieldColumnConfig MySQL 列定义参数
 * 用途：对应 CREATE TABLE 单列：类型 / 长度 / NULL / DEFAULT / AUTO_INCREMENT / 键等
 */
export type CrudFieldColumnConfig = {
    /** type MySQL 列数据类型 */
    type: FieldType
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
    /** render 列渲染类型，默认 text */
    render?: ListRenderType
    /** width 列宽 */
    width?: number | string
    /** minWidth 最小列宽 */
    minWidth?: number | string
    /** relationProp 关联展示字段（如 category.name） */
    relationProp?: string
    /** statusMapKey constants 中 StatusMap 键名 */
    statusMapKey?: string
    /** optionsKey 静态选项键（如 json tags 列） */
    optionsKey?: string
}

/**
 * CrudFieldSearchConfig 列表搜索渲染配置
 * 用途：index.vue 搜索栏；有此对象则参与搜索
 * optionsKey/statusMapKey/placeholder 可省略，默认复用 form
 */
export type CrudFieldSearchConfig = {
    /** mode 搜索方式 {keyword:关键词, advanced:高级筛选} */
    mode: SearchMode
    /** render 搜索控件；省略时按 FORM_TO_SEARCH_RENDER(form.render) 推断 */
    render?: SearchRenderType
    /** placeholder 占位符 */
    placeholder?: string
    /** optionsKey 远程地址或 constants 静态键名；省略复用 form */
    optionsKey?: string
    /** statusMapKey constants 中 StatusMap 键名；省略复用 form/list */
    statusMapKey?: string
}

/**
 * CrudFieldFormConfig 新增/编辑表单字段渲染配置
 * 用途：edit.vue；form===false 表示不进表单；省略时默认不进表单
 * 各 render 专属参数见 FormFieldRenderParam（./form-field-params.ts）
 */
export type CrudFieldFormConfig = {
    /** render 表单控件类型 */
    render: FormRenderType
    /** required 是否必填 */
    required?: boolean
    /** placeholder 占位符 */
    placeholder?: string
    /** optionsKey 远程地址（example.Category/list）或 constants 静态键名 */
    optionsKey?: string
    /** statusMapKey constants 中 StatusMap 键名 */
    statusMapKey?: string
    /** numberMin number 最小值 */
    numberMin?: number
    /** numberMax number 最大值 */
    numberMax?: number
    /** numberStep number 步进 */
    numberStep?: number
    /** maxLength 输入框最大长度（UI，可与 column.length 不同） */
    maxLength?: number
}

export type { FormFieldRenderParam, FormFieldRenderParamMap, FormFieldRenderParamOf } from './form-field-params'
export type {
    FormColorFieldParam,
    FormDateFieldParam,
    FormDatetimeFieldParam,
    FormHiddenFieldParam,
    FormImageFieldParam,
    FormInputFieldParam,
    FormMultiSelectFieldParam,
    FormNumberFieldParam,
    FormRadioFieldParam,
    FormReadonlyFieldParam,
    FormRemoteMultiSelectFieldParam,
    FormRemoteSelectFieldParam,
    FormSelectFieldParam,
    FormSwitchFieldParam,
    FormTextareaFieldParam,
} from './form-field-params'

/** CrudFieldSchema CRUD 字段元数据（代码生成输入） */
export type CrudFieldSchema = {
    /** prop 字段名（表单/列属性） */
    prop: string
    /** label 中文标签 */
    label: string
    /** column MySQL 列定义；belongs_to_many 等无主表物理列时可省略 */
    column?: CrudFieldColumnConfig
    /** relation ORM 关联；与 column 独立 */
    relation?: CrudFieldRelationConfig
    /** list 列表列配置；省略则不显示列 */
    list?: CrudFieldListConfig
    /** search 搜索配置；省略则不参与搜索 */
    search?: CrudFieldSearchConfig
    /** form 表单配置；false=不进表单；省略=不进表单 */
    form?: false | CrudFieldFormConfig
}

/** CrudStatusMapEntry StatusMap 单项 */
export type CrudStatusMapEntry = {
    /** label 展示文案 */
    label: string
    /** type Element Plus Tag 类型 */
    type?: TagType | string
}

/** CrudOptionItem 静态下拉选项 */
export type CrudOptionItem = {
    /** value 选项值 */
    value: string | number
    /** label 展示文案 */
    label: string
}

/** CrudOutputPaths 生成文件相对路径（不含固定前缀） */
export type CrudOutputPaths = {
    /** controller 相对 app/admin/controller/，如 Countries.php 或 demo/Item.php */
    controller: string
    /** model 相对 app/admin/model/ */
    model: string
    /** validate 相对 app/admin/validate/ */
    validate: string
    /** apis 相对 web/src/apis/，如 countries.ts 或 demo/item.ts */
    apis: string
    /** viewsDir 相对 web/src/views/，如 countries/ 或 demo/item/ */
    viewsDir: string
}

/** CrudMenuLevel 前端菜单层级（index0=一级；末项必须为 view） */
export type CrudMenuLevel = {
    /** type 仅最后一项为 view，前面均为 menu */
    type: CrudMenuLevelType
    /** path 路由片段，相对父级 */
    path: string
    /** title 显示名 */
    title: string
    /** icon 图标 */
    icon?: string
    /** sort 排序 */
    sort?: number
    /** enabled false 时本级不生成，下级升一级 */
    enabled: boolean
}

/**
 * CrudGenerateConfig 一键生成输入配置
 * 用途：CLI / 后续 dev/curd UI 的统一 JSON 契约
 */
export type CrudGenerateConfig = {
    /** table 数据表名（无前缀，如 example_data） */
    table: string
    /** menus 无限级菜单（末项 type=view；enabled=false 时下级升一级） */
    menus: CrudMenuLevel[]
    /** paths 生成文件相对路径 */
    paths: CrudOutputPaths
    /** fields 字段元数据 */
    fields: CrudFieldSchema[]
    /** maps StatusMap 等枚举映射（生成 constants.ts） */
    maps?: Record<string, Record<string, CrudStatusMapEntry>>
    /** options 静态多选/下拉选项（生成 constants.ts） */
    options?: Record<string, CrudOptionItem[]>
    /** writeMode 写盘策略，默认 skip */
    writeMode?: CrudWriteMode
    /** generateAuth 是否写入权限/菜单到数据库，默认 true；不挂角色 */
    generateAuth?: boolean
    /** module 由 paths.controller 派生（内部/后端用） */
    module?: string
    /** entity 由 paths.controller 派生（内部/后端用） */
    entity?: string
    /** title 由末级 view.title 派生 */
    title?: string
    /** moduleTitle 由首个启用菜单派生 */
    moduleTitle?: string
    /** menuIcon 由首个启用项派生 */
    menuIcon?: string
    /** menuSort 由首个启用项派生 */
    menuSort?: number
}
