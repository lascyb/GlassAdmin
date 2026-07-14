import type {
    CrudFieldSchema,
    CrudGenerateConfig,
    CrudOptionItem,
    CrudStatusMapEntry,
    FormRenderType,
    ListRenderType,
    SearchMode,
    SearchRenderType,
} from '@/crud/types';
import { FORM_RENDER_FIELD_TYPE, FORM_RENDER_TYPE_LABEL } from '@/crud/types';

/**
 * FieldControlIntent 业务语义字段类型
 * {text:文本, textarea:多行, number:数字, amount:金额, switch:开关,
 *  status:状态枚举, select:下拉枚举, remote:远程单选, remote_multi:远程多选,
 *  date:日期, datetime:日期时间, image:图片, color:颜色,
 *  list_only:仅列表, none:不进界面}
 */
export type FieldControlIntent =
    | 'text'
    | 'textarea'
    | 'number'
    | 'amount'
    | 'switch'
    | 'status'
    | 'select'
    | 'remote'
    | 'remote_multi'
    | 'date'
    | 'datetime'
    | 'image'
    | 'color'
    | 'list_only'
    | 'none'

/** FIELD_CONTROL_OPTIONS 字段类型选项（工作台大选择） */
export const FIELD_CONTROL_OPTIONS: { value: FieldControlIntent; label: string }[] = [
    { value: 'text', label: '文本' },
    { value: 'textarea', label: '多行' },
    { value: 'number', label: '数字' },
    { value: 'amount', label: '金额' },
    { value: 'switch', label: '开关' },
    { value: 'status', label: '状态枚举' },
    { value: 'select', label: '下拉枚举' },
    { value: 'remote', label: '远程单选' },
    { value: 'remote_multi', label: '远程多选' },
    { value: 'date', label: '日期' },
    { value: 'datetime', label: '日期时间' },
    { value: 'image', label: '图片' },
    { value: 'color', label: '颜色' },
    { value: 'list_only', label: '仅列表' },
    { value: 'none', label: '不进界面' },
]

/** FIELD_CONTROL_LABEL 字段类型中文名 */
export const FIELD_CONTROL_LABEL: Record<FieldControlIntent, string> = Object.fromEntries(
    FIELD_CONTROL_OPTIONS.map((o) => [o.value, o.label]),
) as Record<FieldControlIntent, string>

/** FieldTemplateId 左栏添加模板 */
export type FieldTemplateId =
    | 'title'
    | 'status'
    | 'switch'
    | 'category'
    | 'tags'
    | 'amount'
    | 'datetime'
    | 'cover'
    | 'blank'

/** FIELD_TEMPLATE_OPTIONS 模板优先添加项（blank 为高级空白） */
export const FIELD_TEMPLATE_OPTIONS: { id: FieldTemplateId; label: string; advanced?: boolean }[] = [
    { id: 'title', label: '标题' },
    { id: 'status', label: '状态' },
    { id: 'switch', label: '开关' },
    { id: 'category', label: '分类远程' },
    { id: 'tags', label: '标签多对多' },
    { id: 'amount', label: '金额' },
    { id: 'datetime', label: '时间' },
    { id: 'cover', label: '封面' },
    { id: 'blank', label: '空白字段', advanced: true },
]

/** IntentLayerDefaults 字段类型 → 五层默认 */
type IntentLayerDefaults = {
    column?: CrudFieldSchema['column']
    relation?: CrudFieldSchema['relation']
    list?: CrudFieldSchema['list'] | false
    search?: { mode: SearchMode; render?: SearchRenderType } | false
    form: false | { render: FormRenderType; required?: boolean; numberMin?: number; numberMax?: number; numberStep?: number; maxLength?: number; placeholder?: string }
    /** enumKind 枚举配套 {map:写 maps+statusMapKey, options:写 options+optionsKey, none:无} */
    enumKind?: 'map' | 'options' | 'none'
    /** needRemote 需要远程数据源 */
    needRemote?: boolean
    /** needPivot 多对多中间表 */
    needPivot?: boolean
}

