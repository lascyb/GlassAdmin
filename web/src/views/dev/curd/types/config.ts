import type { CrudFieldSchema } from './field'
import type { CrudMenuLevelType } from './menu'
import type { TagType } from './tag'
import type { CrudWriteMode } from './write-mode'

/** CrudOptionItem 静态枚举/选项项 */
export type CrudOptionItem = {
    /** value 选项值 */
    value: string | number
    /** label 展示文案 */
    label: string
    /** type Element Plus Tag 类型（status/tag 列可选） */
    type?: TagType | string
}

/** CrudOutputPaths 生成文件相对路径（不含固定前缀） */
export type CrudOutputPaths = {
    /** controller 相对 app/admin/controller/，如 Countries.php 或 demo/Item.php */
    controller: string
    /** model 相对 app/admin/model/ */
    model: string
    /** validate 相对 app/admin/validate/ */
    validate: string
    /** apis 相对 web/src/apis/，如 countries.ts 或 demo/item.ts */
    apis: string
    /** viewsDir 相对 web/src/views/，如 countries/ 或 demo/item/ */
    viewsDir: string
}

/** CrudMenuLevel 前端菜单层级（index0=一级；末项必须为 view） */
export type CrudMenuLevel = {
    /** type 仅最后一项为 view，前面均为 menu */
    type: CrudMenuLevelType
    /** path 路由片段，相对父级 */
    path: string
    /** title 显示名 */
    title: string
    /** icon 图标 */
    icon?: string
    /** sort 排序 */
    sort?: number
    /** enabled false 时本级不生成，下级升一级 */
    enabled: boolean
}

/**
 * CrudGenerateConfig 一键生成输入配置
 * 用途：CLI / dev/curd UI 的统一 JSON 契约
 */
export type CrudGenerateConfig = {
    /** table 数据表名（无前缀，如 example_data） */
    table: string
    /** menus 无限级菜单（末项 type=view；enabled=false 时下级升一级） */
    menus: CrudMenuLevel[]
    /** paths 生成文件相对路径 */
    paths: CrudOutputPaths
    /** fields 字段元数据 */
    fields: CrudFieldSchema[]
    /** options 静态枚举/选项（生成 constants.ts） */
    options?: Record<string, CrudOptionItem[]>
    /** writeMode 写盘策略，默认 skip */
    writeMode?: CrudWriteMode
    /** generateAuth 是否写入权限/菜单到数据库，默认 true；不挂角色 */
    generateAuth?: boolean
    /** module 由 paths.controller 派生（内部/后端用） */
    module?: string
    /** entity 由 paths.controller 派生（内部/后端用） */
    entity?: string
    /** title 由末级 view.title 派生 */
    title?: string
    /** moduleTitle 由首个启用菜单派生 */
    moduleTitle?: string
    /** menuIcon 由首个启用项派生 */
    menuIcon?: string
    /** menuSort 由首个启用项派生 */
    menuSort?: number
}
