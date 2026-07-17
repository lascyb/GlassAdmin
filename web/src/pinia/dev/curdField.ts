import { useDevCurd } from '@/pinia/dev/curd'
import { resolveFieldType } from '@/views/dev/curd/constants/field'
import { formDefaultsOf } from '@/views/dev/curd/constants/form-render'
import { applyRenderExt } from '@/views/dev/curd/fieldExt'
import {
    applyFieldType,
    defaultSearchRenderHint,
    ensureColumn,
    fillPivotDefaults,
} from '@/views/dev/curd/helpers'
import type {
    CrudFieldFormConfig,
    CrudFieldSchema,
    CrudFieldSearchConfig,
    FormRenderType,
    ListRenderType,
    RelationType,
    SearchRenderType,
} from '@/views/dev/curd/types'
import { isLayerEnabled } from '@/views/dev/curd/types'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

/** formConfigOf 取 form 配置对象（含 disabled，用于保留参数） */
const formConfigOf = (field: CrudFieldSchema | null): CrudFieldFormConfig | null => field?.form ?? null

/** hasSearchParticipation 是否启用关键词或高级搜索 */
const hasSearchParticipation = (search: CrudFieldSearchConfig): boolean =>
    search.keyword === true || search.advanced === true

/**
 * useDevCurdField 字段列表当前选中字段的编辑态。
 * fields 数据源在 useDevCurd.config；不 persist。
 */
