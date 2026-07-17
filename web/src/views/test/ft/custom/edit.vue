<script setup lang="ts">
import * as apis from '@/apis/test/ft/custom'
import { ElMessage } from 'element-plus'
import { onMounted, reactive, ref } from 'vue'

const props = defineProps<{ id?: number }>()
const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const form = reactive({
    id: undefined as number | undefined,
    custom_val: '',
})

const loading = ref(false)
const saving = ref(false)
const title = ref('新增自定义')

const resetForm = () => {
    Object.assign(form, {
        id: undefined,
        custom_val: '',
    })
}

const loadDetail = async () => {
    if (props.id) {
        title.value = '编辑自定义'
        loading.value = true
        try {
            const d = await apis.read(props.id)
            Object.assign(form, {
                id: d.id,
                custom_val: d.custom_val,
            })
        } finally {
            loading.value = false
        }
    } else {
        title.value = '新增自定义'
        resetForm()
    }
}

onMounted(() => {
    void loadDetail()
})

const submit = async () => {
    saving.value = true
    try {
        const payload = {
            custom_val: form.custom_val,
        }
        if (form.id) {
            await apis.update(form.id, payload)
        } else {
            await apis.create(payload)
        }
        ElMessage.success('已保存')
        emit('saved')
        show.value = false
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '保存失败')
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <el-dialog
        v-model="show"
        draggable
        :title="title"
        width="640px"
        destroy-on-close
        lock-scroll
        top="6vh"
        :close-on-click-modal="false"
    >
        <el-skeleton :loading="loading" animated :rows="8">
            <el-form :model="form" label-position="right" label-width="96px" @submit.prevent="submit">
                <el-form-item label="自定义值">
                    <el-input v-model.trim="form.custom_val" maxlength="255" show-word-limit clearable placeholder="" />
                </el-form-item>

            </el-form>
        </el-skeleton>
        <template #footer>
            <el-button @click="show = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
        </template>
    </el-dialog>
</template>
