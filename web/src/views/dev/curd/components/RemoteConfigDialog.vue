<script setup lang="ts">
import {
    getRemoteTableColumns,
    listPivotTables,
    listRemoteControllers,
    type PivotTableItem,
    type RemoteControllerItem,
    type RemoteTableColumn,
} from '@/apis/dev/curd'
import { useDevCurd } from '@/pinia/dev/curd'
import { useDevCurdField } from '@/pinia/dev/curdField'
import { applyParsedColumnType, defaultPivot, ensureColumn, parseMysqlType } from '@/views/dev/curd/helpers'
import type { CrudFieldSchema } from '@/views/dev/curd/types'
import { isLayerEnabled } from '@/views/dev/curd/types'
import { ElMessage } from 'element-plus'
import { storeToRefs } from 'pinia'
import { computed, ref, watch } from 'vue'
import { applyRenderExt, ensureFieldRemoteExt, relatedNameFromProp } from '../fieldExt'

const visible = defineModel<boolean>({ default: false })

const { selectedField: field } = storeToRefs(useDevCurdField())

/** mainTable 当前 CRUD 主表名（全局配置「数据表」，未填则为空） */
const mainTable = computed(() => useDevCurd().config.table?.trim() || '')

const loading = ref(false)
const controllers = ref<RemoteControllerItem[]>([])
const tableName = ref('')
const columnsLoading = ref(false)
const columns = ref<RemoteTableColumn[]>([])
const tablePk = ref<string | null>(null)
const pivotLoading = ref(false)
const pivotTables = ref<PivotTableItem[]>([])
const pivotExpectedName = ref('')
/** snapshot 打开弹窗时的字段快照（取消时还原） */
const snapshot = ref('')

/** remoteCfg 当前字段 remote 配置（直连 schema） */
const remoteCfg = computed(() => {
    const f = field.value
    return f ? ensureFieldRemoteExt(f) : null
})

const selectedController = computed(() => {
    const api = remoteCfg.value?.api?.trim()
    if (!api) return undefined
    const keyFromOpt = api.endsWith('/list') ? api.slice(0, -5) : ''
    return controllers.value.find((c) => c.key === keyFromOpt || c.listUrl === api)
})

/** selectedKey 当前选中的控制器 key */
const selectedKey = computed(() => selectedController.value?.key)

const isMulti = computed(() => {
    const f = field.value
    if (!f) return false
    if (isLayerEnabled(f.form) && f.form.render === 'remote_multi_select') return true
    return isLayerEnabled(f.search) && f.search.render === 'remote_multi_select'
})

const fieldOptions = computed(() =>
    columns.value.map((c) => ({
        value: c.name,
        label: c.comment
            ? `${c.name}（${c.comment}）${c.type ? ` · ${c.type}` : ''}`
            : c.type
              ? `${c.name} · ${c.type}`
              : c.name,
    })),
)

const pivotOptions = computed(() =>
    pivotTables.value.map((item) => ({
        value: item.table,
        label: item.comment ? `${item.table}（${item.comment}）` : item.table,
    })),
)

/** pivotHint 中间表匹配说明 */
const pivotHint = computed(() => {
    if (!mainTable.value) return '请先在全局配置填写数据表名'
    const remote = tableName.value.trim()
    if (!remote) return '选择或填写关联表后，将从数据库匹配中间表'
    if (pivotLoading.value) return '正在查询中间表…'
    if (!pivotTables.value.length) {
        return pivotExpectedName.value
            ? `库中无匹配中间表，生成时将创建 ${pivotExpectedName.value}`
            : '库中无匹配中间表'
    }
    return `已匹配 ${pivotTables.value.length} 张中间表（${mainTable.value} ↔ ${remote}），也可直接输入表名`
})

/** pivotKeysFromList 中间表是否来自库匹配（锁定外键） */
const pivotKeysFromList = computed(() => {
    const t = field.value?.relation.pivotTable?.trim()
    if (!t) return false
    return pivotTables.value.some((i) => i.table === t)
})

/** tableLastSegment 表名末段 */
const tableLastSegment = (table: string): string => {
    const parts = table.split('_').filter(Boolean)
    return parts.length ? parts[parts.length - 1]! : table
}

/** columnMatchesSegment 列名是否对应表名末段 */
const columnMatchesSegment = (col: string, seg: string): boolean => {
    if (!seg) return false
    return col === `${seg}_id` || col.endsWith(`_${seg}_id`)
}

/** inferPivotKeysFallback 按主表 + 关联表推断外键 */
const inferPivotKeysFallback = (f: CrudFieldSchema) => {
    if (!mainTable.value) return
    const pivot = defaultPivot(mainTable.value, f.prop, tableName.value.trim() || undefined)
    f.relation.foreignKey = pivot.foreignKey
    f.relation.relatedKey = pivot.relatedKey
}

