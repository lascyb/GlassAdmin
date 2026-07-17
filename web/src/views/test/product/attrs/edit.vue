<script setup lang="ts">
import * as apis from '@/apis/test/product/attrs'
import { ElMessage } from 'element-plus'
import { onMounted, reactive, ref } from 'vue'

const props = defineProps<{ id?: string }>()
const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const form = reactive({
    id: undefined as string | undefined,
    key: '',
    value: '',
})

const loading = ref(false)
const saving = ref(false)
const title = ref('新增Attrs')

const resetForm = () => {
    Object.assign(form, {
        id: undefined,
        key: '',
        value: '',
    })
}

const loadDetail = async () => {
    if (props.id) {
        title.value = '编辑Attrs'
        loading.value = true
        try {
            const d = await apis.read(props.id)
            Object.assign(form, {
                id: d.key,
                key: d.key ?? '',
                value: d.value ?? '',
            })
        } finally {
            loading.value = false
        }
    } else {
        title.value = '新增Attrs'
        resetForm()
    }
}

onMounted(() => {
    void loadDetail()
})

const submit = async () => {
    if (!form.key.trim()) {
        ElMessage.warning('请输入Key')
        return
    }
    if (!form.value.trim()) {
        ElMessage.warning('请输入Value')
        return
    }
    saving.value = true
    try {
        const payload = {
            key: form.key.trim(),
            value: form.value.trim(),
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
                <el-form-item label="Key" required>
                    <el-input v-model.trim="form.key" maxlength="100" show-word-limit clearable placeholder="请输入Key" />
                </el-form-item>

                <el-form-item label="Value" required>
                    <el-input v-model.trim="form.value" maxlength="100" show-word-limit clearable placeholder="请输入Value" />
                </el-form-item>

            </el-form>
        </el-skeleton>
        <template #footer>
            <el-button @click="show = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
        </template>
    </el-dialog>
</template>