export const useDevCurdField = defineStore('dev_curd_field', () => {
    const selectedIndex = ref(0)

    /** fields 字段列表（live） */
    const fields = computed((): CrudFieldSchema[] => useDevCurd().config.fields)

    const selectedField = computed({
        get: (): CrudFieldSchema | null => fields.value[selectedIndex.value] ?? null,
        set: (f: CrudFieldSchema | null) => {
            if (!f) return
            const idx = selectedIndex.value
            const list = fields.value
            if (idx < 0 || idx >= list.length) return
            list[idx] = f
        },
    })

    /** fieldAt 取指定索引字段 */
    const fieldAt = (index: number): CrudFieldSchema | null => fields.value[index] ?? null

    /** selectField 选中字段 */
    const selectField = (index: number) => {
        if (index < 0 || index >= fields.value.length) return
        selectedIndex.value = index
    }

    /** resetSelection 重置为第一项 */
    const resetSelection = () => {
        selectedIndex.value = 0
    }

    /** selectLast 选中最后一项 */
    const selectLast = () => {
        const len = fields.value.length
        selectedIndex.value = len > 0 ? len - 1 : 0
    }

    /** syncAfterRemove 删除字段后校正选中下标 */
    const syncAfterRemove = (removedIndex: number) => {
        const len = fields.value.length
        if (selectedIndex.value === removedIndex) {
            selectedIndex.value = Math.min(removedIndex, Math.max(0, len - 1))
        } else if (selectedIndex.value > removedIndex) {
            selectedIndex.value -= 1
        }
    }

    /** syncAfterReorder 拖拽重排后校正选中下标 */
    const syncAfterReorder = (oldIndex: number, newIndex: number) => {
        if (selectedIndex.value === oldIndex) {
            selectedIndex.value = newIndex
        } else if (oldIndex < selectedIndex.value && newIndex >= selectedIndex.value) {
            selectedIndex.value -= 1
        } else if (oldIndex > selectedIndex.value && newIndex <= selectedIndex.value) {
            selectedIndex.value += 1
        }
    }

    /** formCfg 当前选中字段表单配置（含 disabled 时保留对象） */
    const formCfg = computed((): CrudFieldFormConfig | null =>
        formConfigOf(selectedField.value),
    )

    /** searchRenderHint 搜索控件默认推断文案 */
    const searchRenderHint = computed(() =>
        selectedField.value ? defaultSearchRenderHint(selectedField.value) : '无',
    )

    /** setFieldTypeAt 指定字段切换 UI 类型并按 inference 写回各层 */
    const setFieldTypeAt = (index: number, type: string) => {
        const field = fieldAt(index)
        if (!field) return
        applyFieldType(field, type, useDevCurd().assembleConfig())
    }

    /** onSelectedPropBlur 选中字段 prop 失焦后补中间表默认 */
    const onSelectedPropBlur = () => {
        if (selectedField.value && resolveFieldType(selectedField.value) === 'remote_multi_select') {
            fillPivotDefaults(selectedField.value, useDevCurd().assembleConfig())
        }
    }

    /** fillPivotForSelected 中间表自动填充 */
    const fillPivotForSelected = (relatedTable?: string, force = false) => {
        if (selectedField.value) {
            fillPivotDefaults(selectedField.value, useDevCurd().assembleConfig(), relatedTable, force)
        }
    }

    /** setListEnabledAt 设置列表层 enabled */
    const setListEnabledAt = (index: number, enabled: boolean) => {
        const field = fieldAt(index)
        if (!field) return
        if (!field.list || typeof field.list !== 'object') {
            field.list = { render: 'text', minWidth: 120 }
        }
        field.list.enabled = enabled
        if (enabled && !field.list.render) {
            field.list.render = 'text'
            field.list.minWidth = 120
        }
    }

    /** setSearchEnabledAt 设置搜索层 enabled */
    const setSearchEnabledAt = (index: number, enabled: boolean) => {
        const field = fieldAt(index)
        if (!field) return
        if (!field.search || typeof field.search !== 'object') {
            field.search = { keyword: false, advanced: false }
        }
        field.search.enabled = enabled
        if (enabled && !hasSearchParticipation(field.search)) {
            field.search.keyword = true
        }
    }

    /** setFormEnabledAt 设置表单层 enabled */
    const setFormEnabledAt = (index: number, enabled: boolean) => {
        const field = fieldAt(index)
        if (!field) return
        if (!field.form || typeof field.form !== 'object') {
            field.form = { render: 'input' }
        }
        field.form.enabled = enabled
        if (enabled && !field.form.render) {
            field.form.render = 'input'
        }
    }

    /** toggleListAt 指定字段切换列表 enabled */
    const toggleListAt = (index: number) => {
        const field = fieldAt(index)
        if (!field) return
        setListEnabledAt(index, !isLayerEnabled(field.list))
    }

    /** toggleSearchAt 指定字段切换搜索 enabled */
    const toggleSearchAt = (index: number) => {
        const field = fieldAt(index)
        if (!field) return
        setSearchEnabledAt(index, !isLayerEnabled(field.search))
    }

    /** toggleFormAt 指定字段切换表单 enabled */
    const toggleFormAt = (index: number) => {
        const field = fieldAt(index)
        if (!field) return
        setFormEnabledAt(index, !isLayerEnabled(field.form))
    }

    /** hasList 选中字段是否显示列表列 */
    const hasList = computed({
        get: () => selectedField.value != null && isLayerEnabled(selectedField.value.list),
        set: (v: boolean) => {
            if (!selectedField.value) return
            selectedField.value.list.enabled = v
            if (v && !selectedField.value.list.render) {
                selectedField.value.list.render = 'text'
                selectedField.value.list.minWidth = 120
            }
        },
    })

    /** hasSearch 选中字段是否参与搜索 */
    const hasSearch = computed({
        get: () => selectedField.value != null && isLayerEnabled(selectedField.value.search),
        set: (v: boolean) => {
            if (!selectedField.value) return
            selectedField.value.search.enabled = v
            if (v && !hasSearchParticipation(selectedField.value.search)) {
                selectedField.value.search.keyword = true
            }
        },
    })

    /** hasForm 选中字段是否进表单 */
    const hasForm = computed({
        get: () => selectedField.value != null && isLayerEnabled(selectedField.value.form),
        set: (v: boolean) => {
            if (!selectedField.value) return
            selectedField.value.form.enabled = v
            if (v && !selectedField.value.form.render) {
                selectedField.value.form.render = 'input'
            }
        },
    })

    /** hasColumn 选中字段是否有物理列 */
    const hasColumn = computed({
        get: () => selectedField.value != null && isLayerEnabled(selectedField.value.column),
        set: (v: boolean) => {
            if (!selectedField.value) return
            if (v) ensureColumn(selectedField.value)
            else selectedField.value.column.enabled = false
        },
    })

    /** hasRelation 选中字段是否配置关联 */
    const hasRelation = computed({
        get: () => selectedField.value != null && isLayerEnabled(selectedField.value.relation),
        set: (v: boolean) => {
            if (!selectedField.value) return
            if (v) {
                selectedField.value.relation.enabled = true
                if (!selectedField.value.relation.type) {
                    selectedField.value.relation.type = 'belongs_to'
                }
            } else {
                selectedField.value.relation.enabled = false
            }
        },
    })

    /** ensureSelectedForm 保证选中字段 form 对象（不要求 enabled） */
    const ensureSelectedForm = (): CrudFieldFormConfig | null => {
        const field = selectedField.value
        if (!field) return null
        return field.form
    }

    /** ensureSelectedSearch 保证选中字段 search 对象（不要求 enabled） */
    const ensureSelectedSearch = (): CrudFieldSearchConfig | null => selectedField.value?.search ?? null

    /** disableSearch 关闭搜索 enabled（保留 search 参数） */
    const disableSearch = () => {
        if (!selectedField.value) return
        selectedField.value.search.enabled = false
        selectedField.value.search.keyword = false
        selectedField.value.search.advanced = false
    }

    /** syncSearchFlags 同步关键词/高级搜索开关 */
    const syncSearchFlags = (keyword: boolean, advanced: boolean) => {
        if (!selectedField.value) return
        selectedField.value.search.keyword = keyword
        selectedField.value.search.advanced = advanced
        selectedField.value.search.enabled = keyword || advanced
    }

    /** setFormRender 切换表单控件（仅写 form 层，不联动 list/search/column） */
    const setFormRender = (render: FormRenderType) => {
        const field = selectedField.value
        const f = ensureSelectedForm()
        if (!f || !field) return
        f.enabled = true
        f.render = render
        const defaults = formDefaultsOf(render)
        if (defaults) Object.assign(f, defaults)
        applyRenderExt(field, render, useDevCurd().assembleConfig())
    }

    /** setListRender 仅更新列表列渲染（不覆盖 form） */
    const setListRender = (render: ListRenderType) => {
        if (!selectedField.value) return
        selectedField.value.list.enabled = true
        selectedField.value.list.render = render
        if (!selectedField.value.list.minWidth) {
            selectedField.value.list.minWidth = 120
        }
    }

    /** setSearchRender 切换高级搜索控件；传空仅清除 render/match，不改 advanced */
    const setSearchRender = (render: SearchRenderType | '') => {
        const s = ensureSelectedSearch()
        if (!s) return
        if (!render) {
            delete s.render
            delete s.match
            return
        }
        s.enabled = true
        s.advanced = true
        s.render = render
    }

    /** setColumnDefault 解析并写回列默认值 */
    const setColumnDefault = (v: string) => {
        if (!selectedField.value || !isLayerEnabled(selectedField.value.column)) return
        if (v === '') delete selectedField.value.column.default
        else if (v === 'null') selectedField.value.column.default = null
        else if (/^-?\d+(\.\d+)?$/.test(v)) selectedField.value.column.default = Number(v)
        else selectedField.value.column.default = v
    }

    /** setRelationType 切换关联类型 */
    const setRelationType = (type: RelationType) => {
        if (!selectedField.value || !isLayerEnabled(selectedField.value.relation)) return
        selectedField.value.relation.type = type
    }

    return {
        selectedIndex,
        selectedField,
        selectField,
        resetSelection,
        selectLast,
        syncAfterRemove,
        syncAfterReorder,
        formCfg,
        searchRenderHint,
        setFieldTypeAt,
        onSelectedPropBlur,
        fillPivotForSelected,
        toggleListAt,
        toggleSearchAt,
        toggleFormAt,
        hasList,
        hasSearch,
        hasForm,
        hasColumn,
        hasRelation,
        disableSearch,
        setFormRender,
        setListRender,
        syncSearchFlags,
        setSearchRender,
        setColumnDefault,
        setRelationType,
    }
})
