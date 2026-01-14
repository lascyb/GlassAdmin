import {defineStore} from 'pinia'
import {reactive} from "vue";

type Header = {
    height: number
}

export const useLayoutHeader = defineStore('layout_header', () => {
    const header = reactive<Header>({
        height: 60,
    })
    return {
        header
    }
})
