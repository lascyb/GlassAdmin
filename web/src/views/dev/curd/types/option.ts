/** NamedOption 带中文标签的孤立选项（UI 展示，不强制联动 schema） */
export type NamedOption = {
    /** value 选项值 */
    value: string
    /** label 展示文案 */
    label: string
}

/** NamedOptionGroup 分组选项（供 el-option-group / 点选面板） */
export type NamedOptionGroup = {
    /** label 分组名 */
    label: string
    /** options 组内选项 */
    options: NamedOption[]
}
