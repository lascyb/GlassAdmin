import {defineStore} from 'pinia'
import {computed, ref} from 'vue'

import {useAdminTokenLocal, useAdminTokenSession} from '@/pinia/admin/token'

/** AdminUser 登录后的后台管理员信息 */
export type Admin = {
    id: number
    username: string
    nickname: string
    /** 主角色 auth_role.id，未绑定为 null */
    role_id?: number | null
}

/**
 * useAdmin 登录态唯一入口。
 * remember/user 由本 store 持久化（默认 localStorage）；
 * token 拆分到 admin_token_session / admin_token_local，由 remember 决定使用哪一侧；
 * afterHydrate 校验 token 有效性，无 token 或已过期时清空 user/remember 与两侧 token。
 */
export const useAdmin = defineStore('admin', () => {
    const tokenSession = useAdminTokenSession()
    const tokenLocal = useAdminTokenLocal()

    const data = ref<Admin | null>(null)
    const remember = ref(false)
    const referer = ref("")

    /** activeTokenStore 由 remember 选定的 token 容器 */
    const activeTokenStore = computed(() => (remember.value ? tokenLocal : tokenSession))

    const token = computed(() => activeTokenStore.value.token)
    const expireAt = computed(() => activeTokenStore.value.expireAt)
    const tokenValid = computed(() => activeTokenStore.value.valid)
    const loggedIn = computed(() => !!data.value && tokenValid.value)

    /**
     * init 登录成功：写入 remember / user，并把 token 写入 remember 对应侧、清掉另一侧
     * @param tokenValue 服务端签发 token
     * @param admin 用户信息
     * @param expireAt 服务端给出的token过期秒级时间戳
     * @param rememberLogin 是否勾选「记住我」
     */
    const init = (
        tokenValue: string,
        admin: Admin,
        expireAt: number,
        rememberLogin: boolean,
    ) => {
        const inactive = rememberLogin ? tokenSession : tokenLocal
        const target = rememberLogin ? tokenLocal : tokenSession
        inactive.clearToken()
        target.setToken(tokenValue, expireAt)
        data.value = admin
        remember.value = rememberLogin
    }

    /** clear 退出登录：清两侧 token、user、remember */
    const clear = () => {
        tokenSession.clearToken()
        tokenLocal.clearToken()
        data.value = null
    }

    const setReferer = (href: string = "") => {
        if (referer.value == "") {
            referer.value = href
        }
    }
    const getReferer = () => {
        let r = referer.value
        referer.value = ""
        return r
    }

    return {
        token,
        expireAt,
        tokenValid,
        data,
        remember,
        loggedIn,
        referer,
        init,
        clear,
        setReferer,
        getReferer
    }
}, {
    persist: {
        pick: ['data', 'remember'],
    },
})
