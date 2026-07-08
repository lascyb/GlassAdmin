import { getData } from '@/apis/http'

/** DashboardKpi 系统使用情况 KPI */
export type DashboardKpi = {
    admin_total: number
    admin_enabled: number
    admin_disabled: number
    role_count: number
    frontend_rule_count: number
    backend_api_count: number
    today_active_users: number
    today_ops: number
    today_write_ops: number
    today_login_count: number
}

/** DashboardTrend 趋势序列 */
export type DashboardTrend = {
    days: number
    dates: string[]
    ops: number[]
    write_ops: number[]
    active_users: number[]
    logins: number[]
}

/** DashboardRankAdmin 活跃管理员 */
export type DashboardRankAdmin = {
    admin_id: number
    name: string
    count: number
}

/** DashboardRankAction 高频操作 */
export type DashboardRankAction = {
    action: string
    count: number
}

/** DashboardRoleDist 角色分布 */
export type DashboardRoleDist = {
    role_name: string
    count: number
}

/** DashboardModuleDist 模块操作分布 */
export type DashboardModuleDist = {
    module: string
    count: number
}

/** DashboardRecentLog 最近操作日志 */
export type DashboardRecentLog = {
    id: number
    admin_id: number
    admin_name: string
    action: string
    summary: string
    ip?: string | null
    create_time?: string | null
}

/** DashboardOverview 仪表盘概览 */
export type DashboardOverview = {
    kpi: DashboardKpi
    trend: DashboardTrend
    top_admins: DashboardRankAdmin[]
    top_actions: DashboardRankAction[]
    role_distribution: DashboardRoleDist[]
    module_distribution: DashboardModuleDist[]
    recent_logs: DashboardRecentLog[]
}

/**
 * fetchUsageOverview 系统使用情况仪表盘数据
 */
export const fetchUsageOverview = async (days = 7) => {
    return getData<DashboardOverview>('dashboard.Usage/index', { days })
}
