import type { GenerateResult } from '@/apis/dev/curd'
import * as curdApi from '@/apis/dev/curd'
import type { CrudFieldSchema, CrudGenerateConfig } from '@/crud/types'
import {
    buildFieldFromTemplate,
    detectControlIntent,
    type FieldTemplateId,
} from '@/views/dev/curd/fieldIntent'
import { cloneConfig, emptyConfig, leafView, syncDerivedFields } from '@/views/dev/curd/helpers'
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

type Step = 'entry' | 'build'

/**
 * useDevCurd CRUD 可视化生成页状态：入口/构建、配置、字段选中、生成结果与弹窗。
 * 不 persist，离开页面可重置。
 */
export const useDevCurd = defineStore('dev_curd', () => {
    const step = ref<Step>('entry')
    const config = ref<CrudGenerateConfig>(emptyConfig())
    const selectedIndex = ref(0)
    const force = ref(false)
    const noAuth = ref(false)
    const saveHistory = ref(true)
    const loading = ref(false)
    const historyVisible = ref(false)
    const tableVisible = ref(false)
    const resultVisible = ref(false)
    const result = ref<GenerateResult | null>(null)
    const resultError = ref('')

    const selectedField = computed({
        get: (): CrudFieldSchema | null => config.value.fields[selectedIndex.value] ?? null,
        set: (f: CrudFieldSchema | null) => {
            if (!f) return
            const idx = selectedIndex.value
            if (idx < 0 || idx >= config.value.fields.length) return
            config.value.fields[idx] = f
        },
    })

    /** goBuild 进入构建页 */
    const goBuild = (cfg: CrudGenerateConfig) => {
        config.value = cloneConfig(cfg)
        selectedIndex.value = 0
        step.value = 'build'
    }

    /** goBack 返回入口 */
    const goBack = () => {
        step.value = 'entry'
    }

    /** goEmpty 从零开始 */
    const goEmpty = () => {
        goBuild(emptyConfig())
    }

    /** loadFromHistory 从历史加载 */
    const loadFromHistory = async (id: number) => {
        loading.value = true
        try {
            const detail = await curdApi.readHistory(id)
            if (!detail.configParsed) {
                throw new Error('历史配置无法解析')
            }
            goBuild(detail.configParsed)
            ElMessage.success(`已加载历史 #${id}`)
        } catch (e: unknown) {
            ElMessage.error(e instanceof Error ? e.message : '加载历史失败')
        } finally {
            loading.value = false
        }
    }

    /** loadFromTable 从表结构反推 */
    const loadFromTable = async (table: string) => {
        loading.value = true
        try {
            const cfg = await curdApi.loadTableSchema(table)
            goBuild(cfg)
            ElMessage.success(`已加载表 ${table}`)
        } catch (e: unknown) {
            ElMessage.error(e instanceof Error ? e.message : '加载表结构失败')
        } finally {
            loading.value = false
        }
    }

    /** validate 提交前校验 */
    const validate = (): string | null => {
        if (!config.value.table?.trim()) return '请填写数据表名'
        const menus = config.value.menus ?? []
        if (!menus.length) return '请配置至少一级菜单（含页面）'
        const last = menus[menus.length - 1]
        if (last?.type !== 'view') return '末级菜单必须是页面'
        const view = leafView(menus)
        if (!view?.path?.trim()) return '请填写页面 path'
        if (!view?.title?.trim()) return '请填写页面名称'
        for (let i = 0; i < menus.length; i++) {
            const m = menus[i]
            if (!m?.path?.trim()) return `第 ${i + 1} 级 path 不能为空`
            if (!m?.title?.trim()) return `第 ${i + 1} 级 title 不能为空`
        }
        const p = config.value.paths
        if (!p?.controller?.trim()) return '请填写控制器相对路径'
        if (!p?.model?.trim()) return '请填写模型相对路径'
        if (!p?.validate?.trim()) return '请填写验证器相对路径'
        if (!p?.apis?.trim()) return '请填写前端 API 相对路径'
        if (!p?.viewsDir?.trim()) return '请填写前端视图目录'
        if (!config.value.fields?.length) return '至少需要一个字段'
        for (const f of config.value.fields) {
            if (!f.prop?.trim()) return '存在未填写字段名的项'
            if (!f.label?.trim()) return '存在未填写中文标签的项'
            const intent = detectControlIntent(f)
            if (intent === 'remote' || intent === 'remote_multi') {
                const key =
                    f.form && f.form !== false ? f.form.optionsKey?.trim() : ''
                if (!key) {
                    return `字段「${f.label || f.prop}」为远程控件，请填写数据源（如 example.Category/list）`
                }
            }
            if (intent === 'status' || intent === 'select') {
                const mapKey =
                    (f.form && f.form !== false && f.form.statusMapKey) ||
                    f.list?.statusMapKey
                if (!mapKey?.trim()) {
                    return `字段「${f.label || f.prop}」为枚举控件，请配置映射键`
                }
                const entries = config.value.maps?.[mapKey]
                if (!entries || Object.keys(entries).length === 0) {
                    return `字段「${f.label || f.prop}」的枚举映射「${mapKey}」为空`
                }
            }
            if (intent === 'remote_multi') {
                if (!f.relation?.pivotTable?.trim()) {
                    return `字段「${f.label || f.prop}」为远程多选，请填写中间表`
                }
            }
        }
        return null
    }

    /** runGenerate 预览或生成 */
    const runGenerate = async (dryRun: boolean) => {
        const err = validate()
        if (err) {
            ElMessage.warning(err)
            return
        }

        if (!dryRun && force.value) {
            try {
                await ElMessageBox.confirm('将强制覆盖已存在文件，是否继续？', '确认生成', {
                    type: 'warning',
                })
            } catch {
                return
            }
        }

        loading.value = true
        result.value = null
        resultError.value = ''
        try {
            const payload = cloneConfig(config.value)
            syncDerivedFields(payload)
            const res = await curdApi.generate(payload, {
                dryRun,
                force: force.value,
                noAuth: noAuth.value,
                saveHistory: saveHistory.value,
            })
            result.value = res
            resultVisible.value = true
            ElMessage.success(dryRun ? '预览完成' : '生成完成')
        } catch (e: unknown) {
            resultError.value = e instanceof Error ? e.message : '生成失败'
            result.value = null
            resultVisible.value = true
            ElMessage.error(resultError.value)
        } finally {
            loading.value = false
        }
    }

    /** addField 按模板新增字段（默认空白） */
    const addField = (templateId: FieldTemplateId = 'blank') => {
        const next = buildFieldFromTemplate(templateId, config.value.fields.length, config.value)
        // 同名 prop 时追加序号，避免冲突
        const exists = config.value.fields.some((f) => f.prop === next.prop)
        if (exists) {
            next.prop = `${next.prop}_${config.value.fields.length + 1}`
        }
        config.value.fields = [...config.value.fields, next]
        selectedIndex.value = config.value.fields.length - 1
    }

    /** removeFieldAt 按索引删除字段 */
    const removeFieldAt = (idx: number) => {
        if (config.value.fields.length <= 1) return
        const list = config.value.fields.filter((_, i) => i !== idx)
        config.value.fields = list
        if (selectedIndex.value === idx) {
            selectedIndex.value = Math.min(idx, list.length - 1)
        } else if (selectedIndex.value > idx) {
            selectedIndex.value -= 1
        }
    }

    /** reorderFields 拖拽重排字段 */
    const reorderFields = (oldIndex: number, newIndex: number) => {
        if (oldIndex === newIndex) return
        const list = [...config.value.fields]
        const [moved] = list.splice(oldIndex, 1)
        if (!moved) return
        list.splice(newIndex, 0, moved)
        config.value.fields = list
        if (selectedIndex.value === oldIndex) {
            selectedIndex.value = newIndex
        } else if (oldIndex < selectedIndex.value && newIndex >= selectedIndex.value) {
            selectedIndex.value -= 1
        } else if (oldIndex > selectedIndex.value && newIndex <= selectedIndex.value) {
            selectedIndex.value += 1
        }
    }

    return {
        step,
        config,
        selectedIndex,
        force,
        noAuth,
        saveHistory,
        loading,
        historyVisible,
        tableVisible,
        resultVisible,
        result,
        resultError,
        selectedField,
        goBuild,
        goBack,
        goEmpty,
        loadFromHistory,
        loadFromTable,
        validate,
        runGenerate,
        addField,
        removeFieldAt,
        reorderFields,
    }
})
