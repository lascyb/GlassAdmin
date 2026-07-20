import { FORM_RENDER } from '@/views/dev/curd/constants'
import { formRenderDefOf } from '@/views/dev/curd/constants/form-render'
import {
    DEFAULT_OPTIONS,
    DEFAULT_SELECT_OPTIONS,
    DEFAULT_STATUS_OPTIONS,
} from '@/views/dev/curd/constants/option-defaults'
import type {
    CrudFieldEnumExt,
    CrudFieldExtConfig,
    CrudFieldRemoteExt,
    CrudFieldSchema,
    CrudGenerateConfig,
    FormRenderType,
    ListRenderType,
} from '@/views/dev/curd/types'
import { isLayerEnabled } from '@/views/dev/curd/types'
import { LIST_RENDER_NEED_ENUM } from '@/views/dev/curd/constants/list-render'

/** mapKeyFromProp 由 prop 生成 STATUS_MAP 风格键名 */
const mapKeyFromProp = (prop: string): string => {
    const base = (prop || 'field').replace(/[^a-zA-Z0-9_]/g, '_').toUpperCase()
    return base.endsWith('_MAP') ? base : `${base}_MAP`
}

/** optionsKeyFromProp 由 prop 生成 OPTIONS 键名 */
const optionsKeyFromProp = (prop: string): string => {
    const base = (prop || 'field').replace(/[^a-zA-Z0-9_]/g, '_').toUpperCase()
    return base.endsWith('_OPTIONS') ? base : `${base}_OPTIONS`
}

/** ensureFieldExt 保证 ext 对象存在 */
const ensureFieldExt = (field: CrudFieldSchema): CrudFieldExtConfig => {
    if (!field.ext) field.ext = {}
    return field.ext
}

/** inferEnumKeyName 由字段推测 options 键名 */
const inferEnumKeyName = (field: CrudFieldSchema): string => {
    if (isLayerEnabled(field.form) && (field.form.render === 'multi_select' || field.form.render === 'checkbox')) {
        return optionsKeyFromProp(field.prop)
    }
    return mapKeyFromProp(field.prop)
}

/** ensureFieldEnumExt 保证 ext.enum 存在 */
export const ensureFieldEnumExt = (field: CrudFieldSchema): CrudFieldEnumExt => {
    const ext = ensureFieldExt(field)
    if (!ext.enum) ext.enum = {}
    return ext.enum
}

/** ensureFieldRemoteExt 保证 ext.remote 存在 */
export const ensureFieldRemoteExt = (field: CrudFieldSchema): CrudFieldRemoteExt => {
    const ext = ensureFieldExt(field)
    if (!ext.remote) ext.remote = {}
    return ext.remote
}

/** remoteApiOf 取远程 api */
export const remoteApiOf = (field: CrudFieldSchema | null | undefined): string =>
    field?.ext?.remote?.api?.trim() || ''

/** isRemoteField 远程下拉/多选（走 ext.remote + 关联，列表 tag/tags 无需本地枚举） */
export const isRemoteField = (field: CrudFieldSchema | null | undefined): boolean => {
    if (!field) return false
    if (field.uiType === 'remote_select' || field.uiType === 'remote_multi_select') return true
    if (remoteApiOf(field)) return true
    if (isLayerEnabled(field.form)) {
        const r = field.form.render
        if (r === 'remote_select' || r === 'remote_multi_select') return true
    }
    if (isLayerEnabled(field.search)) {
        const r = field.search.render
        if (r === 'remote_select' || r === 'remote_multi_select') return true
    }
    return false
}

/** enumKeyOf 取字段枚举键名（远程字段不推测枚举） */
export const enumKeyOf = (field: CrudFieldSchema | null | undefined): string => {
    if (!field || isRemoteField(field)) return ''
    if (field.ext?.enum?.key) return field.ext.enum.key
    return inferEnumKeyName(field)
}

/** listRenderNeedsEnum 列表 tag/tags 等是否需要本地枚举（远程字段排除） */
export const listRenderNeedsEnum = (
    field: CrudFieldSchema | null | undefined,
    listRender?: ListRenderType | string,
): boolean => {
    if (!field || !listRender || isRemoteField(field)) return false
    return (LIST_RENDER_NEED_ENUM as readonly string[]).includes(listRender)
}

/** relatedNameFromProp 从 prop 推测关联名（tag_ids → tag） */
export const relatedNameFromProp = (prop: string): string =>
    prop.replace(/_ids?$/i, '').replace(/_id$/i, '') || 'related'

/** ensureRemoteExt 远程数据源占位写入 ext.remote */
const ensureRemoteExt = (field: CrudFieldSchema): void => {
    ensureFieldRemoteExt(field)
}

/** applyRenderExt 按 needEnum/needRemote 补 ext.enum / ext.remote */
export const applyRenderExt = (
    field: CrudFieldSchema,
    render: FormRenderType,
    cfg: CrudGenerateConfig,
    flags?: { needEnum?: boolean; needRemote?: boolean },
): void => {
    const def = formRenderDefOf(render)
    const needEnum = flags?.needEnum ?? def?.needEnum
    const needRemote = flags?.needRemote ?? def?.needRemote
    const prevEnumKey = field.ext?.enum?.key
    const prevRemote = field.ext?.remote ? { ...field.ext.remote } : undefined

    if (needEnum) {
        if (!cfg.options) cfg.options = {}
        const enumExt = ensureFieldEnumExt(field)
        const key = enumExt.key || inferEnumKeyName(field)
        enumExt.key = key
        if (!cfg.options[key]?.length) {
            if (render === FORM_RENDER.MULTI_SELECT || render === FORM_RENDER.CHECKBOX) {
                cfg.options[key] = structuredClone(DEFAULT_OPTIONS)
            } else if (render === FORM_RENDER.SELECT) {
                cfg.options[key] = structuredClone(DEFAULT_SELECT_OPTIONS)
            } else {
                cfg.options[key] = structuredClone(DEFAULT_STATUS_OPTIONS)
            }
        }
        if (prevEnumKey) enumExt.key = prevEnumKey
    } else if (field.ext) {
        delete field.ext.enum
    }

    if (needRemote) {
        ensureRemoteExt(field)
        if (prevRemote && field.ext?.remote) {
            field.ext.remote = { ...prevRemote, ...field.ext.remote }
        }
    } else if (field.ext) {
        delete field.ext.remote
    }

    if (field.ext && !field.ext.enum && !field.ext.remote) {
        delete field.ext
    }
}
