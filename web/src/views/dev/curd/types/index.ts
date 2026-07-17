/**
 * CRUD 契约类型统一导出
 *
 * 字段契约分层：column / relation / list / search / form / ext
 * 常量见 ../constants/index.ts
 * 字段类型样例：extend/crud/samples/test/
 */

export type { FieldType } from './mysql-field-type'
export type { FormRenderType } from './form-render'
export type { ListRenderType } from './list-render'
export type { SearchRenderType } from './search-render'
export type { RelationType } from './relation'
export type { FieldInference, FieldInferenceList } from './inference'

export type {
    CrudFieldColumnConfig,
    CrudFieldRelationConfig,
    CrudFieldListConfig,
    CrudFieldSearchConfig,
    CrudFieldFormConfig,
    CrudFieldEnumExt,
    CrudFieldRemoteExt,
    CrudFieldExtConfig,
    CrudFieldSchema,
} from './field'
export { isLayerEnabled } from './field'

export type {
    CrudOptionItem,
    CrudOutputPaths,
    CrudMenuLevel,
    CrudGenerateConfig,
} from './config'
