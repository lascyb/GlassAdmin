<script setup lang="ts">
import * as apis from '@/apis/test/z/probe'
import { ElMessage } from 'element-plus'
import { onMounted, reactive, ref } from 'vue'

const props = defineProps<{ id?: number }>()
const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const form = reactive({
    id: undefined as number | undefined,
    title: '',
    new_col: '',
})

const loading = ref(false)
const saving = ref(false)
const title = ref('新增SchemaProbe')

const resetForm = () => {
    Object.assign(form, {
        id: undefined,
        title: '',
        new_col: '',
    })
}

const loadDetail = async () => {
    if (props.id) {
        title.value = '编辑SchemaProbe'
        loading.value = true
        try {
            const d = await apis.read(props.id)
            Object.assign(form, {
                id: d.id,
                title: d.title ?? '',
                new_col: d.new_col ?? '',
            })
        } finally {
            loading.value = false
        }
    } else {
        title.value = '新增SchemaProbe'
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
            title: form.title.trim(),
            new_col: form.new_col.trim(),
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
                <el-form-item label="标题">
                    <el-input v-model.trim="form.title" maxlength="255" show-word-limit clearable placeholder="请输入标题" />
                </el-form-item>

                <el-form-item label="新列">
                    <el-input v-model.trim="form.new_col" maxlength="80" show-word-limit clearable placeholder="请输入新列" />
                </el-form-item>

            </el-form>
        </el-skeleton>
        <template #footer>
            <el-button @click="show = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
        </template>
    </el-dialog>
</template>