/** INTENT_DEFAULTS 字段类型五层默认表 */
const INTENT_DEFAULTS: Record<FieldControlIntent, IntentLayerDefaults> = {
    text: {
        column: { type: 'varchar', length: 255, nullable: true },
        list: { render: 'text', minWidth: 120 },
        search: { mode: 'keyword' },
        form: { render: 'input' },
    },
    textarea: {
        column: { type: 'text', nullable: true },
        list: false,
        search: false,
        form: { render: 'textarea', maxLength: 2000 },
    },
    number: {
        column: { type: 'int', nullable: false, default: 0 },
        list: { render: 'number', width: 80 },
        search: { mode: 'advanced', render: 'number_range' },
        form: { render: 'number', numberMin: 0, numberMax: 99999 },
    },
    amount: {
        column: { type: 'decimal', precision: 12, scale: 2, nullable: false, default: 0 },
        list: { render: 'number', width: 100 },
        search: { mode: 'advanced', render: 'number_range' },
        form: { render: 'number', numberMin: 0, numberMax: 9999999, numberStep: 0.01 },
    },
    switch: {
        column: { type: 'tinyint', nullable: false, default: 0 },
        list: { render: 'bool', width: 72 },
        search: { mode: 'advanced', render: 'bool' },
        form: { render: 'switch' },
    },
    status: {
        column: { type: 'tinyint', nullable: false, default: 1 },
        list: { render: 'status', width: 88 },
        search: { mode: 'advanced', render: 'select' },
        form: { render: 'radio' },
        enumKind: 'map',
    },
    select: {
        column: { type: 'tinyint', nullable: false, default: 1 },
        list: { render: 'tag', width: 88 },
        search: { mode: 'advanced', render: 'select' },
        form: { render: 'select' },
        enumKind: 'map',
    },
    remote: {
        column: { type: 'bigint', unsigned: true, nullable: true, default: null },
        relation: { type: 'belongs_to' },
        list: { render: 'tag', width: 110 },
        search: { mode: 'advanced', render: 'remote_select' },
        form: { render: 'remote_select' },
        needRemote: true,
    },
    remote_multi: {
        relation: { type: 'belongs_to_many' },
        list: { render: 'tags', minWidth: 160 },
        search: { mode: 'advanced', render: 'remote_multi_select' },
        form: { render: 'remote_multi_select' },
        needRemote: true,
        needPivot: true,
    },
    date: {
        column: { type: 'date', nullable: true, default: null },
        list: { render: 'text', width: 120 },
        search: { mode: 'advanced', render: 'date_range' },
        form: { render: 'date' },
    },
    datetime: {
        column: { type: 'bigint', nullable: true, default: null },
        list: { render: 'datetime', width: 170 },
        search: { mode: 'advanced', render: 'datetime_range' },
        form: { render: 'datetime' },
    },
    image: {
        column: { type: 'varchar', length: 255, nullable: true, default: null },
        list: { render: 'image', width: 88 },
        search: false,
        form: { render: 'image' },
    },
    color: {
        column: { type: 'varchar', length: 32, nullable: true, default: null },
        list: { render: 'color', width: 72 },
        search: false,
        form: { render: 'color' },
    },
    list_only: {
        column: { type: 'varchar', length: 255, nullable: true },
        list: { render: 'text', minWidth: 120 },
        search: false,
        form: false,
    },
    none: {
        column: { type: 'bigint', nullable: false, default: 0 },
        list: false,
        search: false,
        form: false,
    },
}

/** DEFAULT_STATUS_MAP 状态类默认可跑 maps 项 */
export const DEFAULT_STATUS_MAP: Record<string, CrudStatusMapEntry> = {
    '1': { label: '启用', type: 'success' },
    '0': { label: '禁用', type: 'info' },
}

/** DEFAULT_SELECT_MAP 下拉枚举默认可跑 maps 项 */
export const DEFAULT_SELECT_MAP: Record<string, CrudStatusMapEntry> = {
    '1': { label: '选项一', type: 'info' },
    '2': { label: '选项二', type: 'warning' },
    '3': { label: '选项三', type: 'danger' },
}

