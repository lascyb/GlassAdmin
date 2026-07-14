import type {
    CrudFieldSchema,
    CrudGenerateConfig,
    CrudMenuLevel,
    CrudOutputPaths,
    FieldType,
    FormRenderType,
    ListRenderType,
    SearchMode,
    SearchRenderType,
} from '@/crud/types'
import {
    FORM_RENDER_TYPE_LABEL,
    FORM_TO_SEARCH_RENDER,
    LIST_RENDER_TYPE_LABEL,
    SEARCH_RENDER_TYPE_LABEL,
} from '@/crud/types'
import {defaultPivot} from './fieldIntent'

/** FIELD_TYPES MySQL 列类型选项 */
export const FIELD_TYPES: FieldType[] = [
    'bigint',
    'int',
    'tinyint',
    'decimal',
    'varchar',
    'char',
    'text',
    'mediumtext',
    'longtext',
    'date',
    'datetime',
    'timestamp',
    'json',
]

/** FORM_RENDER_OPTIONS 表单控件选项 */
export const FORM_RENDER_OPTIONS = Object.entries(FORM_RENDER_TYPE_LABEL).map(([value, label]) => ({
    value: value as FormRenderType,
    label,
}))

/** LIST_RENDER_OPTIONS 列表渲染选项 */
export const LIST_RENDER_OPTIONS = Object.entries(LIST_RENDER_TYPE_LABEL).map(([value, label]) => ({
    value: value as ListRenderType,
    label,
}))

/** SEARCH_RENDER_OPTIONS 搜索控件选项 */
export const SEARCH_RENDER_OPTIONS = Object.entries(SEARCH_RENDER_TYPE_LABEL).map(([value, label]) => ({
    value,
    label,
}))

/** SEARCH_MODE_OPTIONS 搜索方式 */
export const SEARCH_MODE_OPTIONS = [
    {value: 'keyword', label: '关键词'},
    {value: 'advanced', label: '高级筛选'},
] as const

/** RELATION_TYPE_OPTIONS 关联类型(thinkphp8支持的关联类型) */
export const RELATION_TYPE_OPTIONS = [
    {value: "has_one", label: "一对一"},
    {value: "belongs_to", label: "一对一"},
    {value: "has_many", label: "一对多"},
    {value: "has_one_through", label: "远程一对一"},
    {value: "has_many_through", label: "远程一对多"},
    {value: "belongs_to_many", label: "多对多"},
    {value: "morph_many", label: "多态一对多"},
    {value: "morph_one", label: "多态一对一"},
    {value: "morph_to", label: "多态"}
] as const

/** PATH_PREFIXES 生成路径固定前缀 */
export const PATH_PREFIXES = {
    controller: 'app/admin/controller/',
    model: 'app/admin/model/',
    validate: 'app/admin/validate/',
    apis: 'web/src/apis/',
    viewsDir: 'web/src/views/',
} as const

/** capitalizePart 表名片段首字母大写 */
const capitalizePart = (p: string): string => p.charAt(0).toUpperCase() + p.slice(1).toLowerCase()

/** defaultPaths 空相对路径占位 */
export const defaultPaths = (): CrudOutputPaths => ({
    controller: 'Item.php',
    model: 'Item.php',
    validate: 'Item.php',
    apis: 'item.ts',
    viewsDir: 'item/',
})

/** guessPathsFromParts 按下划线逐级拆目录，末段为类名：a_b_c → a/b/C.php */
export const guessPathsFromParts = (parts: string[]): CrudOutputPaths => {
    if (parts.length === 0) return defaultPaths()
    const last = parts[parts.length - 1] ?? 'item'
    const entity = capitalizePart(last)
    const file = last.toLowerCase()
    const dirs = parts.slice(0, -1).map((p) => p.toLowerCase())
    const prefix = dirs.length ? `${dirs.join('/')}/` : ''
    return {
        controller: `${prefix}${entity}.php`,
        model: `${prefix}${entity}.php`,
        validate: `${prefix}${entity}.php`,
        apis: `${prefix}${file}.ts`,
        viewsDir: `${prefix}${file}/`,
    }
}

