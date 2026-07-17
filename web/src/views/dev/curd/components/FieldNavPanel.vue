<script setup lang="ts">
import type { CrudFieldSchema } from '@/views/dev/curd/types'
import { isLayerEnabled } from '@/views/dev/curd/types'
import { useDevCurd } from '@/pinia/dev/curd'
import { useDevCurdField } from '@/pinia/dev/curdField'
import { Delete, Plus, Rank } from '@element-plus/icons-vue'
import { storeToRefs } from 'pinia'
import Sortable from 'sortablejs'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import {
    FIELD_TYPE_GROUPS,
    FIELD_TYPE_NEED_ENUM,
    FIELD_TYPE_NEED_REMOTE,
    FIELD_TYPE_OPTIONS,
    resolveFieldType,
} from '../constants/field'
import { applyFieldType } from '../helpers'
import InlineOptionsEditor from './InlineOptionsEditor.vue'
import RemoteConfigDialog from './RemoteConfigDialog.vue'

const store = useDevCurd()
const fieldStore = useDevCurdField()
const { config } = storeToRefs(store)
const { selectedIndex } = storeToRefs(fieldStore)

const enumNeedSet = new Set<string>(FIELD_TYPE_NEED_ENUM)
const remoteNeedSet = new Set<string>(FIELD_TYPE_NEED_REMOTE)

/** keywordFields 启用关键词搜索的字段（展示于模块 header） */
const keywordFields = computed(() =>
    config.value.fields
        .map((field, index) => ({ field, index }))
        .filter(({ field }) => field.search?.keyword === true && field.search?.enabled !== false),
)

const listRef = ref<HTMLElement | null>(null)
let sortable: Sortable | null = null

const typeValueSet = new Set(FIELD_TYPE_OPTIONS.map((o) => o.value))

/** fieldTypeDisplay 将字段映射为 UI 模板类型 */
const fieldTypeDisplay = (field: CrudFieldSchema): string | undefined => resolveFieldType(field)

/** onFieldTypeChange 行内切换字段类型并按 inference 写回各层 */
const onFieldTypeChange = (index: number, type: string | undefined) => {
    if (!type || !typeValueSet.has(type)) return
    fieldStore.selectField(index)
    const field = config.value.fields[index]
    if (!field) return
    applyFieldType(field, type, store.assembleConfig())
}

/** 字段行稳定 key（避免 :key="index" 导致落地回弹） */
let fieldKeySeq = 0
const fieldKeyMap = new WeakMap<object, string>()
/** fieldRowKey 取字段行稳定 key */
const fieldRowKey = (field: object) => {
    let key = fieldKeyMap.get(field)
    if (!key) {
        key = `f${++fieldKeySeq}`
        fieldKeyMap.set(field, key)
    }
    return key
}

/** initSortable 初始化拖拽 */
const initSortable = () => {
    sortable?.destroy()
    sortable = null
    if (!listRef.value) return
    sortable = Sortable.create(listRef.value, {
        animation: 150,
        handle: '.drag-handle',
        draggable: '.field-item',
        // table 行原生 DnD 不可靠；勿 fallbackOnBody（tr 挂到 body 会坏）
        forceFallback: true,
        fallbackTolerance: 3,
        onEnd: (evt) => {
            const { oldIndex, newIndex } = evt
            if (oldIndex == null || newIndex == null || oldIndex === newIndex) return
            store.reorderFields(oldIndex, newIndex)
        },
    })
}

onMounted(() => {
    void nextTick(initSortable)
})

watch(
    () => config.value.fields.length,
    () => void nextTick(initSortable),
)

onBeforeUnmount(() => {
    sortable?.destroy()
    sortable = null
})

const addVisible = ref(false)

/** openAddDialog 打开添加字段弹窗 */
const openAddDialog = () => {
    addVisible.value = true
}

/** pickAddType 点选类型后添加字段并应用类型 */
const pickAddType = (type: string) => {
    store.addField('blank')
    addVisible.value = false
    const index = config.value.fields.length - 1
    if (index >= 0) onFieldTypeChange(index, type)
}

const enumVisible = ref(false)
const remoteVisible = ref(false)

/** fieldNeedsEnum 当前字段类型是否需要本地枚举 */
const fieldNeedsEnum = (field: CrudFieldSchema): boolean => {
    const t = fieldTypeDisplay(field)
    return !!t && enumNeedSet.has(t)
}

