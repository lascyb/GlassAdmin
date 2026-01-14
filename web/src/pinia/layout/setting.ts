import {defineStore} from 'pinia'
import {reactive} from "vue";

type Layout = {
    showSetting: boolean
    container: 'Default' | "Double"
}

export const useLayoutSetting = defineStore('layout_setting', () => {
    const layout = reactive<Layout>({
        showSetting: true,
        container: 'Default'
    })
    const showSetting = () => {
        layout.showSetting = true
    }
    return {
        layout,
        showSetting
    }
}, {
    persist: true
})