/** guessMenusFromParts 末段为 view，前面为 menu；单段仅 view */
export const guessMenusFromParts = (parts: string[]): CrudMenuLevel[] => {
    if (parts.length === 0) {
        return [{type: 'view', path: 'item', title: 'Item', icon: 'el:Document', sort: 0, enabled: true}]
    }
    const first = parts[0] ?? 'item'
    if (parts.length === 1) {
        const p = first.toLowerCase()
        return [
            {
                type: 'view',
                path: p,
                title: capitalizePart(first),
                icon: 'el:Document',
                sort: 0,
                enabled: true,
            },
        ]
    }
    const menus: CrudMenuLevel[] = []
    parts.slice(0, -1).forEach((raw, i) => {
        const path = raw.toLowerCase()
        const level: CrudMenuLevel = {
            type: 'menu',
            path,
            title: capitalizePart(raw),
            enabled: true,
        }
        if (i === 0) {
            level.icon = 'el:Document'
            level.sort = 0
        }
        menus.push(level)
    })
    const last = parts[parts.length - 1] ?? 'item'
    menus.push({
        type: 'view',
        path: last.toLowerCase(),
        title: capitalizePart(last),
        enabled: true,
    })
    return menus
}

/** leafView 取末级页面项 */
export const leafView = (menus: CrudMenuLevel[]): CrudMenuLevel | null => {
    if (!menus.length) return null
    const last = menus[menus.length - 1]
    return last?.type === 'view' ? last : null
}

/** parseControllerPath 从 controller 相对路径解析 moduleDir + entity */
export const parseControllerPath = (
    controller: string,
): { module: string; entity: string } => {
    const cleaned = controller.replace(/^\/+/, '').replace(/\.php$/i, '')
    const segs = cleaned.split('/').filter(Boolean)
    if (segs.length === 0) return {module: '', entity: 'Item'}
    if (segs.length === 1) return {module: '', entity: segs[0] ?? 'Item'}
    const entity = segs[segs.length - 1] ?? 'Item'
    const module = segs.slice(0, -1).join('/')
    return {module, entity}
}

/** syncDerivedFields 由 menus/paths 回填 title/module/entity 等内部字段 */
export const syncDerivedFields = (cfg: CrudGenerateConfig): void => {
    const view = leafView(cfg.menus ?? [])
    if (view) {
        cfg.title = view.title
    }
    const first = (cfg.menus ?? []).find((m) => m.enabled) ?? cfg.menus?.[0]
    if (first) {
        cfg.moduleTitle = first.title
        if (first.icon) cfg.menuIcon = first.icon
        if (first.sort != null) cfg.menuSort = first.sort
    }
    if (!cfg.menuIcon) cfg.menuIcon = 'el:Document'
    if (cfg.menuSort == null) cfg.menuSort = 0
    const parsed = parseControllerPath(cfg.paths?.controller ?? '')
    cfg.module = parsed.module
    cfg.entity = parsed.entity
}

/** guessMetaFromTable 从表名推导 menus + paths */
export const guessMetaFromTable = (
    table: string,
): Pick<CrudGenerateConfig, 'menus' | 'paths' | 'title'> => {
    const parts = table
        .split('_')
        .map((p) => p.trim())
        .filter(Boolean)
    const menus = guessMenusFromParts(parts)
    const paths = guessPathsFromParts(parts)
    return {
        menus,
        paths,
        title: leafView(menus)?.title ?? '',
    }
}

/** defaultPrimaryField 默认主键字段 */
const defaultPrimaryField = (): CrudFieldSchema => ({
    prop: 'id',
    label: 'ID',
    column: {
        type: 'bigint',
        unsigned: true,
        nullable: false,
        autoIncrement: true,
        primary: true,
    },
    list: {render: 'text', width: 72},
    form: false,
})

/** emptyConfig 从零开始的空配置（含 id 主键） */
export const emptyConfig = (): CrudGenerateConfig => {
    const menus = guessMenusFromParts([])
    const paths = defaultPaths()
    const cfg: CrudGenerateConfig = {
        table: '',
        menus,
        paths,
        fields: [defaultPrimaryField()],
        generateAuth: true,
        writeMode: 'skip',
        menuIcon: 'el:Document',
        menuSort: 0,
    }
    syncDerivedFields(cfg)
    return cfg
}

/** defaultField 新增空白字段 */
export const defaultField = (index = 0): CrudFieldSchema => ({
    prop: `field_${index + 1}`,
    label: `字段${index + 1}`,
    column: {
        type: 'varchar',
        length: 255,
        nullable: true,
    },
    list: {render: 'text', minWidth: 120},
    search: {mode: 'keyword'},
    form: {render: 'input'},
})

