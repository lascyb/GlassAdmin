import type { SearchRenderType } from '../types/search-render'
import type { NamedOption, NamedOptionGroup } from '../types/option'

/** SearchRenderDef 搜索控件项定义（契约 + UI） */
type SearchRenderDef = {
    key: string
    value: string
    label: string
    group: string
    needEnum?: true // 需要枚举/选项
    needRemote?: true // 需要远程参数配置
    needMatch?: true // 支持模糊/精确匹配
}

/** SEARCH_RENDER_DEFS 搜索控件定义列表 {number_range:数值区间, date_range/datetime_range:时间区间, bool:全部/是/否} */
export const SEARCH_RENDER_DEFS = [
    { key: 'INPUT', value: 'input', label: '文本', group: '文本', needMatch: true },
    { key: 'NUMBER_RANGE', value: 'number_range', label: '数值区间', group: '数值' },
    { key: 'SELECT', value: 'select', label: '下拉选择', group: '选择', needEnum: true },
    { key: 'RADIO', value: 'radio', label: '单选', group: '选择', needEnum: true },
    { key: 'BOOL', value: 'bool', label: '是/否', group: '选择' },
    { key: 'REMOTE_SELECT', value: 'remote_select', label: '远程下拉', group: '选择', needRemote: true },
    {
        key: 'REMOTE_MULTI_SELECT',
        value: 'remote_multi_select',
        label: '远程多选',
        group: '选择',
        needRemote: true,
    },
    { key: 'DATE_RANGE', value: 'date_range', label: '日期区间', group: '日期时间' },
    { key: 'DATETIME_RANGE', value: 'datetime_range', label: '日期时间区间', group: '日期时间' },
] as const satisfies readonly SearchRenderDef[]

const SEARCH_GROUP_ORDER = ['文本', '数值', '选择', '日期时间'] as const

/** SEARCH_RENDER 搜索控件渲染类型 */
export const SEARCH_RENDER = Object.fromEntries(SEARCH_RENDER_DEFS.map((d) => [d.key, d.value])) as {
    [K in (typeof SEARCH_RENDER_DEFS)[number]['key']]: Extract<
        (typeof SEARCH_RENDER_DEFS)[number],
        { key: K }
    >['value']
}

/** SEARCH_RENDER_TYPE_LABEL 搜索控件中文名 */
export const SEARCH_RENDER_TYPE_LABEL = Object.fromEntries(
    SEARCH_RENDER_DEFS.map((d) => [d.value, d.label]),
) as Record<SearchRenderType, string>

/** SEARCH_RENDER_OPTIONS 搜索控件选项 */
export const SEARCH_RENDER_OPTIONS = SEARCH_RENDER_DEFS.map(({ value, label }) => ({ value, label }))

/** SEARCH_CONTROL_DEFS 搜索控件 UI 定义（同 SEARCH_RENDER_DEFS） */
export const SEARCH_CONTROL_DEFS = SEARCH_RENDER_DEFS

/** SEARCH_CONTROL_GROUPS 搜索控件选项（分组） */
export const SEARCH_CONTROL_GROUPS: NamedOptionGroup[] = SEARCH_GROUP_ORDER.map((label) => ({
    label,
    options: SEARCH_RENDER_DEFS.filter((d) => d.group === label).map(({ value, label: l }) => ({
        value,
        label: l,
    })),
})).filter((g) => g.options.length > 0)

/** SEARCH_CONTROL_OPTIONS 扁平搜索控件选项 */
export const SEARCH_CONTROL_OPTIONS: NamedOption[] = SEARCH_RENDER_DEFS.map(({ value, label }) => ({
    value,
    label,
}))

/** SEARCH_CONTROL_NEED_ENUM 需要枚举/选项的搜索控件 */
export const SEARCH_CONTROL_NEED_ENUM = SEARCH_RENDER_DEFS.filter((d) => 'needEnum' in d).map((d) => d.value)

/** SEARCH_CONTROL_NEED_REMOTE 需要远程参数配置的搜索控件 */
export const SEARCH_CONTROL_NEED_REMOTE = SEARCH_RENDER_DEFS.filter((d) => 'needRemote' in d).map(
    (d) => d.value,
)

/** SEARCH_CONTROL_NEED_MATCH 支持模糊/精确匹配方式的搜索控件 */
export const SEARCH_CONTROL_NEED_MATCH = SEARCH_RENDER_DEFS.filter((d) => 'needMatch' in d).map((d) => d.value)
