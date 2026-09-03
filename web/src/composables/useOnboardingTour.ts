import { persistStorageKeyPrefix } from '@/config/persistStorage'
import { ref } from 'vue'

/** tourPendingKey 按管理员 id 存首次登录待播标记 */
const tourPendingKey = (adminId: number): string =>
    `${persistStorageKeyPrefix}tour_pending:${adminId}`

/** tourActive 为 true 时才挂载异步 OnboardingTour */
export const tourActive = ref(false)

/** accountMenuForced 引导第 5 步强制展开头像下拉 */
export const accountMenuForced = ref(false)

/**
 * markTourPending 登录 show_tour 时写入待播
 */
export const markTourPending = (adminId: number): void => {
    if (adminId <= 0) {
        return
    }
    try {
        localStorage.setItem(tourPendingKey(adminId), '1')
    } catch {
        /* 无存储则本次无法自动弹 */
    }
}

/**
 * hasTourPending 该用户是否有未完成的首次引导
 */
export const hasTourPending = (adminId: number): boolean => {
    if (adminId <= 0) {
        return false
    }
    try {
        return localStorage.getItem(tourPendingKey(adminId)) === '1'
    } catch {
        return false
    }
}

/**
 * clearTourPending 关闭或完成引导后清除待播
 */
export const clearTourPending = (adminId: number): void => {
    if (adminId <= 0) {
        return
    }
    try {
        localStorage.removeItem(tourPendingKey(adminId))
    } catch {
        /* ignore */
    }
}

/**
 * startOnboardingTour 挂载并播放引导（首次 pending 或查看引导）
 */
export const startOnboardingTour = (): void => {
    tourActive.value = true
}

/**
 * stopOnboardingTour 卸载引导并收起强制下拉
 */
export const stopOnboardingTour = (): void => {
    tourActive.value = false
    accountMenuForced.value = false
}

/**
 * tryAutoStartTour 壳层就绪后：有 pending 则自动播放
 */
export const tryAutoStartTour = (adminId: number | undefined): void => {
    if (adminId && hasTourPending(adminId)) {
        startOnboardingTour()
    }
}
