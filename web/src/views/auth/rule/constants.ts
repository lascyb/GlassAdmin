/** FrontendRuleTypeTag el-tag 颜色类型 */
export type FrontendRuleTypeTag = 'primary' | 'success' | 'warning' | 'info' | 'danger'

/** FrontendRuleTypeItem 前端规则 type 展示项 */
export type FrontendRuleTypeItem = {
    label: string
    type: FrontendRuleTypeTag
}

/** FRONTEND_RULE_TYPE_MAP 前端规则 type 展示配置 {menu:目录,view:页面,route:路由,btn:按钮,...} */
export const FRONTEND_RULE_TYPE_MAP: Record<string, FrontendRuleTypeItem> = {
    menu: { label: '目录', type: 'primary' },
    view: { label: '页面', type: 'success' },
    route: { label: '路由', type: 'warning' },
    btn: { label: '按钮', type: 'warning' },
    page: { label: '页面', type: 'info' },
    link: { label: '外链', type: 'info' },
    iframe: { label: '内嵌', type: 'danger' },
}

/**
 * ruleTypeLabel 前端规则 type 转中文标签
 */
export function ruleTypeLabel(type: string): string {
    return FRONTEND_RULE_TYPE_MAP[type]?.label ?? type
}

/**
 * ruleTypeTagType 前端规则 type → el-tag 的 type
 */
export function ruleTypeTagType(type: string): FrontendRuleTypeTag {
    return FRONTEND_RULE_TYPE_MAP[type]?.type ?? 'info'
}
