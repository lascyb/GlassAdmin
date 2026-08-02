import {defineStore} from "pinia";
import {reactive} from "vue";
import type {FORM_TYPE} from "@/views/dev/curd/components/render/registry.ts";

export  const useCurdField = defineStore("test_curd_field",()=>{
    const form = reactive<FORM_TYPE>({
        enabled: false,
        required: false,
    })
    return {
        form
    }
})