/** cloneConfig 深拷贝配置 */
export const cloneConfig = (cfg: CrudGenerateConfig): CrudGenerateConfig =>
    JSON.parse(JSON.stringify(cfg)) as CrudGenerateConfig

/** fieldTypeSummary 字段类型摘要（列表展示） */
export const fieldTypeSummary = (field: CrudFieldSchema): string => {
    const parts: string[] = []
    if (field.column?.type) {
        let t: string = field.column.type
        if (field.column.length) t += `(${field.column.length})`
        if (field.column.precision != null) {
            t = `${field.column.type}(${field.column.precision},${field.column.scale ?? 0})`
        }
        parts.push(t)
    } else if (field.relation?.type) {
        parts.push(field.relation.type)
    } else {
        parts.push('-')
    }
    if (field.form === false) {
        parts.push('无表单')
    } else if (field.form?.render) {
        parts.push(FORM_RENDER_TYPE_LABEL[field.form.render] ?? field.form.render)
    }
    if (!field.list) parts.push('无列')
    if (field.search) parts.push(field.search.mode === 'keyword' ? '关键词' : '筛选')
    return parts.join(' · ')
}

/** ensureColumn 保证 column 对象存在 */
export const ensureColumn = (field: CrudFieldSchema): void => {
    if (!field.column) {
        field.column = {type: 'varchar', length: 255}
    }
}

/** fillPivotDefaults 多对多中间表空值自动填充 */
export const fillPivotDefaults = (field: CrudFieldSchema, cfg: CrudGenerateConfig): void => {
    if (!field.relation) field.relation = {type: 'belongs_to_many'}
    const pivot = defaultPivot(cfg.table || 'item', field.prop)
    if (!field.relation.pivotTable) field.relation.pivotTable = pivot.pivotTable
    if (!field.relation.foreignKey) field.relation.foreignKey = pivot.foreignKey
    if (!field.relation.relatedKey) field.relation.relatedKey = pivot.relatedKey
}

/** defaultSearchRenderHint 按 form 推断默认 search.render 文案 */
export const defaultSearchRenderHint = (field: CrudFieldSchema): string => {
    if (field.form === false || field.form == null) return '无'
    const mapped = FORM_TO_SEARCH_RENDER[field.form.render]
    return mapped ? SEARCH_RENDER_TYPE_LABEL[mapped] : '无默认'
}

/** ListRenderGuess 列表渲染推测出的 form/search 默认 */
type ListRenderGuess = {
    form: FormRenderType
    search?: { mode: SearchMode; render?: SearchRenderType } // 无 search 表示不参与搜索
}

/**
 * LIST_TO_RENDER_GUESS 列表渲染 → 表单/搜索默认
 * 用途：字段详情切换 list.render 时同步填充
 */
const LIST_TO_RENDER_GUESS: Record<ListRenderType, ListRenderGuess> = {
    text: {form: 'input', search: {mode: 'keyword'}},
    number: {form: 'number', search: {mode: 'advanced', render: 'number_range'}},
    status: {form: 'radio', search: {mode: 'advanced', render: 'select'}},
    tag: {form: 'select', search: {mode: 'advanced', render: 'select'}},
    tags: {form: 'multi_select', search: {mode: 'advanced'}},
    color: {form: 'color'},
    image: {form: 'image'},
    datetime: {form: 'datetime', search: {mode: 'advanced', render: 'datetime_range'}},
    bool: {form: 'switch', search: {mode: 'advanced', render: 'bool'}},
}

/** applyListRenderGuess 按列表渲染推测填充 form/search */
export const applyListRenderGuess = (field: CrudFieldSchema, render: ListRenderType): void => {
    const guess = LIST_TO_RENDER_GUESS[render]
    if (typeof field.form === 'object' && field.form) {
        field.form.render = guess.form
    } else {
        field.form = {render: guess.form}
    }
    if (guess.search) {
        const prev = field.search
        field.search = {
            mode: guess.search.mode,
            ...(guess.search.render ? {render: guess.search.render} : {}),
            ...(prev?.placeholder ? {placeholder: prev.placeholder} : {}),
            ...(prev?.optionsKey ? {optionsKey: prev.optionsKey} : {}),
            ...(prev?.statusMapKey ? {statusMapKey: prev.statusMapKey} : {}),
        }
    } else {
        delete field.search
    }
}