/** fieldNeedsRemote 当前字段类型是否需要远程参数 */
const fieldNeedsRemote = (field: CrudFieldSchema): boolean => {
    const t = fieldTypeDisplay(field)
    return !!t && remoteNeedSet.has(t)
}

/** openEnumDialog 打开枚举编辑弹窗 */
const openEnumDialog = (index: number) => {
    fieldStore.selectField(index)
    enumVisible.value = true
}

/** openRemoteDialog 打开远程参数弹窗 */
const openRemoteDialog = (index: number) => {
    fieldStore.selectField(index)
    remoteVisible.value = true
}
</script>

<template>
    <el-card shadow="never" class="!border h-full flex flex-col min-h-0">
        <div class="flex items-center justify-between mb-2 gap-2">
            <div class="flex items-center gap-2 min-w-0 flex-1">
                <div class="text-sm font-medium shrink-0">字段列表</div>
                <div v-if="keywordFields.length" class="keyword-bar">
                    <span class="keyword-bar__label">关键词</span>
                    <el-tag
                        v-for="{ field, index } in keywordFields"
                        :key="fieldRowKey(field)"
                        size="small"
                        type="info"
                        class="cursor-pointer"
                        @click="fieldStore.selectField(index)"
                    >
                        {{ field.label || field.prop }}
                    </el-tag>
                </div>
            </div>
            <el-button size="small" type="primary" :icon="Plus" @click="openAddDialog">添加</el-button>
        </div>

        <el-dialog v-model="addVisible" title="添加字段" width="520px" append-to-body destroy-on-close>
            <div class="add-type-panel">
                <div v-for="g in FIELD_TYPE_GROUPS" :key="g.label" class="add-type-group">
                    <div class="add-type-group__label">{{ g.label }}</div>
                    <div class="add-type-group__options">
                        <button
                            v-for="o in g.options"
                            :key="o.value"
                            type="button"
                            class="add-type-option"
                            @click="pickAddType(o.value)"
                        >
                            {{ o.label }}
                        </button>
                    </div>
                </div>
            </div>
        </el-dialog>

        <el-dialog
            v-model="enumVisible"
            title="编辑枚举选项"
            width="560px"
            append-to-body
            destroy-on-close
        >
            <InlineOptionsEditor />
        </el-dialog>

        <RemoteConfigDialog v-model="remoteVisible" />

        <div class="flex-1 overflow-auto min-h-[280px] max-h-[calc(100vh-360px)]">
            <table class="field-table">
                <colgroup>
                    <col class="col-drag" />
                    <col class="col-label" />
                    <col class="col-prop" />
                    <col class="col-type" />
                    <col class="col-flag" />
                    <col class="col-flag" />
                    <col class="col-flag" />
                    <col class="col-action" />
                </colgroup>
                <thead>
                    <tr>
                        <th></th>
                        <th>字段</th>
                        <th>字段名称</th>
                        <th>类型</th>
                        <th>列表</th>
                        <th>搜索</th>
                        <th>表单</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody ref="listRef">
                    <tr
                        v-for="(field, index) in config.fields"
                        :key="fieldRowKey(field)"
                        class="field-item cursor-pointer"
                        :class="index === selectedIndex ? 'is-selected' : ''"
                        @mousedown="fieldStore.selectField(index)"
                    >
                        <td>
                            <span class="drag-handle">
                                <el-icon><Rank /></el-icon>
                            </span>
                        </td>
                        <td>
                            <el-input v-model="field.prop" size="small" placeholder="prop" />
                        </td>
                        <td>
                            <el-input
                                v-model="field.label"
                                size="small"
                                placeholder="中文标签"
                            />
                        </td>
                        <td @mousedown.stop>
                            <el-select
                                :model-value="fieldTypeDisplay(field)"
                                size="small"
                                placeholder="字段类型"
                                class="w-full"
                                popper-class="field-type-select-popper"
                                @update:model-value="(v: string) => onFieldTypeChange(index, v)"
                            >
                                <el-option-group
                                    v-for="g in FIELD_TYPE_GROUPS"
                                    :key="g.label"
                                    :label="g.label"
                                >
                                    <el-option
                                        v-for="o in g.options"
                                        :key="o.value"
                                        :label="o.label"
                                        :value="o.value"
                                    />
                                </el-option-group>
                            </el-select>
                        </td>
                        <td>
                            <el-checkbox
                                :model-value="isLayerEnabled(field.list)"
                                @change="() => fieldStore.toggleListAt(index)"
                            />
                        </td>
                        <td>
                            <el-checkbox
                                :model-value="field.search.advanced === true"
                                @change="() => fieldStore.toggleSearchAt(index)"
                            />
                        </td>
                        <td>
                            <el-checkbox
                                :model-value="isLayerEnabled(field.form)"
                                @change="() => fieldStore.toggleFormAt(index)"
                            />
                        </td>
                        <td class="col-action-cell" @click.stop @mousedown.stop>
                            <div class="action-cell">
                                <el-button
                                    v-if="fieldNeedsEnum(field)"
                                    size="small"
                                    type="primary"
                                    link
                                    @click="openEnumDialog(index)"
                                >
                                    枚举
                                </el-button>
                                <el-button
                                    v-if="fieldNeedsRemote(field)"
                                    size="small"
                                    type="primary"
                                    link
                                    @click="openRemoteDialog(index)"
                                >
                                    远程
                                </el-button>
                                <el-button
                                    size="small"
                                    type="danger"
                                    link
                                    :icon="Delete"
                                    :disabled="config.fields.length <= 1"
                                    @click="store.removeFieldAt(index)"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </el-card>
