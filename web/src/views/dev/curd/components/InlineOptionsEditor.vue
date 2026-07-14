<script setup lang="ts">
import type { CrudOptionItem, CrudStatusMapEntry } from '@/crud/types'
import { useDevCurd } from '@/pinia/dev/curd'
import { Delete, Plus } from '@element-plus/icons-vue'
import { storeToRefs } from 'pinia'
import { computed, watch } from 'vue'
import {
    DEFAULT_OPTIONS,
    DEFAULT_SELECT_MAP,
    DEFAULT_STATUS_MAP,
    detectControlIntent,
    mapKeyFromProp,
    optionsKeyFromProp,
} from '../fieldIntent'

const store = useDevCurd()
const { config, selectedField: field } = storeToRefs(store)

/** mode 编辑模式 {map:maps, options:options} */
const mode = computed<'map' | 'options'>(() => {
    if (!field.value) return 'map'
    if (field.value.form && field.value.form !== false && field.value.form.render === 'multi_select') {
        return 'options'
    }
    return 'map'
})

/** resolveKey 当前应绑定的键名 */
const resolveKey = (): string => {
    if (!field.value) return ''
    if (mode.value === 'map') {
        return (
            (field.value.form && field.value.form !== false && field.value.form.statusMapKey) ||
            field.value.list?.statusMapKey ||
            mapKeyFromProp(field.value.prop)
        )
    }
    return (
        (field.value.form && field.value.form !== false && field.value.form.optionsKey) ||
        field.value.list?.optionsKey ||
        optionsKeyFromProp(field.value.prop)
    )
}

/** boundKey 映射/选项键（只读，按 resolveKey 自动推导） */
const boundKey = computed((): string => resolveKey())

/** bindKeyToField 将键绑到字段 form/list/search */
const bindKeyToField = (key: string) => {
    if (!field.value) return
    if (mode.value === 'map') {
        if (field.value.form && field.value.form !== false) field.value.form.statusMapKey = key
        if (field.value.list) field.value.list.statusMapKey = key
        if (field.value.search) field.value.search.statusMapKey = key
    } else {
        if (field.value.form && field.value.form !== false) field.value.form.optionsKey = key
        if (field.value.list) field.value.list.optionsKey = key
        if (field.value.search) field.value.search.optionsKey = key
    }
}

/** ensureDefaults 无数据时写入默认可跑项 */
const ensureDefaults = () => {
    if (!field.value) return
    const key = resolveKey()
    if (!key) return
    if (mode.value === 'map') {
        if (!config.value.maps) config.value.maps = {}
        if (!config.value.maps[key] || Object.keys(config.value.maps[key]!).length === 0) {
            const intent = detectControlIntent(field.value)
            config.value.maps[key] = structuredClone(
                intent === 'select' ? DEFAULT_SELECT_MAP : DEFAULT_STATUS_MAP,
            )
        }
    } else {
        if (!config.value.options) config.value.options = {}
        if (!config.value.options[key]?.length) {
            config.value.options[key] = structuredClone(DEFAULT_OPTIONS)
        }
    }
    bindKeyToField(key)
}

watch(
    () => [field.value?.prop, field.value && field.value.form !== false ? field.value.form?.statusMapKey : ''],
    () => ensureDefaults(),
    { immediate: true },
)

type MapRow = { value: string; label: string; type: string }

/** mapRows 当前 maps 行 */
const mapRows = computed((): MapRow[] => {
    const key = boundKey.value
    const m = config.value.maps?.[key] ?? {}
    return Object.entries(m).map(([value, entry]) => ({
        value,
        label: entry.label,
        type: entry.type ?? '',
    }))
})

/** writeMapRows 写回 maps */
const writeMapRows = (rows: MapRow[]) => {
    if (!config.value.maps) config.value.maps = {}
    const key = boundKey.value
    const next: Record<string, CrudStatusMapEntry> = {}
    for (const r of rows) {
        if (!r.value.trim()) continue
        next[r.value.trim()] = {
            label: r.label || r.value,
            ...(r.type ? { type: r.type } : {}),
        }
    }
    config.value.maps[key] = next
    bindKeyToField(key)
}

/** optionRows 当前 options 行 */
const optionRows = computed((): CrudOptionItem[] => {
    const key = boundKey.value
    return config.value.options?.[key] ?? []
})

