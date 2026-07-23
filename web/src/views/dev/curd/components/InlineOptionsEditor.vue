<script setup lang="ts">
import type { CrudOptionItem } from '@/views/dev/curd/types'
import { isLayerEnabled } from '@/views/dev/curd/types'
import { useDevCurd } from '@/pinia/dev/curd'
import { useDevCurdField } from '@/pinia/dev/curdField'
import { Delete, Plus } from '@element-plus/icons-vue'
import { storeToRefs } from 'pinia'
import { computed, onMounted } from 'vue'
import { DEFAULT_OPTIONS, DEFAULT_SELECT_OPTIONS, DEFAULT_STATUS_OPTIONS, resolveFieldType } from '../constants'
import { ensureFieldEnumExt, enumKeyOf } from '../fieldExt'

type Row = { value: string; label: string; type: string }

const { config } = storeToRefs(useDevCurd())
const { selectedField: field } = storeToRefs(useDevCurdField())

/** boundKey 当前 options 键 */
const boundKey = computed((): string => enumKeyOf(field.value))

/** bindEnumKey 将键绑到 ext.enum */
const bindEnumKey = (key: string) => {
    const f = field.value
    if (!f || !key) return
    ensureFieldEnumExt(f).key = key
}

/** ensureDefaults 无数据时写入默认可跑项 */
const ensureDefaults = () => {
    const f = field.value
    const key = boundKey.value
    if (!f || !key) return
    if (!config.value.options || Array.isArray(config.value.options)) {
        config.value.options = {}
    }
    if (!config.value.options[key]?.length) {
        const type = resolveFieldType(f)
        if (isLayerEnabled(f.form) && (f.form.render === 'multi_select' || f.form.render === 'checkbox')) {
            config.value.options[key] = structuredClone(DEFAULT_OPTIONS)
        } else {
            config.value.options[key] = structuredClone(
                type === 'select' ? DEFAULT_SELECT_OPTIONS : DEFAULT_STATUS_OPTIONS,
            )
        }
    }
    bindEnumKey(key)
}

onMounted(() => ensureDefaults())

/** rows 统一行数据 */
const rows = computed((): Row[] => {
    const key = boundKey.value
    if (!key) return []
    return (config.value.options?.[key] ?? []).map((o) => ({
        value: String(o.value),
        label: o.label,
        type: o.type ?? '',
    }))
})

/** parseOptionValue 写回时尽量保留 number */
const parseOptionValue = (raw: string): string | number => {
    const t = raw.trim()
    if (t !== '' && /^-?\d+$/.test(t)) return Number(t)
    return t || raw
}

/** writeRows 写回 options */
const writeRows = (next: Row[]) => {
    const key = boundKey.value
    if (!key) return
    if (!config.value.options || Array.isArray(config.value.options)) {
        config.value.options = {}
    }
    config.value.options[key] = next
        .filter((r) => r.value.trim())
        .map(
            (r): CrudOptionItem => ({
                value: parseOptionValue(r.value),
                label: r.label || r.value,
                ...(r.type ? { type: r.type } : {}),
            }),
        )
    bindEnumKey(key)
}

/** addRow 新增一行 */
const addRow = () => {
    ensureDefaults()
    const list = [...rows.value]
    const n = String(list.length)
    list.push({ value: n, label: `选项${n}`, type: 'info' })
    writeRows(list)
}

/** removeRow 删除一行 */
const removeRow = (idx: number) => {
    const list = [...rows.value]
    list.splice(idx, 1)
    writeRows(list)
}

/** updateRow 更新一行 */
const updateRow = (idx: number, patch: Partial<Row>) => {
    const list = [...rows.value]
    const cur = list[idx]
    if (!cur) return
    list[idx] = { ...cur, ...patch }
    writeRows(list)
}
</script>

<template>
    <div v-if="field" class="space-y-2">
        <div v-for="(row, idx) in rows" :key="idx" class="flex flex-wrap items-center gap-2">
            <el-input
                :model-value="row.value"
                class="!w-20"
                placeholder="值"
                @update:model-value="(v: string) => updateRow(idx, { value: v })"
            />
            <el-input
                :model-value="row.label"
                class="!w-28"
                placeholder="文案"
                @update:model-value="(v: string) => updateRow(idx, { label: v })"
            />
            <el-select
                :model-value="row.type"
                clearable
                placeholder="Tag"
                class="!w-28"
                @update:model-value="(v: string) => updateRow(idx, { type: v ?? '' })"
            >
                <el-option label="success" value="success" />
                <el-option label="info" value="info" />
                <el-option label="warning" value="warning" />
                <el-option label="danger" value="danger" />
                <el-option label="primary" value="primary" />
            </el-select>
            <el-button type="danger" link :icon="Delete" @click="removeRow(idx)" />
        </div>
        <el-button size="small" :icon="Plus" @click="addRow">添加选项</el-button>
    </div>
</template>