/** DEFAULT_OPTIONS 静态 options 默认可跑项 */
export const DEFAULT_OPTIONS: CrudOptionItem[] = [
    { value: 'a', label: '选项 A' },
    { value: 'b', label: '选项 B' },
    { value: 'c', label: '选项 C' },
]

/** mapKeyFromProp 由 prop 生成 STATUS_MAP 风格键名 */
export const mapKeyFromProp = (prop: string): string => {
    const base = (prop || 'field').replace(/[^a-zA-Z0-9_]/g, '_').toUpperCase()
    return base.endsWith('_MAP') ? base : `${base}_MAP`
}

/** optionsKeyFromProp 由 prop 生成 OPTIONS 键名 */
export const optionsKeyFromProp = (prop: string): string => {
    const base = (prop || 'field').replace(/[^a-zA-Z0-9_]/g, '_').toUpperCase()
    return base.endsWith('_OPTIONS') ? base : `${base}_OPTIONS`
}

/** relatedNameFromProp 从 prop 推测关联名（tag_ids → tag） */
const relatedNameFromProp = (prop: string): string =>
    prop.replace(/_ids?$/i, '').replace(/_id$/i, '') || 'related'

/** defaultPivot 多对多中间表默认 */
export const defaultPivot = (
    table: string,
    prop: string,
): { pivotTable: string; foreignKey: string; relatedKey: string } => {
    const related = relatedNameFromProp(prop)
    const entitySeg = table.split('_').filter(Boolean).pop() || 'item'
    return {
        pivotTable: `${table}_${related}`,
        foreignKey: `${entitySeg}_id`,
        relatedKey: `${related}_id`,
    }
}

/** defaultRelationProp 列表关联展示默认 */
const defaultRelationProp = (intent: FieldControlIntent, prop: string): string | undefined => {
    if (intent === 'remote') {
        const name = relatedNameFromProp(prop)
        return `${name}.name`
    }
    if (intent === 'remote_multi') {
        return relatedNameFromProp(prop) + 's'
    }
    return undefined
}

/** detectControlIntent 从现有字段反推字段类型意图 */
export const detectControlIntent = (field: CrudFieldSchema): FieldControlIntent => {
    const form = field.form
    if (form === false || form == null) {
        return field.list ? 'list_only' : 'none'
    }
    const render = form.render
    if (render === 'switch') return 'switch'
    if (render === 'radio') return 'status'
    if (render === 'select') return 'select'
    if (render === 'remote_select') return 'remote'
    if (render === 'remote_multi_select' || render === 'multi_select') {
        return field.relation?.type === 'belongs_to_many' || render === 'remote_multi_select'
            ? 'remote_multi'
            : 'select'
    }
    if (render === 'number') {
        return field.column?.type === 'decimal' ? 'amount' : 'number'
    }
    if (render === 'textarea') return 'textarea'
    if (render === 'date') return 'date'
    if (render === 'datetime') return 'datetime'
    if (render === 'image') return 'image'
    if (render === 'color') return 'color'
    if (render === 'input') return 'text'
    return 'text'
}

/** ensureEnumMaps 保证枚举 maps 存在并绑到字段 */
const ensureEnumMaps = (
    field: CrudFieldSchema,
    cfg: CrudGenerateConfig,
    intent: FieldControlIntent,
): void => {
    if (!cfg.maps) cfg.maps = {}
    const key = field.form && field.form !== false && field.form.statusMapKey
        ? field.form.statusMapKey
        : mapKeyFromProp(field.prop)
    if (!cfg.maps[key] || Object.keys(cfg.maps[key]!).length === 0) {
        cfg.maps[key] = structuredClone(intent === 'select' ? DEFAULT_SELECT_MAP : DEFAULT_STATUS_MAP)
    }
    if (field.form && field.form !== false) field.form.statusMapKey = key
    if (field.list) field.list.statusMapKey = key
    if (field.search) field.search.statusMapKey = key
}

/** ensureRemoteOptionsKey 远程数据源占位 */
const ensureRemoteOptionsKey = (field: CrudFieldSchema): void => {
    const related = relatedNameFromProp(field.prop)
    const entity = related.charAt(0).toUpperCase() + related.slice(1)
    const placeholder = `example.${entity}/list`
    if (field.form && field.form !== false && !field.form.optionsKey) {
        field.form.optionsKey = placeholder
    }
}

