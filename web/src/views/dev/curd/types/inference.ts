import type { CrudFieldColumnConfig } from './field'
import type { FormRenderType } from './form-render'
import type { ListRenderType } from './list-render'
import type { RelationType } from './relation'
import type { SearchRenderType } from './search-render'

/** FieldInferenceSearch 搜索层推测配置 */
export type FieldInferenceSearch = {
    keyword?: boolean // 是否启用关键词搜索
    advanced?: boolean // 是否启用高级搜索
    render?: SearchRenderType // 高级搜索控件
}

/** FieldInferenceList 列表层推测配置 */
export type FieldInferenceList =
    | ListRenderType
    | false
    | {
          render: ListRenderType
          width?: number
          minWidth?: number
          relationProp?: string
      }

/** FieldInferenceRelation 关联层推测配置 */
export type FieldInferenceRelation = {
    type: RelationType
    needPivot?: true // belongs_to_many 需补中间表
}

/** FieldInferenceForm 表单层推测配置 */
export type FieldInferenceForm = {
    render: FormRenderType
    required?: boolean
    maxLength?: number
    numberMin?: number
    numberMax?: number
    numberStep?: number
}

/** FieldInferenceColumn 列定义推测（false 表示不生成物理列） */
export type FieldInferenceColumn = Omit<CrudFieldColumnConfig, 'enabled'>

/** FieldInference 字段模板推测各层初始配置 */
export type FieldInference = {
    column?: FieldInferenceColumn | false // false 表示关闭 column
    relation?: FieldInferenceRelation | false // false 表示关闭 relation
    list?: FieldInferenceList // false 表示关闭列表
    search?: FieldInferenceSearch | false // false 表示关闭搜索
    form?: FieldInferenceForm | false // false 表示不进表单
}