/** writeOptionRows 写回 options */
const writeOptionRows = (rows: CrudOptionItem[]) => {
    if (!config.value.options) config.value.options = {}
    config.value.options[boundKey.value] = rows
    bindKeyToField(boundKey.value)
}

/** addMapRow 新增 maps 行 */
const addMapRow = () => {
    ensureDefaults()
    const rows = [...mapRows.value]
    const nextVal = String(rows.length)
    rows.push({ value: nextVal, label: `选项${nextVal}`, type: 'info' })
    writeMapRows(rows)
}

/** removeMapRow 删除 maps 行 */
const removeMapRow = (idx: number) => {
    const rows = [...mapRows.value]
    rows.splice(idx, 1)
    writeMapRows(rows)
}

/** updateMapRow 更新单行并写回 */
const updateMapRow = (idx: number, patch: Partial<MapRow>) => {
    const rows = [...mapRows.value]
    const cur = rows[idx]
    if (!cur) return
    rows[idx] = { ...cur, ...patch }
    writeMapRows(rows)
}

/** addOptionRow 新增 options 行 */
const addOptionRow = () => {
    ensureDefaults()
    const rows = [...optionRows.value]
    rows.push({ value: `opt_${rows.length + 1}`, label: `选项${rows.length + 1}` })
    writeOptionRows(rows)
}

/** removeOptionRow 删除 options 行 */
const removeOptionRow = (idx: number) => {
    const rows = [...optionRows.value]
    rows.splice(idx, 1)
    writeOptionRows(rows)
}

/** updateOptionRow 更新 options 行 */
const updateOptionRow = (idx: number, patch: Partial<CrudOptionItem>) => {
    const rows = [...optionRows.value]
    const cur = rows[idx]
    if (!cur) return
    rows[idx] = { ...cur, ...patch }
    writeOptionRows(rows)
}
</script>

<template>
    <div v-if="field" class="space-y-3">
        <el-form label-width="80px" size="small">
            <el-form-item :label="mode === 'map' ? '映射键' : '选项键'">
                <el-input :model-value="boundKey" class="!w-56 max-w-full" disabled />
            </el-form-item>
        </el-form>

        <div v-if="mode === 'map'" class="space-y-2">
            <div class="text-xs text-gray-500">枚举选项（写入 config.maps，自动绑定 statusMapKey）</div>
            <div
                v-for="(row, idx) in mapRows"
                :key="idx"
                class="flex flex-wrap items-center gap-2"
            >
                <el-input
                    :model-value="row.value"
                    class="!w-20"
                    placeholder="值"
                    @update:model-value="(v: string) => updateMapRow(idx, { value: v })"
                />
                <el-input
                    :model-value="row.label"
                    class="!w-28"
                    placeholder="文案"
                    @update:model-value="(v: string) => updateMapRow(idx, { label: v })"
                />
                <el-select
                    :model-value="row.type"
                    clearable
                    placeholder="Tag"
                    class="!w-28"
                    @update:model-value="(v: string) => updateMapRow(idx, { type: v ?? '' })"
                >
                    <el-option label="success" value="success" />
                    <el-option label="info" value="info" />
                    <el-option label="warning" value="warning" />
                    <el-option label="danger" value="danger" />
                    <el-option label="primary" value="primary" />
                </el-select>
                <el-button type="danger" link :icon="Delete" @click="removeMapRow(idx)" />
            </div>
            <el-button size="small" :icon="Plus" @click="addMapRow">添加选项</el-button>
        </div>

        <div v-else class="space-y-2">
            <div class="text-xs text-gray-500">静态选项（写入 config.options，自动绑定 optionsKey）</div>
            <div
                v-for="(row, idx) in optionRows"
                :key="idx"
                class="flex flex-wrap items-center gap-2"
            >
                <el-input
                    :model-value="String(row.value)"
                    class="!w-28"
                    placeholder="value"
                    @update:model-value="(v: string) => updateOptionRow(idx, { value: v })"
                />
                <el-input
                    :model-value="row.label"
                    class="!w-36"
                    placeholder="label"
                    @update:model-value="(v: string) => updateOptionRow(idx, { label: v })"
                />
                <el-button type="danger" link :icon="Delete" @click="removeOptionRow(idx)" />
            </div>
            <el-button size="small" :icon="Plus" @click="addOptionRow">添加选项</el-button>
        </div>
    </div>
</template>
