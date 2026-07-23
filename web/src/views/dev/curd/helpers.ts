import {
    FIELD_TYPE_LIST,
    fieldTypeDefOf,
    SEARCH_RENDER,
    SEARCH_RENDER_TYPE_LABEL,
} from '@/views/dev/curd/constants'
import { applyRenderExt, relatedNameFromProp } from '@/views/dev/curd/fieldExt'
import type {
    CrudFieldSchema,
    CrudGenerateConfig,
    CrudMenuLevel,
    CrudOutputPaths,
    FieldInference,
    FieldInferenceList,
    FieldType,
    FormRenderType,
    SearchRenderType,
} from '@/views/dev/curd/types'
import { isLayerEnabled } from '@/views/dev/curd/types'

/** FIELD_TYPES MySQL 列类型选项 */
export const FIELD_TYPES: FieldType[] = [...FIELD_TYPE_LIST]

/** defaultPivot 多对多中间表默认（relatedTable 优先于 prop 推断关联名） */
export const defaultPivot = (
    mainTable: string,
    prop: string,
    relatedTable?: string,
): { pivotTable: string; foreignKey: string; relatedKey: string } => {
    const related = relatedTable?.trim()
        ? relatedTable.trim().split('_').filter(Boolean).pop() || relatedTable.trim()
        : relatedNameFromProp(prop)
    const entitySeg = mainTable.split('_').filter(Boolean).pop() || 'item'
    return {
        pivotTable: `${mainTable}_${related}`,
        foreignKey: `${entitySeg}_id`,
        relatedKey: `${related}_id`,
    }
}

/** capitalizePart 表名片段首字母大写 */
const capitalizePart = (p: string): string => p.charAt(0).toUpperCase() + p.slice(1).toLowerCase()

/** PHP 不可用作 class 名的保留字（大小写不敏感） */
const PHP_RESERVED_CLASS = new Set([
    'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone',
    'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty',
    'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'enum', 'eval',
    'exit', 'extends', 'final', 'finally', 'fn', 'for', 'foreach', 'function', 'global', 'goto',
    'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface',
    'isset', 'list', 'match', 'namespace', 'new', 'or', 'print', 'private', 'protected',
    'public', 'readonly', 'require', 'require_once', 'return', 'static', 'switch', 'throw',
    'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield', 'from',
    'int', 'float', 'bool', 'string', 'true', 'false', 'null', 'void', 'iterable', 'object',
    'mixed', 'never', 'self', 'parent',
])

/** safeEntityClassName 保留字时追加 Entity 后缀 */
export const safeEntityClassName = (entity: string): string =>
    PHP_RESERVED_CLASS.has(entity.toLowerCase()) ? `${entity}Entity` : entity

