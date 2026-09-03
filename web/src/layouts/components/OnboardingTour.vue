<script setup lang="ts">
import {
    accountMenuForced,
    clearTourPending,
    stopOnboardingTour,
} from '@/composables/useOnboardingTour'
import { useAdmin } from '@/pinia/admin'
import { useLayoutMenus } from '@/pinia/layout/menus'
import { useLayoutSetting } from '@/pinia/layout/setting'
import { nextTick, onBeforeUnmount, onMounted, ref, shallowRef } from 'vue'

/** STEP_DELAY 抽屉 / 下拉定位完成后再绑锚点；Tour 不跟踪 transform */
const STEP_DELAY = 480

const STEP_IDS = [
    'ga-tour-logo',
    'ga-tour-pref-entry',
    'ga-tour-pref-layout',
    'ga-tour-pref-accordion',
    'ga-tour-pref-catalog',
    'ga-tour-account-trigger',
    'ga-tour-account-roles',
] as const

const setting = useLayoutSetting()
const layoutMenus = useLayoutMenus()
const admin = useAdmin()

const open = ref(false)
const current = ref(0)
/** positioned 为 true 才露出 Tour，避免先画到错误坐标再跳动 */
const positioned = ref(false)
let runId = 0
/** 各步独立 shallowRef，避免把 DOM 放进深层响应式数组 */
const targetLogo = shallowRef<HTMLElement>()
const targetPrefEntry = shallowRef<HTMLElement>()
const targetLayout = shallowRef<HTMLElement>()
const targetAccordion = shallowRef<HTMLElement>()
const targetCatalog = shallowRef<HTMLElement>()
const targetAccount = shallowRef<HTMLElement>()
const targetRoles = shallowRef<HTMLElement>()
const targets = [
    targetLogo,
    targetPrefEntry,
    targetLayout,
    targetAccordion,
    targetCatalog,
    targetAccount,
    targetRoles,
]
let finished = false

const wait = (ms: number): Promise<void> =>
    new Promise((resolve) => window.setTimeout(resolve, ms))

const waitPaint = (): Promise<void> =>
    new Promise((resolve) => {
        requestAnimationFrame(() => requestAnimationFrame(() => resolve()))
    })

const rectKey = (el: HTMLElement): string => {
    const r = el.getBoundingClientRect()
    if (r.width < 2 || r.height < 2) {
        return ''
    }
    return `${Math.round(r.left)}|${Math.round(r.top)}|${Math.round(r.width)}|${Math.round(r.height)}`
}

/**
 * waitRectStable 等到目标有尺寸且连续两帧坐标不变（下拉 Popper 会二次落点）
 */
const waitRectStable = async (getEl: () => HTMLElement | null): Promise<HTMLElement | null> => {
    let prev = ''
    let hits = 0
    let el: HTMLElement | null = null
    for (let i = 0; i < 24; i++) {
        el = getEl()
        const key = el ? rectKey(el) : ''
        if (key && key === prev) {
            hits++
            if (hits >= 2) {
                return el
            }
        } else {
            hits = 0
        }
        prev = key
        await waitPaint()
    }
    return el
}

/**
 * queryStepEl 取锚点；切换角色只用已展开且可见的那一行
 */
const queryStepEl = (index: number): HTMLElement | null => {
    if (index === 6) {
        const menus = document.querySelectorAll('.ga-account-dropdown')
        for (const menu of menus) {
            const hidden = (menu as HTMLElement).getAttribute('aria-hidden')
            if (hidden === 'true') {
                continue
            }
            const item = menu.querySelector('.account-roles-item') as HTMLElement | null
            if (item && rectKey(item)) {
                return item.closest('li') ?? item
            }
        }
        return null
    }
    const id = STEP_IDS[index]
    if (!id) {
        return null
    }
    return document.getElementById(id)
}

/**
 * bindTarget 先清空再写入，迫使 Tour 重新 getBoundingClientRect
 */
const bindTarget = async (index: number, el: HTMLElement | undefined): Promise<void> => {
    const slot = targets[index]
    if (!slot) {
        return
    }
    if (slot.value) {
        slot.value = undefined
        await nextTick()
    }
    slot.value = el
}

/**
 * prepareStep 按步骤打开/关闭个人偏好与头像下拉，再写入锚点
 */