/** syncPivotKeysFromTable 手填中间表时从库解析或推断外键 */
const syncPivotKeysFromTable = async (pivotTable: string) => {
    const f = field.value
    if (!f || !pivotTable) return
    try {
        const res = await getRemoteTableColumns(pivotTable)
        const idCols = res.fields.map((col) => col.name).filter((name) => name.endsWith('_id'))
        if (idCols.length >= 2) {
            const mainSeg = tableLastSegment(mainTable.value)
            const relatedSeg = tableLastSegment(tableName.value.trim() || 'related')
            let fk = idCols.find((c) => columnMatchesSegment(c, mainSeg))
            let rk = idCols.find((c) => columnMatchesSegment(c, relatedSeg))
            if (!fk || !rk) {
                if (idCols.length === 2) {
                    fk = fk ?? idCols[0]
                    rk = rk ?? (idCols[1] === fk ? idCols[0] : idCols[1])
                }
            }
            if (fk && rk && fk !== rk) {
                f.relation.foreignKey = fk
                f.relation.relatedKey = rk
                return
            }
        }
    } catch {
        // 表不存在或未建，走推断
    }
    inferPivotKeysFallback(f)
}

/** guessLabel 从字段列表推测默认 label */
const guessLabel = (names: string[], pk: string | null): string => {
    for (const n of ['name', 'title', 'nickname', 'username', 'label']) {
        if (names.includes(n)) return n
    }
    const first = names.find((n) => n !== pk)
    return first || pk || 'name'
}

/** applyPivotItem 写回中间表及外键 */
const applyPivotItem = (item: PivotTableItem) => {
    const f = field.value
    if (!f) return
    f.relation.pivotTable = item.table
    f.relation.foreignKey = item.foreignKey
    f.relation.relatedKey = item.relatedKey
}

/** onPivotTableChange 选择或输入中间表 */
const onPivotTableChange = (table: string | undefined) => {
    const f = field.value
    if (!f) return
    if (!table?.trim()) {
        delete f.relation.pivotTable
        delete f.relation.foreignKey
        delete f.relation.relatedKey
        return
    }
    const name = table.trim()
    const item = pivotTables.value.find((i) => i.table === name)
    if (item) {
        applyPivotItem(item)
        return
    }
    f.relation.pivotTable = name
    void syncPivotKeysFromTable(name)
}

/** pickDefaultPivot 自动选中推荐或已有中间表 */
const pickDefaultPivot = () => {
    const f = field.value
    if (!f || !pivotTables.value.length) return
    const cur = f.relation.pivotTable?.trim()
    if (cur) {
        const existing = pivotTables.value.find((i) => i.table === cur)
        if (existing) applyPivotItem(existing)
        return
    }
    const suggested = pivotTables.value.find((i) => i.suggested) ?? pivotTables.value[0]
    if (suggested) applyPivotItem(suggested)
}

/** loadPivotTables 从数据库匹配中间表 */
const loadPivotTables = async () => {
    const remote = tableName.value.trim()
    pivotTables.value = []
    pivotExpectedName.value = ''
    if (!isMulti.value || !remote || !mainTable.value) return

    pivotLoading.value = true
    try {
        const res = await listPivotTables(mainTable.value, remote)
        pivotTables.value = res.items
        pivotExpectedName.value = res.expectedName
        pickDefaultPivot()
    } catch (e: unknown) {
        pivotTables.value = []
        ElMessage.warning(e instanceof Error ? e.message : '查询中间表失败')
    } finally {
        pivotLoading.value = false
    }
}

/** takeSnapshot 保存打开弹窗时的字段快照 */
const takeSnapshot = () => {
    const f = field.value
    if (!f) {
        snapshot.value = ''
        return
    }
    snapshot.value = JSON.stringify({
        remote: f.ext?.remote ? { ...f.ext.remote } : undefined,
        relation: { ...f.relation },
        form: { ...f.form },
        list: { ...f.list },
        column: { ...f.column },
    })
}

/** restoreSnapshot 取消时还原字段快照 */
const restoreSnapshot = () => {
    const f = field.value
    if (!f || !snapshot.value) return
    const s = JSON.parse(snapshot.value) as {
        remote?: { api?: string; valueField?: string; labelField?: string; table?: string }
        relation: typeof f.relation
        form: typeof f.form
        list: typeof f.list
        column: typeof f.column
    }
    if (s.remote) {
        const remote = ensureFieldRemoteExt(f)
        remote.api = s.remote.api ?? ''
        remote.valueField = s.remote.valueField ?? 'id'
        remote.labelField = s.remote.labelField ?? 'name'
        if (s.remote.table) remote.table = s.remote.table
        else delete remote.table
    } else if (f.ext?.remote) {
        delete f.ext.remote
    }
    Object.assign(f.relation, s.relation)
    Object.assign(f.form, s.form)
    Object.assign(f.list, s.list)
    if (s.column) Object.assign(f.column, s.column)
}

