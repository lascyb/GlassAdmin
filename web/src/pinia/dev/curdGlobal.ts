import type { CrudGenerateConfig, CrudMenuLevel, CrudOutputPaths } from '@/views/dev/curd/types'
import {
    emptyConfig,
    fillMenuLevelIcons,
    guessMetaFromTable,
    leafView,
    syncDerivedFields,
} from '@/views/dev/curd/helpers'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

/**
 * useDevCurdGlobal 全局配置（表名/菜单/路径/生成开关）
 * 与字段编辑 store 分离；不 persist。
 */
export const useDevCurdGlobal = defineStore('dev_curd_global', () => {
    const seed = emptyConfig()

    const table = ref(seed.table)
    const connection = ref(seed.connection ?? '')
    const menus = ref<CrudMenuLevel[]>(seed.menus)
    const paths = ref<CrudOutputPaths>(structuredClone(seed.paths))
    const generateAuth = ref(seed.generateAuth !== false)

    const force = ref(false)
    const noAuth = ref(false)
    const saveHistory = ref(true)

    /** 派生字段（由 syncDerivedFields 回填） */
    const title = ref(seed.title ?? '')
    const module = ref(seed.module ?? '')
    const entity = ref(seed.entity ?? '')
    const moduleTitle = ref(seed.moduleTitle ?? '')
    const menuIcon = ref(seed.menuIcon ?? '')
    const menuSort = ref(seed.menuSort ?? 0)

    /** asMetaConfig 拼出供 syncDerived 使用的片段 */
    const asMetaConfig = (): CrudGenerateConfig =>
        ({
            table: table.value,
            connection: connection.value,
            menus: menus.value,
            paths: paths.value,
            generateAuth: generateAuth.value,
            fields: [],
            title: title.value,
            module: module.value,
            entity: entity.value,
            moduleTitle: moduleTitle.value,
            menuIcon: menuIcon.value,
            menuSort: menuSort.value,
        }) as CrudGenerateConfig

    /** applyDerived 从 cfg 写回派生字段 */
    const applyDerived = (cfg: CrudGenerateConfig) => {
        title.value = cfg.title ?? ''
        module.value = cfg.module ?? ''
        entity.value = cfg.entity ?? ''
        moduleTitle.value = cfg.moduleTitle ?? ''
        menuIcon.value = cfg.menuIcon ?? ''
        menuSort.value = cfg.menuSort ?? 0
        generateAuth.value = cfg.generateAuth !== false
    }

    /** syncMeta 同步 menus/paths 派生字段 */
    const syncMeta = () => {
        const cfg = asMetaConfig()
        syncDerivedFields(cfg)
        applyDerived(cfg)
    }

    /** hydrate 从完整配置灌入全局态 */
    const hydrate = (cfg: CrudGenerateConfig) => {
        table.value = cfg.table ?? ''
        connection.value = cfg.connection ?? ''
        menus.value = structuredClone(cfg.menus ?? [])
        fillMenuLevelIcons(menus.value)
        paths.value = structuredClone(cfg.paths ?? emptyConfig().paths)
        generateAuth.value = cfg.generateAuth !== false
        applyDerived(cfg)
        syncMeta()
    }

    /** toMeta 导出全局片段（供合并生成） */
    const toMeta = (): Pick<
        CrudGenerateConfig,
        | 'table'
        | 'connection'
        | 'menus'
        | 'paths'
        | 'generateAuth'
        | 'title'
        | 'module'
        | 'entity'
        | 'moduleTitle'
        | 'menuIcon'
        | 'menuSort'
    > => ({
        table: table.value,
        connection: connection.value,
        menus: menus.value,
        paths: paths.value,
        generateAuth: generateAuth.value,
        title: title.value,
        module: module.value,
        entity: entity.value,
        moduleTitle: moduleTitle.value,
        menuIcon: menuIcon.value,
        menuSort: menuSort.value,
    })

    const leafTitle = computed({
        get: () => leafView(menus.value)?.title ?? '',
        set: (v: string) => {
            const view = leafView(menus.value)
            if (view) {
                view.title = v
                syncMeta()
            }
        },
    })

    /** onMenusChange menus 编辑后回写派生字段 */
    const onMenusChange = () => {
        const list = menus.value
        if (list.length) {
            const last = list[list.length - 1]
            if (last) last.type = 'view'
            for (let i = 0; i < list.length - 1; i++) {
                const row = list[i]
                if (row) row.type = 'menu'
            }
            fillMenuLevelIcons(list)
        }
        syncMeta()
    }

    /** addMenuLevel 在页面之前插入一级 menu */
    const addMenuLevel = () => {
        const list = [...menus.value]
        const idx = Math.max(0, list.length - 1)
        list.splice(idx, 0, {
            type: 'menu',
            path: `menu${idx + 1}`,
            title: `菜单${idx + 1}`,
            icon: 'el:Folder',
            enabled: true,
        })
        fillMenuLevelIcons(list)
        menus.value = list
        onMenusChange()
    }

    /** removeMenuAt 删除非页面行 */
    const removeMenuAt = (index: number) => {
        if (index < 0 || index >= menus.value.length - 1) return
        const list = [...menus.value]
        list.splice(index, 1)
        menus.value = list
        onMenusChange()
    }

    /** onTableInput 表名变更时覆盖 menus + paths */
    const onTableInput = (value: string) => {
        const normalized = value.trim().toLowerCase()
        table.value = normalized
        const meta = guessMetaFromTable(normalized)
        menus.value = meta.menus
        paths.value = meta.paths
        syncMeta()
    }

    const levelLabelNames = ['一级', '二级', '三级', '四级', '五级', '六级', '七级', '八级']
    /** levelLabel 级别文案 */
    const levelLabel = (index: number): string => levelLabelNames[index] ?? `${index + 1}级`

    return {
        table,
        connection,
        menus,
        paths,
        generateAuth,
        force,
        noAuth,
        saveHistory,
        title,
        module,
        entity,
        moduleTitle,
        menuIcon,
        menuSort,
        leafTitle,
        hydrate,
        toMeta,
        syncMeta,
        onMenusChange,
        addMenuLevel,
        removeMenuAt,
        onTableInput,
        levelLabel,
    }
})