const prepareStep = async (index: number): Promise<void> => {
    document.body.classList.add('ga-tour-playing')
    if (index <= 1 || index === 5) {
        setting.layout.showSetting = false
        accountMenuForced.value = false
        if (index === 0 && layoutMenus.collapse) {
            layoutMenus.collapse = false
        }
    } else if (index >= 2 && index <= 4) {
        accountMenuForced.value = false
        setting.layout.showSetting = true
    } else {
        setting.layout.showSetting = false
        accountMenuForced.value = true
    }
    await nextTick()
    await wait(STEP_DELAY)
    await waitPaint()
    const isAccountStep = index === 5 || index === 6
    let el = isAccountStep
        ? await waitRectStable(() => queryStepEl(index))
        : queryStepEl(index)
    if (!isAccountStep) {
        el?.scrollIntoView({ block: 'nearest', inline: 'nearest' })
        await waitPaint()
    }
    await bindTarget(index, el ?? undefined)
    if (isAccountStep) {
        const settled = await waitRectStable(() => queryStepEl(index))
        await bindTarget(index, settled ?? undefined)
    }
}

/**
 * goToStep 先隐藏引导，定位完成后再显示
 */
const goToStep = async (index: number): Promise<void> => {
    const id = ++runId
    positioned.value = false
    await prepareStep(index)
    if (id !== runId) {
        return
    }
    if (!open.value) {
        open.value = true
    }
    await nextTick()
    await waitPaint()
    if (id !== runId) {
        return
    }
    positioned.value = true
}

/**
 * finishTour 关闭或完成时清 pending 并卸载
 */
const finishTour = (): void => {
    if (finished) {
        return
    }
    finished = true
    const id = admin.data?.id
    if (id) {
        clearTourPending(id)
    }
    setting.layout.showSetting = false
    stopOnboardingTour()
}

const onChange = (index: number): void => {
    positioned.value = false
    void goToStep(index)
}

onMounted(() => {
    document.body.classList.add('ga-tour-playing')
    void goToStep(0)
})

onBeforeUnmount(() => {
    document.body.classList.remove('ga-tour-playing')
    accountMenuForced.value = false
})
</script>

<template>
    <el-tour
        v-model="open"
        v-model:current="current"
        class="ga-onboarding-tour"
        :class="{ 'is-ready': positioned }"
        :z-index="2100"
        :scroll-into-view-options="{ block: 'nearest', inline: 'nearest' }"
        :target-area-clickable="false"
        @change="onChange"
        @close="finishTour"
        @finish="finishTour"
    >
        <el-tour-step
            :target="targetLogo"
            title="工作台"
            description="点击侧栏站点名称可随时回到工作台。"
            placement="right"
        />
        <el-tour-step
            :target="targetPrefEntry"
            title="个人偏好"
            description="点这里打开个人偏好，可调整菜单样式和其它显示选项。"
            placement="bottom"
        />
        <el-tour-step
            :target="targetLayout"
            title="菜单样式"
            description="左侧单层、左侧双层、顶侧双栏可随时切换，即时生效。"
            placement="left"
        />
        <el-tour-step
            :target="targetAccordion"
            title="左侧菜单手风琴"
            description="开启后同一时刻只展开一个子菜单目录。"
            placement="left"
        />
        <el-tour-step
            :target="targetCatalog"
            title="展示全部可发现菜单"
            description="开启后侧栏会显示标记为「可发现」但尚未授权的菜单，点击进入说明页。"
            placement="left"
        />
        <el-tour-step
            :target="targetAccount"
            title="头像菜单"
            description="点头像可打开账号菜单，进入个人中心、切换角色或退出登录。"
            placement="bottom"
        />
        <el-tour-step
            :target="targetRoles"
            title="切换角色"
            description="在头像菜单中切换当前使用的角色；未分配角色时该项不可用。"
            placement="left"
        />
    </el-tour>
</template>

<style>
/* 引导期间关掉抽屉 / 下拉位移动画，避免 Tour 量到半途坐标 */
body.ga-tour-playing .ga-pref-drawer.el-drawer,
body.ga-tour-playing .ga-pref-drawer .el-drawer,
body.ga-tour-playing .el-overlay.ga-pref-drawer {
    transition-duration: 0s !important;
}

body.ga-tour-playing .ga-account-dropdown {
    animation: none !important;
    transition: none !important;
}

/* 定位完成前不显示，避免遮罩从错误坐标跳到目标 */
.el-tour.ga-onboarding-tour:not(.is-ready) {
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
}
</style>