/** loadControllers 加载控制器列表 */
const loadControllers = async () => {
    loading.value = true
    try {
        controllers.value = await listRemoteControllers()
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '加载控制器失败')
        controllers.value = []
    } finally {
        loading.value = false
    }
}

/** loadColumns 按表名加载字段 */
const loadColumns = async (table: string) => {
    const t = table.trim()
    if (!t) {
        columns.value = []
        tablePk.value = null
        return
    }
    columnsLoading.value = true
    try {
        const res = await getRemoteTableColumns(t)
        columns.value = res.fields
        tablePk.value = res.pk
        const names = res.fields.map((f) => f.name)
        const remote = remoteCfg.value
        if (remote) {
            remote.table = t
            if (!names.includes(remote.valueField || '')) {
                remote.valueField = res.pk || names[0] || 'id'
            }
            if (!names.includes(remote.labelField || '')) {
                remote.labelField = guessLabel(names, res.pk)
            }
        }
    } catch (e: unknown) {
        columns.value = []
        tablePk.value = null
        ElMessage.warning(e instanceof Error ? e.message : '读取表字段失败')
    } finally {
        columnsLoading.value = false
    }
}

/** initDialog 打开弹窗时初始化 */
const initDialog = async () => {
    takeSnapshot()
    await loadControllers()
    tableName.value = remoteCfg.value?.table || selectedController.value?.table || ''
    if (tableName.value) {
        await loadColumns(tableName.value)
        await loadPivotTables()
    } else {
        columns.value = []
        tablePk.value = null
        pivotTables.value = []
    }
}

watch(visible, (v) => {
    if (v) void initDialog()
})

/** onControllerChange 选择控制器后写回 remote.api */
const onControllerChange = (key: string | undefined) => {
    const f = field.value
    if (!key || !f) return
    const c = controllers.value.find((x) => x.key === key)
    if (!c) return
    const remote = ensureFieldRemoteExt(f)
    remote.api = c.listUrl
    tableName.value = c.table || ''
    if (c.pk) remote.valueField = c.pk
    if (tableName.value) {
        void loadColumns(tableName.value).then(() => loadPivotTables())
    } else {
        columns.value = []
        tablePk.value = null
        pivotTables.value = []
        delete remote.table
    }
}

/** onTableBlur 手填表名后拉字段并匹配中间表 */
const onTableBlur = () => {
    void loadColumns(tableName.value).then(() => loadPivotTables())
}

/** syncColumnFromValueField 远程单选：按 Value 字段表类型写回数据库列 */
const syncColumnFromValueField = () => {
    const f = field.value
    if (!f || isMulti.value) return
    const valueName = remoteCfg.value?.valueField?.trim()
    if (!valueName) return
    const valueCol = columns.value.find((c) => c.name === valueName)
    if (!valueCol?.type) return
    applyParsedColumnType(f, parseMysqlType(valueCol.type), {
        nullable: true,
        default: null,
        enabled: true,
    })
}

watch(
    () => [remoteCfg.value?.valueField, columns.value] as const,
    () => {
        if (!visible.value || isMulti.value) return
        syncColumnFromValueField()
    },
)

/** onCancel 取消并还原快照 */
const onCancel = () => {
    restoreSnapshot()
    visible.value = false
}

/** onConfirm 写回 list/relation 层配置 */
const onConfirm = () => {
    const f = field.value
    if (!f) return
    const c = selectedController.value
    if (!c) {
        ElMessage.warning('请选择控制器')
        return
    }
    const remoteExt = ensureFieldRemoteExt(f)
    if (!remoteExt.valueField) remoteExt.valueField = 'id'
    if (!remoteExt.labelField) remoteExt.labelField = 'name'
    if (tableName.value.trim()) remoteExt.table = tableName.value.trim()

    if (!isLayerEnabled(f.form)) {
        f.form = { render: isMulti.value ? 'remote_multi_select' : 'remote_select', enabled: true }
    } else {
        f.form.enabled = true
        f.form.render = isMulti.value ? 'remote_multi_select' : 'remote_select'
    }

    f.list.enabled = true
    if (!f.list.render) {
        f.list.render = isMulti.value ? 'tags' : 'tag'
        f.list.minWidth = 120
    }
    const rel = relatedNameFromProp(f.prop)
    f.list.relationProp = isMulti.value
        ? `${rel}s`
        : `${rel}.${remoteExt.labelField}`

    if (isMulti.value) {
        f.relation.enabled = true
        f.relation.type = 'belongs_to_many'
        f.column.enabled = false
        if (!f.relation.pivotTable?.trim() && pivotExpectedName.value && mainTable.value) {
            f.relation.pivotTable = pivotExpectedName.value
            const mainSeg = mainTable.value.split('_').filter(Boolean).pop() || mainTable.value
            const relatedSeg = tableName.value.trim().split('_').filter(Boolean).pop() || 'related'
            if (!f.relation.foreignKey) f.relation.foreignKey = `${mainSeg}_id`
            if (!f.relation.relatedKey) f.relation.relatedKey = `${relatedSeg}_id`
        }
    } else {
        f.relation.enabled = true
        f.relation.type = 'belongs_to'
        syncColumnFromValueField()
        if (!isLayerEnabled(f.column) || !f.column.type) {
            ensureColumn(f)
            Object.assign(f.column, {
                type: 'bigint',
                unsigned: true,
                nullable: true,
                default: null,
                enabled: true,
            })
        }
    }

    const render = isMulti.value ? 'remote_multi_select' : 'remote_select'
    applyRenderExt(f, render, useDevCurd().assembleConfig(), { needRemote: true })

    // 远程字段不参与关键词搜索
    f.search.keyword = false
    f.search.advanced = true
    f.search.enabled = true
    if (!f.search.render) f.search.render = render

    visible.value = false
}
</script>

