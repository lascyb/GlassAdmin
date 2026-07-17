import type { GenerateResult } from '@/apis/dev/curd'
import * as curdApi from '@/apis/dev/curd'
import type { CrudGenerateConfig } from '@/views/dev/curd/types'
import { useDevCurdField } from '@/pinia/dev/curdField'
import { useDevCurdGlobal } from '@/pinia/dev/curdGlobal'
import {
    FIELD_TYPE_NEED_ENUM,
    FIELD_TYPE_NEED_REMOTE,
    resolveFieldType,
} from '@/views/dev/curd/constants/field'
import { enumKeyOf, remoteApiOf } from '@/views/dev/curd/fieldExt'
import { buildFieldFromTemplate } from '@/views/dev/curd/fieldTemplate'
import type { FieldTemplateId } from '@/views/dev/curd/types/intent'
import { cloneConfig, emptyConfig, leafView, syncDerivedFields } from '@/views/dev/curd/helpers'
import { ElMessage, ElMessageBox } from 'element-plus'
import { defineStore } from 'pinia'
import { ref } from 'vue'

const enumNeedSet = new Set<string>(FIELD_TYPE_NEED_ENUM)
const remoteNeedSet = new Set<string>(FIELD_TYPE_NEED_REMOTE)

type Step = 'entry' | 'build'

/**
 * useDevCurd CRUD 字段列表与生成流程状态。
 * 全局配置见 useDevCurdGlobal；选中字段见 useDevCurdField；不 persist。
 */
export const useDevCurd = defineStore('dev_curd', () => {
    const step = ref<Step>('entry')
    const config = ref<CrudGenerateConfig>(emptyConfig())
    const loading = ref(false)
    const historyVisible = ref(false)
    const tableVisible = ref(false)
    const resultVisible = ref(false)
    const result = ref<GenerateResult | null>(null)
    const resultError = ref('')

    /** assembleConfig 合并全局 + 字段配置 */
    const assembleConfig = (): CrudGenerateConfig => {
        const meta = useDevCurdGlobal().toMeta()
        return {
            ...cloneConfig(config.value),
            ...meta,
            fields: config.value.fields,
            options: config.value.options,
        }
    }

    /** goBuild 进入构建页 */
    const goBuild = (cfg: CrudGenerateConfig) => {
        const next = cloneConfig(cfg)
        config.value = next
        useDevCurdGlobal().hydrate(next)
        useDevCurdField().resetSelection()
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
        const cfg = assembleConfig()
        if (!cfg.table?.trim()) return '请填写数据表名'
        const menus = cfg.menus ?? []
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
        const p = cfg.paths
        if (!p?.controller?.trim()) return '请填写控制器相对路径'
        if (!p?.model?.trim()) return '请填写模型相对路径'
        if (!p?.validate?.trim()) return '请填写验证器相对路径'
        if (!p?.apis?.trim()) return '请填写前端 API 相对路径'
        if (!p?.viewsDir?.trim()) return '请填写前端视图目录'
        if (!cfg.fields?.length) return '至少需要一个字段'
        for (const f of cfg.fields) {
            if (!f.prop?.trim()) return '存在未填写字段名的项'
            if (!f.label?.trim()) return '存在未填写中文标签的项'
            const type = resolveFieldType(f)
            if (type && remoteNeedSet.has(type)) {
                const api = remoteApiOf(f)
                if (!api) {
                    return `字段「${f.label || f.prop}」为远程控件，请填写数据源（如 test.ft.Text/list）`
                }
            }
            if (type && enumNeedSet.has(type)) {
                const optKey = enumKeyOf(f)
                if (!optKey?.trim()) {
                    return `字段「${f.label || f.prop}」为枚举控件，请配置 options 键`
                }
                const items = cfg.options?.[optKey]
                if (!items?.length) {
                    return `字段「${f.label || f.prop}」的枚举「${optKey}」为空`
                }
            }
            if (type === 'remote_multi_select') {
                if (!f.relation?.pivotTable?.trim()) {
                    return `字段「${f.label || f.prop}」为远程多选，请填写中间表`
                }
            }
        }
        return null
    }

    /** runGenerate 预览或生成 */
    const runGenerate = async (dryRun: boolean) => {
        const global = useDevCurdGlobal()
        const err = validate()
        if (err) {
            ElMessage.warning(err)
            return
        }

        if (!dryRun && global.force) {
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
            const payload = assembleConfig()
            syncDerivedFields(payload)
            const res = await curdApi.generate(payload, {
                dryRun,
                force: global.force,
                noAuth: global.noAuth,
                saveHistory: global.saveHistory,
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
        const cfg = assembleConfig()
        const next = buildFieldFromTemplate(templateId, cfg.fields.length, cfg)
        // 同名 prop 时追加序号，避免冲突
        const exists = cfg.fields.some((f) => f.prop === next.prop)
        if (exists) {
            next.prop = `${next.prop}_${cfg.fields.length + 1}`
        }
        config.value.fields = [...config.value.fields, next]
        useDevCurdField().selectLast()
    }

    /** removeFieldAt 按索引删除字段 */
    const removeFieldAt = (idx: number) => {
        if (config.value.fields.length <= 1) return
        const list = config.value.fields.filter((_, i) => i !== idx)
        config.value.fields = list
        useDevCurdField().syncAfterRemove(idx)
    }

    /** reorderFields 拖拽重排字段 */
    const reorderFields = (oldIndex: number, newIndex: number) => {
        if (oldIndex === newIndex) return
        const list = [...config.value.fields]
        const [moved] = list.splice(oldIndex, 1)
        if (!moved) return
        list.splice(newIndex, 0, moved)
        config.value.fields = list
        useDevCurdField().syncAfterReorder(oldIndex, newIndex)
    }

    return {
        step,
        config,
        loading,
        historyVisible,
        tableVisible,
        resultVisible,
        result,
        resultError,
        assembleConfig,
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
