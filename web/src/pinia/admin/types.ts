/** AdminUser 登录后的后台管理员信息 */
export type Admin = {
    id: number
    username: string
    nickname: string
    /** 头像相对路径（public/storage） */
    avatar?: string | null
    /** 绑定的角色 system_auth_role.id 列表（当前生效） */
    role_ids?: number[]
    /** 绑定的角色 system_auth_role.id 列表（当前生效） */
    role_codes?: string[]
    /** 已分配角色（含 enabled） */
    roles?: { id: number; code: string; name: string; enabled: number }[]
}

export type AdminLoginMethod = 'password' | 'feishu'
/** 空字符串表示尚未选择登录方式偏好 */
export type AdminLoginMethodPreference = AdminLoginMethod | ''