/** applyControlIntent 字段类型切换：一键重算五层默认 */
export const applyControlIntent = (
    field: CrudFieldSchema,
    intent: FieldControlIntent,
    cfg: CrudGenerateConfig,
): void => {
    const d = INTENT_DEFAULTS[intent]
    const prevForm = field.form && field.form !== false ? { ...field.form } : null
    const prevList = field.list ? { ...field.list } : null
    const prevSearch = field.search ? { ...field.search } : null

    // column
    if (d.column) {
        field.column = { ...d.column }
    } else {
        delete field.column
    }

    // relation
    if (d.relation) {
        field.relation = { ...d.relation }
        if (d.needPivot) {
            const pivot = defaultPivot(cfg.table || 'item', field.prop)
            field.relation.pivotTable = field.relation.pivotTable || pivot.pivotTable
            field.relation.foreignKey = field.relation.foreignKey || pivot.foreignKey
            field.relation.relatedKey = field.relation.relatedKey || pivot.relatedKey
        }
    } else {
        delete field.relation
    }

    // list
    if (d.list === false) {
        delete field.list
    } else if (d.list) {
        const relationProp = defaultRelationProp(intent, field.prop)
        field.list = {
            ...d.list,
            ...(prevList?.width != null ? { width: prevList.width } : {}),
            ...(prevList?.minWidth != null && !d.list.width ? { minWidth: prevList.minWidth } : {}),
            ...(relationProp ? { relationProp } : {}),
        }
    }

    // search
    if (d.search === false || !d.search) {
        delete field.search
    } else {
        field.search = {
            mode: d.search.mode,
            ...(d.search.render ? { render: d.search.render } : {}),
            ...(prevSearch?.placeholder ? { placeholder: prevSearch.placeholder } : {}),
        }
    }

    // form
    if (d.form === false) {
        field.form = false
    } else {
        field.form = {
            render: d.form.render,
            ...(d.form.required != null ? { required: d.form.required } : {}),
            ...(d.form.placeholder ? { placeholder: d.form.placeholder } : {}),
            ...(d.form.numberMin != null ? { numberMin: d.form.numberMin } : {}),
            ...(d.form.numberMax != null ? { numberMax: d.form.numberMax } : {}),
            ...(d.form.numberStep != null ? { numberStep: d.form.numberStep } : {}),
            ...(d.form.maxLength != null ? { maxLength: d.form.maxLength } : {}),
            ...(prevForm?.optionsKey && d.needRemote ? { optionsKey: prevForm.optionsKey } : {}),
            ...(prevForm?.statusMapKey && d.enumKind === 'map' ? { statusMapKey: prevForm.statusMapKey } : {}),
        }
    }

    if (d.enumKind === 'map') {
        ensureEnumMaps(field, cfg, intent)
    }
    if (d.needRemote) {
        ensureRemoteOptionsKey(field)
    }
}

