import { applyRenderExt } from '@/views/dev/curd/fieldExt'
import { defaultPivot } from '@/views/dev/curd/helpers'
import type { CrudFieldSchema, CrudGenerateConfig } from '@/views/dev/curd/types'
import type { FieldTemplateId } from '@/views/dev/curd/types/intent'

/** buildFieldFromTemplate 按模板生成完整字段 schema */
export const buildFieldFromTemplate = (
    id: FieldTemplateId,
    index: number,
    cfg: CrudGenerateConfig,
): CrudFieldSchema => {
    const blank = (): CrudFieldSchema => ({
        prop: `field_${index + 1}`,
        label: `字段${index + 1}`,
        uiType: 'text',
        column: { type: 'varchar', length: 255, nullable: true },
        relation: { type: 'belongs_to', enabled: false },
        list: { render: 'text', minWidth: 120 },
        search: { keyword: true },
        form: { render: 'input' },
    })

    let field: CrudFieldSchema

    switch (id) {
        case 'title':
            field = {
                prop: 'title',
                label: '标题',
                uiType: 'text',
                column: { type: 'varchar', length: 100, nullable: false },
                relation: { type: 'belongs_to', enabled: false },
                list: { render: 'text', minWidth: 140 },
                search: { keyword: true },
                form: { render: 'input', required: true, maxLength: 100 },
            }
            break
        case 'status':
            field = {
                prop: 'status',
                label: '状态',
                uiType: 'radio',
                column: { type: 'tinyint', nullable: false, default: 1 },
                relation: { type: 'belongs_to', enabled: false },
                list: { render: 'enum_tag', width: 88 },
                search: { advanced: true },
                form: { render: 'radio' },
            }
            applyRenderExt(field, 'radio', cfg, { needEnum: true })
            break
        case 'switch':
            field = {
                prop: 'is_top',
                label: '置顶',
                uiType: 'switch',
                column: { type: 'tinyint', nullable: false, default: 0 },
                relation: { type: 'belongs_to', enabled: false },
                list: { render: 'bool', width: 72 },
                search: { advanced: true },
                form: { render: 'switch' },
            }
            break
        case 'category':
            field = {
                prop: 'category_id',
                label: '分类',
                uiType: 'remote_select',
                column: { type: 'bigint', unsigned: true, nullable: true, default: null },
                relation: { type: 'belongs_to' },
                list: { render: 'enum_tag', relationProp: 'category.name', width: 110 },
                search: { advanced: true },
                form: { render: 'remote_select' },
                ext: { remote: { api: 'test.ft.Text/list', valueField: 'id', labelField: 'title' } },
            }
            break
        case 'tags': {
            const pivot = defaultPivot(cfg.table || 'item', 'tag_ids')
            field = {
                prop: 'tag_ids',
                label: '标签',
                uiType: 'remote_multi_select',
                column: { type: 'varchar', length: 255, enabled: false },
                relation: {
                    type: 'belongs_to_many',
                    pivotTable: pivot.pivotTable,
                    foreignKey: pivot.foreignKey,
                    relatedKey: pivot.relatedKey,
                },
                list: { render: 'tags', relationProp: 'tags', minWidth: 160 },
                search: { advanced: true },
                form: { render: 'remote_multi_select' },
                ext: { remote: { api: 'test.ft.Text/list', valueField: 'id', labelField: 'title' } },
            }
            break
        }
        case 'amount':
            field = {
                prop: 'amount',
                label: '金额',
                uiType: 'amount',
                column: { type: 'decimal', precision: 12, scale: 2, nullable: false, default: 0 },
                relation: { type: 'belongs_to', enabled: false },
                list: { render: 'number', width: 100 },
                search: { advanced: true },
                form: { render: 'number', numberMin: 0, numberMax: 9999999, numberStep: 0.01 },
            }
            break
        case 'datetime':
            field = {
                prop: 'publish_time',
                label: '发布时间',
                uiType: 'datetime',
                column: { type: 'bigint', nullable: true, default: null },
                relation: { type: 'belongs_to', enabled: false },
                list: { render: 'datetime', width: 170 },
                search: { advanced: true },
                form: { render: 'datetime' },
            }
            break
        case 'cover':
            field = {
                prop: 'cover',
                label: '封面',
                uiType: 'image',
                column: { type: 'varchar', length: 255, nullable: true, default: null },
                relation: { type: 'belongs_to', enabled: false },
                list: { render: 'image', width: 88 },
                search: { keyword: true, enabled: false },
                form: { render: 'image_upload' },
            }
            break
        case 'blank':
        default:
            field = blank()
            break
    }

    return field
}
