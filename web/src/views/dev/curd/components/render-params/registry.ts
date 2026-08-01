import type { Component } from 'vue'
import FormColor from './form/Color.vue'
import FormDate from './form/Date.vue'
import FormDatetime from './form/Datetime.vue'
import FormEmpty from './form/Empty.vue'
import FormImageUpload from './form/ImageUpload.vue'
import FormInput from './form/Input.vue'
import FormMultiSelect from './form/MultiSelect.vue'
import FormNumber from './form/Number.vue'
import FormRadio from './form/Radio.vue'
import FormRemoteMultiSelect from './form/RemoteMultiSelect.vue'
import FormRemoteSelect from './form/RemoteSelect.vue'
import FormSelect from './form/Select.vue'
import FormSwitch from './form/Switch.vue'
import FormTextarea from './form/Textarea.vue'
import ListBool from './list/Bool.vue'
import ListColor from './list/Color.vue'
import ListDatetime from './list/Datetime.vue'
import ListEmpty from './list/Empty.vue'
import ListEnumTag from './list/EnumTag.vue'
import ListImage from './list/Image.vue'
import ListNumber from './list/Number.vue'
import ListTags from './list/Tags.vue'
import ListText from './list/Text.vue'
import SearchBool from './search/Bool.vue'
import SearchDateRange from './search/DateRange.vue'
import SearchDatetimeRange from './search/DatetimeRange.vue'
import SearchEmpty from './search/Empty.vue'
import SearchInput from './search/Input.vue'
import SearchNumberRange from './search/NumberRange.vue'
import SearchRemoteMultiSelect from './search/RemoteMultiSelect.vue'
import SearchRemoteSelect from './search/RemoteSelect.vue'
import SearchSelect from './search/Select.vue'

/** formParamCanonical 表单 render 别名 → 规范键（仅入参组件映射） */
export function formParamCanonical(render: string): string {
    const map: Record<string, string> = {
        password: 'input',
        checkbox: 'multi_select',
        file: 'image_upload',
        image: 'image_upload',
        time: 'datetime',
        readonly: '_empty',
        hidden: '_empty',
        custom: '_empty',
    }
    return map[render] ?? render
}

/** listParamCanonical 列表 render → 规范键 */
export function listParamCanonical(render: string): string {
    return render
}

/** searchParamCanonical 搜索 render 别名 → 规范键 */
export function searchParamCanonical(render: string): string {
    if (render === 'radio') return 'select'
    return render
}

/** FORM_PARAM_COMPONENTS 表单规范键 → 入参组件 */
export const FORM_PARAM_COMPONENTS: Record<string, Component> = {
    input: FormInput,
    textarea: FormTextarea,
    number: FormNumber,
    switch: FormSwitch,
    radio: FormRadio,
    select: FormSelect,
    multi_select: FormMultiSelect,
    remote_select: FormRemoteSelect,
    remote_multi_select: FormRemoteMultiSelect,
    date: FormDate,
    datetime: FormDatetime,
    color: FormColor,
    image_upload: FormImageUpload,
    _empty: FormEmpty,
}

/** LIST_PARAM_COMPONENTS 列表规范键 → 入参组件 */
export const LIST_PARAM_COMPONENTS: Record<string, Component> = {
    text: ListText,
    number: ListNumber,
    bool: ListBool,
    enum_tag: ListEnumTag,
    tags: ListTags,
    datetime: ListDatetime,
    image: ListImage,
    color: ListColor,
}

/** SEARCH_PARAM_COMPONENTS 搜索规范键 → 入参组件 */
export const SEARCH_PARAM_COMPONENTS: Record<string, Component> = {
    input: SearchInput,
    number_range: SearchNumberRange,
    select: SearchSelect,
    bool: SearchBool,
    remote_select: SearchRemoteSelect,
    remote_multi_select: SearchRemoteMultiSelect,
    date_range: SearchDateRange,
    datetime_range: SearchDatetimeRange,
}

/** resolveFormParamComponent 解析表单入参组件 */
export function resolveFormParamComponent(render: string | undefined): Component {
    if (!render) return FormEmpty
    const key = formParamCanonical(render)
    return FORM_PARAM_COMPONENTS[key] ?? FormEmpty
}

/** resolveListParamComponent 解析列表入参组件 */
export function resolveListParamComponent(render: string | undefined): Component {
    if (!render) return ListEmpty
    const key = listParamCanonical(render)
    return LIST_PARAM_COMPONENTS[key] ?? ListEmpty
}

/** resolveSearchParamComponent 解析搜索入参组件 */
export function resolveSearchParamComponent(render: string | undefined): Component {
    if (!render) return SearchEmpty
    const key = searchParamCanonical(render)
    return SEARCH_PARAM_COMPONENTS[key] ?? SearchEmpty
}