<template>
    <el-dialog
        v-model="visible"
        title="编辑远程参数"
        width="560px"
        append-to-body
        destroy-on-close
    >
        <el-form v-loading="loading" label-width="96px" size="small">
            <el-form-item label="控制器" required>
                <el-select
                    :model-value="selectedKey"
                    class="!w-full"
                    filterable
                    clearable
                    placeholder="选择 admin 控制器，如 auth/Admin"
                    @update:model-value="onControllerChange"
                >
                    <el-option
                        v-for="c in controllers"
                        :key="c.key"
                        :label="c.controller"
                        :value="c.key"
                    >
                        <div class="flex justify-between gap-3">
                            <span>{{ c.controller }}</span>
                            <span class="text-xs text-gray-400">{{ c.listUrl }}</span>
                        </div>
                    </el-option>
                </el-select>
            </el-form-item>
            <el-form-item label="接口路径">
                <el-input :model-value="selectedController?.listUrl || remoteCfg?.api || ''" disabled />
            </el-form-item>
            <el-form-item label="数据表" required>
                <el-input
                    v-model="tableName"
                    clearable
                    :placeholder="
                        selectedController && !selectedController.table
                            ? '未推断到模型，请手动填写表名'
                            : '无前缀表名'
                    "
                    @blur="onTableBlur"
                />
            </el-form-item>
            <el-form-item v-if="remoteCfg" label="Value 字段">
                <el-select
                    v-model="remoteCfg.valueField"
                    class="!w-full"
                    filterable
                    :loading="columnsLoading"
                    :disabled="!fieldOptions.length"
                    placeholder="选项 value，默认主键"
                >
                    <el-option
                        v-for="o in fieldOptions"
                        :key="o.value"
                        :label="o.label"
                        :value="o.value"
                    />
                </el-select>
            </el-form-item>
            <el-form-item v-if="remoteCfg" label="Label 字段">
                <el-select
                    v-model="remoteCfg.labelField"
                    class="!w-full"
                    filterable
                    :loading="columnsLoading"
                    :disabled="!fieldOptions.length"
                    placeholder="选项 label 展示字段"
                >
                    <el-option
                        v-for="o in fieldOptions"
                        :key="o.value"
                        :label="o.label"
                        :value="o.value"
                    />
                </el-select>
            </el-form-item>

            <template v-if="isMulti && field">
                <el-divider content-position="left">多对多中间表</el-divider>
                <p class="text-xs text-gray-500 -mt-2 mb-1">{{ pivotHint }}</p>
                <el-form-item label="中间表">
                    <el-select
                        :model-value="field.relation.pivotTable"
                        class="!w-full"
                        filterable
                        clearable
                        allow-create
                        default-first-option
                        :loading="pivotLoading"
                        placeholder="选择或输入中间表名"
                        @update:model-value="onPivotTableChange"
                    >
                        <el-option
                            v-for="o in pivotOptions"
                            :key="o.value"
                            :label="o.label"
                            :value="o.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="本表外键">
                    <el-input
                        v-model="field.relation.foreignKey"
                        placeholder="如 article_id"
                        :disabled="pivotKeysFromList"
                    />
                </el-form-item>
                <el-form-item label="关联外键">
                    <el-input
                        v-model="field.relation.relatedKey"
                        placeholder="如 tag_id"
                        :disabled="pivotKeysFromList"
                    />
                </el-form-item>
            </template>
        </el-form>
        <template #footer>
            <el-button size="small" @click="onCancel">取消</el-button>
            <el-button size="small" type="primary" @click="onConfirm">确定</el-button>
        </template>
    </el-dialog>
</template>