/** buildFieldFromTemplate 按模板生成完整字段 schema */
export const buildFieldFromTemplate = (
    id: FieldTemplateId,
    index: number,
    cfg: CrudGenerateConfig,
): CrudFieldSchema => {
    const blank = (): CrudFieldSchema => ({
        prop: `field_${index + 1}`,
        label: `字段${index + 1}`,
        column: { type: 'varchar', length: 255, nullable: true },
        list: { render: 'text', minWidth: 120 },
        search: { mode: 'keyword' },
        form: { render: 'input' },
    })

    let field: CrudFieldSchema
    let intent: FieldControlIntent = 'text'

    switch (id) {
        case 'title':
            field = {
                prop: 'title',
                label: '标题',
                column: { type: 'varchar', length: 100, nullable: false },
                list: { render: 'text', minWidth: 140 },
                search: { mode: 'keyword' },
                form: { render: 'input', required: true, placeholder: '请输入标题', maxLength: 100 },
            }
            intent = 'text'
            break
        case 'status':
            field = {
                prop: 'status',
                label: '状态',
                column: { type: 'tinyint', nullable: false, default: 1 },
                list: { render: 'status', width: 88 },
                search: { mode: 'advanced' },
                form: { render: 'radio' },
            }
            intent = 'status'
            break
        case 'switch':
            field = {
                prop: 'is_top',
                label: '置顶',
                column: { type: 'tinyint', nullable: false, default: 0 },
                list: { render: 'bool', width: 72 },
                search: { mode: 'advanced' },
                form: { render: 'switch' },
            }
            intent = 'switch'
            break
        case 'category':
            field = {
                prop: 'category_id',
                label: '分类',
                column: { type: 'bigint', unsigned: true, nullable: true, default: null },
                relation: { type: 'belongs_to' },
                list: { render: 'tag', relationProp: 'category.name', width: 110 },
                search: { mode: 'advanced' },
                form: { render: 'remote_select', optionsKey: 'example.Category/list' },
            }
            intent = 'remote'
            break
        case 'tags': {
            const pivot = defaultPivot(cfg.table || 'item', 'tag_ids')
            field = {
                prop: 'tag_ids',
                label: '标签',
                relation: {
                    type: 'belongs_to_many',
                    pivotTable: pivot.pivotTable,
                    foreignKey: pivot.foreignKey,
                    relatedKey: pivot.relatedKey,
                },
                list: { render: 'tags', relationProp: 'tags', minWidth: 160 },
                search: { mode: 'advanced' },
                form: { render: 'remote_multi_select', optionsKey: 'example.Tag/list' },
            }
            intent = 'remote_multi'
            break
        }
        case 'amount':
            field = {
                prop: 'amount',
                label: '金额',
                column: { type: 'decimal', precision: 12, scale: 2, nullable: false, default: 0 },
                list: { render: 'number', width: 100 },
                search: { mode: 'advanced' },
                form: { render: 'number', numberMin: 0, numberMax: 9999999, numberStep: 0.01 },
            }
            intent = 'amount'
            break
        case 'datetime':
            field = {
                prop: 'publish_time',
                label: '发布时间',
                column: { type: 'bigint', nullable: true, default: null },
                list: { render: 'datetime', width: 170 },
                search: { mode: 'advanced' },
                form: { render: 'datetime' },
            }
            intent = 'datetime'
            break
        case 'cover':
            field = {
                prop: 'cover',
                label: '封面',
                column: { type: 'varchar', length: 255, nullable: true, default: null },
                list: { render: 'image', width: 88 },
                form: { render: 'image' },
            }
            intent = 'image'
            break
        case 'blank':
        default:
            field = blank()
            intent = 'text'
            break
    }

    // 枚举类补默认可跑 maps
    if (intent === 'status' || intent === 'select') {
        ensureEnumMaps(field, cfg, intent)
    }
    return field
}

/** controlIntentLabel 字段类型展示名（导航摘要） */
export const controlIntentLabel = (field: CrudFieldSchema): string =>
    FIELD_CONTROL_LABEL[detectControlIntent(field)] ??
    (field.form && field.form !== false
        ? (FORM_RENDER_TYPE_LABEL[field.form.render] ?? field.form.render)
        : '无表单')

/** fieldCapabilityChips 能力 chips：列/搜/表 */
export const fieldCapabilityChips = (field: CrudFieldSchema): { key: string; label: string; on: boolean }[] => [
    { key: 'list', label: '列', on: !!field.list },
    { key: 'search', label: '搜', on: !!field.search },
    {
        key: 'form',
        label: '表',
        on: field.form !== false && field.form != null,
    },
]

/** syncColumnTypeFromForm 按 form.render 建议 column.type（有 column 时） */
export const syncColumnTypeFromForm = (field: CrudFieldSchema): void => {
    if (!field.column || !field.form || field.form === false) return
    const suggested = FORM_RENDER_FIELD_TYPE[field.form.render as FormRenderType]
    if (suggested) field.column.type = suggested
}

/** listRenderOfIntent 字段类型对应默认列表渲染（高级覆盖参考） */
export const listRenderOfIntent = (intent: FieldControlIntent): ListRenderType | undefined => {
    const d = INTENT_DEFAULTS[intent]
    if (!d.list || d.list === false) return undefined
    return d.list.render
}