/** defaultPaths 空相对路径占位 */
const defaultPaths = (): CrudOutputPaths => ({
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
    const entity = safeEntityClassName(capitalizePart(last))
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

/** fillMenuLevelIcons 补全 menus 默认图标（menu→Folder，view→Document） */
export const fillMenuLevelIcons = (menus: CrudMenuLevel[]): void => {
    menus.forEach((m, i) => {
        if (!m.icon?.trim()) {
            m.icon = m.type === 'view' ? 'el:Document' : 'el:Folder'
        }
        if (m.type !== 'view' && i === 0 && m.sort == null) {
            m.sort = 0
        }
    })
}

/** guessMenusFromParts 末段为 view，前面为 menu；单段仅 view */
const guessMenusFromParts = (parts: string[]): CrudMenuLevel[] => {
    let menus: CrudMenuLevel[]
    if (parts.length === 0) {
        menus = [{ type: 'view', path: 'item', title: 'Item', sort: 0, enabled: true }]
    } else if (parts.length === 1) {
        const p = parts[0]!.toLowerCase()
        menus = [
            {
                type: 'view',
                path: p,
                title: capitalizePart(parts[0]!),
                sort: 0,
                enabled: true,
            },
        ]
    } else {
        menus = []
        parts.slice(0, -1).forEach((raw) => {
            menus.push({
                type: 'menu',
                path: raw.toLowerCase(),
                title: capitalizePart(raw),
                enabled: true,
            })
        })
        const last = parts[parts.length - 1] ?? 'item'
        menus.push({
            type: 'view',
            path: last.toLowerCase(),
            title: capitalizePart(last),
            enabled: true,
        })
    }
    fillMenuLevelIcons(menus)
    return menus
}

/** leafView 取末级页面项 */
export const leafView = (menus: CrudMenuLevel[]): CrudMenuLevel | null => {
    if (!menus.length) return null
    const last = menus[menus.length - 1]
    return last?.type === 'view' ? last : null
}

/** parseControllerPath 从 controller 相对路径解析 moduleDir + entity */
const parseControllerPath = (
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
    if (!cfg.menuIcon) cfg.menuIcon = 'el:Folder'
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
        .trim()
        .toLowerCase()
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
    uiType: 'primary_key',
    column: {
        type: 'bigint',
        unsigned: true,
        nullable: false,
        autoIncrement: true,
        primary: true,
    },
    relation: { type: 'belongs_to', enabled: false },
    list: { render: 'text', width: 72 },
    search: { keyword: true, enabled: false },
    form: { render: 'input', enabled: false },
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
        options: {},
        generateAuth: true,
        writeMode: 'skip',
        menuIcon: 'el:Folder',
        menuSort: 0,
    }
    syncDerivedFields(cfg)
    return cfg
}

/** cloneConfig 深拷贝配置 */
export const cloneConfig = (cfg: CrudGenerateConfig): CrudGenerateConfig =>
    JSON.parse(JSON.stringify(cfg)) as CrudGenerateConfig

/** ensureColumn 保证 column 启用并补默认类型 */
export const ensureColumn = (field: CrudFieldSchema): void => {
    field.column.enabled = true
    if (!field.column.type) {
        field.column.type = 'varchar'
        field.column.length = 255
    }
}

/** ParsedMysqlType SHOW COLUMNS Type 解析结果 */
export type ParsedMysqlType = {
    type: string
    length?: number
    precision?: number
    scale?: number
    unsigned: boolean
}

/** parseMysqlType 解析 SHOW COLUMNS 的 Type 字符串（如 bigint unsigned、varchar(100)） */
export const parseMysqlType = (typeRaw: string): ParsedMysqlType => {
    const raw = typeRaw.trim().toLowerCase()
    const unsigned = raw.includes('unsigned')
    const base = raw.replace(/\s+unsigned.*/, '').trim()
    let name = base
    let length: number | undefined
    let precision: number | undefined
    let scale: number | undefined
    const m = /^([a-z]+)\((.+)\)$/.exec(base)
    if (m) {
        name = m[1]!
        const args = m[2]!
        if (name === 'decimal' || name === 'numeric') {
            const parts = args.split(',').map((s) => s.trim())
            precision = Number(parts[0] ?? 12) || 12
            scale = Number(parts[1] ?? 2) || 0
        } else if (/^\d+$/.test(args)) {
            length = Number(args)
        }
    }

    const type = ((): string => {
        switch (name) {
            case 'bigint':
                return 'bigint'
            case 'int':
            case 'integer':
            case 'mediumint':
            case 'smallint':
                return 'int'
            case 'tinyint':
            case 'bool':
            case 'boolean':
                return 'tinyint'
            case 'decimal':
            case 'numeric':
            case 'float':
            case 'double':
            case 'real':
                return 'decimal'
            case 'varchar':
                return 'varchar'
            case 'char':
                return 'char'
            case 'text':
            case 'tinytext':
                return 'text'
            case 'mediumtext':
                return 'mediumtext'
            case 'longtext':
                return 'longtext'
            case 'date':
                return 'date'
            case 'datetime':
                return 'datetime'
            case 'timestamp':
                return 'timestamp'
            case 'json':
                return 'json'
            case 'enum':
            case 'set':
                return 'varchar'
            default:
                return name || 'varchar'
        }
    })()

    if (type === 'varchar' && length == null && (name === 'enum' || name === 'set')) {
        length = 50
    }
    if (type === 'varchar' && length == null) {
        length = 255
    }

    return {
        type,
        ...(length != null ? { length } : {}),
        ...(precision != null ? { precision } : {}),
        ...(scale != null ? { scale } : {}),
        unsigned,
    }
}

/** applyParsedColumnType 将解析结果写回 column 类型相关字段（保留 enabled/nullable 等由调用方决定） */
export const applyParsedColumnType = (
    field: CrudFieldSchema,
    parsed: ParsedMysqlType,
    extras?: Partial<CrudFieldSchema['column']>,
): void => {
    ensureColumn(field)
    field.column.type = parsed.type
    field.column.unsigned = parsed.unsigned
    if (parsed.length != null) field.column.length = parsed.length
    else delete field.column.length
    if (parsed.precision != null) field.column.precision = parsed.precision
    else delete field.column.precision
    if (parsed.scale != null) field.column.scale = parsed.scale
    else delete field.column.scale
    if (extras) Object.assign(field.column, extras)
}

/** fillPivotDefaults 多对多中间表自动填充（relatedTable 来自远程关联表；force 覆盖已有值） */
export const fillPivotDefaults = (
    field: CrudFieldSchema,
    cfg: CrudGenerateConfig,
    relatedTable?: string,
    force = false,
): void => {
    field.relation.enabled = true
    if (!field.relation.type) field.relation.type = 'belongs_to_many'
    const pivot = defaultPivot(cfg.table || 'item', field.prop, relatedTable)
    if (force || !field.relation.pivotTable) field.relation.pivotTable = pivot.pivotTable
    if (force || !field.relation.foreignKey) field.relation.foreignKey = pivot.foreignKey
    if (force || !field.relation.relatedKey) field.relation.relatedKey = pivot.relatedKey
}

/** inferSearchRenderFromColumn 按 column.type 推断 search.render */
const inferSearchRenderFromColumn = (field: CrudFieldSchema): SearchRenderType => {
    const ct = isLayerEnabled(field.column) ? field.column.type : undefined
    switch (ct) {
        case 'tinyint':
            return SEARCH_RENDER.BOOL
        case 'int':
        case 'bigint':
        case 'decimal':
            return SEARCH_RENDER.NUMBER_RANGE
        case 'date':
            return SEARCH_RENDER.DATE_RANGE
        case 'datetime':
        case 'timestamp':
            return SEARCH_RENDER.DATETIME_RANGE
        default:
            return SEARCH_RENDER.INPUT
    }
}

/** defaultSearchRenderHint 推断默认 search.render 文案（显式 render → column） */
export const defaultSearchRenderHint = (field: CrudFieldSchema): string => {
    const render = inferSearchRender(field)
    return SEARCH_RENDER_TYPE_LABEL[render] ?? render
}

/** inferSearchRender 推断 search.render（显式 render → column） */
export const inferSearchRender = (field: CrudFieldSchema): SearchRenderType => {
    if (field.search.render) {
        return field.search.render
    }
    return inferSearchRenderFromColumn(field)
}

/** applyListInference 写回 list 层 */
const applyListInference = (field: CrudFieldSchema, list: FieldInferenceList | undefined): void => {
    if (list === false) {
        field.list = { render: 'text', enabled: false }
        return
    }
    if (!list) return
    const cfg = typeof list === 'string' ? { render: list, minWidth: 120 } : { minWidth: 120, ...list }
    field.list = { enabled: true, ...cfg }
}

/** applySearchInference 写回 search 层 */
const applySearchInference = (
    field: CrudFieldSchema,
    search: FieldInference['search'],
): void => {
    if (search === false) {
        field.search = { keyword: false, advanced: false, enabled: false }
        return
    }
    if (!search) return
    field.search = {
        enabled: true,
        keyword: search.keyword === true,
        advanced: search.advanced === true,
        ...(search.render ? { render: search.render } : {}),
    }
}

/** applyLayerInference 按 inference 写回 column/relation/list/search/form */
const applyLayerInference = (
    field: CrudFieldSchema,
    inference: FieldInference | undefined,
    cfg?: CrudGenerateConfig,
    ext?: { needEnum?: boolean; needRemote?: boolean; defaultRender?: FormRenderType },
): FormRenderType | undefined => {
    if (!inference) return undefined

    let formRender: FormRenderType | undefined

    if (inference.form === false) {
        field.form = { render: ext?.defaultRender ?? 'input', enabled: false }
    } else if (inference.form) {
        const { render, ...params } = inference.form
        formRender = render
        field.form = {
            enabled: true,
            render,
            ...params,
        }
    } else if (ext?.defaultRender) {
        formRender = ext.defaultRender
        field.form = { enabled: true, render: ext.defaultRender }
    }

    if (inference.column === false) {
        field.column = { type: 'varchar', length: 255, enabled: false }
    } else if (inference.column) {
        field.column = { ...inference.column, enabled: true }
    }

    if (inference.relation === false || !inference.relation) {
        field.relation = { type: 'belongs_to', enabled: false }
    } else {
        field.relation = { type: inference.relation.type, enabled: true }
        if (inference.relation.needPivot && cfg) {
            const pivot = defaultPivot(cfg.table || 'item', field.prop)
            field.relation.pivotTable = field.relation.pivotTable || pivot.pivotTable
            field.relation.foreignKey = field.relation.foreignKey || pivot.foreignKey
            field.relation.relatedKey = field.relation.relatedKey || pivot.relatedKey
        }
    }

    applyListInference(field, inference.list)
    applySearchInference(field, inference.search)

    if (cfg && formRender) {
        applyRenderExt(field, formRender, cfg, {
            needEnum: ext?.needEnum,
            needRemote: ext?.needRemote,
        })
    }

    return formRender
}

/** applyFieldType 字段列表切换模板：直接应用 field.ts inference */
export const applyFieldType = (
    field: CrudFieldSchema,
    type: string,
    cfg: CrudGenerateConfig,
): void => {
    const def = fieldTypeDefOf(type)
    if (!def) return
    field.uiType = type
    applyLayerInference(field, def.inference, cfg, {
        needEnum: def.needEnum,
        needRemote: def.needRemote,
    })
}