</template>

<style scoped>
.keyword-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
    min-width: 0;
}
.keyword-bar__label {
    font-size: 12px;
    color: var(--el-text-color-secondary);
    flex-shrink: 0;
}
.add-type-panel {
    display: flex;
    flex-direction: column;
    gap: 14px;
    max-height: min(420px, 60vh);
    overflow-y: auto;
}
.add-type-group__label {
    margin-bottom: 8px;
    font-size: 12px;
    color: var(--el-text-color-secondary);
}
.add-type-group__options {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.add-type-option {
    padding: 6px 12px;
    border: 1px solid var(--el-border-color);
    border-radius: 4px;
    background: var(--el-fill-color-blank);
    color: var(--el-text-color-primary);
    font-size: 13px;
    cursor: pointer;
    line-height: 1.4;
}
.add-type-option:hover {
    border-color: var(--el-color-primary);
    color: var(--el-color-primary);
    background: var(--el-color-primary-light-9);
}
.field-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
}
.field-table .col-drag {
    width: 36px;
}
.field-table .col-label {
    width: 140px;
}
.field-table .col-prop {
    width: 120px;
}
.field-table .col-type {
    width: 110px;
}
.field-table .col-flag {
    width: 48px;
}
.field-table .col-flag {
  width: 48px;
}
.field-table .col-action {
    width: 96px;
}
.field-table .action-cell {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 0 2px;
}
.field-table .action-cell .el-button + .el-button {
    margin-left: 0;
}
.field-table th,
.field-table td {
    border: 1px solid var(--el-border-color);
    padding: 6px 8px;
    vertical-align: middle;
}
.field-table th {
    background: var(--el-fill-color-light);
    font-weight: 500;
    text-align: left;
    white-space: nowrap;
}
.field-table th:nth-child(n + 5),
.field-table td:nth-child(n + 5) {
    text-align: center;
    padding: 6px 4px;
}
.field-table .field-item.is-selected {
    background: var(--el-color-primary-light-9);
}
.field-table .field-item:hover:not(.is-selected) {
    background: var(--el-fill-color-lighter);
}
.field-table .drag-handle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 24px;
    cursor: grab;
    color: var(--el-text-color-secondary);
    touch-action: none;
    user-select: none;
}
.field-table .drag-handle:active {
    cursor: grabbing;
}
:deep(.sortable-fallback) {
    opacity: 0.9;
    background: var(--el-bg-color);
    box-shadow: var(--el-box-shadow-light);
}
:deep(.sortable-ghost) {
    opacity: 0.4;
}
</style>

<!-- teleported 下拉需非 scoped -->
<style>
.field-type-select-popper.el-select__popper,
.field-type-select-popper {
    max-height: min(280px, 50vh);
}
.field-type-select-popper .el-select-dropdown__wrap {
    max-height: min(260px, 46vh) !important;
}
</style>
