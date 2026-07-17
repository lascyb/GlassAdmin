<script setup lang="ts">
import * as apis from '@/apis/test/a/b/e'
import { ElMessage } from 'element-plus'
import { onMounted, reactive, ref } from 'vue'

const props = defineProps<{ id?: number }>()
const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const form = reactive({
    id: undefined as number | undefined,
    name: '',
    age: 0,
})

const loading = ref(false)
const saving = ref(false)
const title = ref('新增E')

const resetForm = () => {
    Object.assign(form, {
        id: undefined,
        name: '',
        age: 0,
    })
}

const loadDetail = async () => {
    if (props.id) {
        title.value = '编辑E'
        loading.value = true
        try {
            const d = await apis.read(props.id)
            Object.assign(form, {
                id: d.id,
                name: d.name ?? '',
                age: d.age,
            })
        } finally {
            loading.value = false
        }
    } else {
        title.value = '新增E'
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
            name: form.name.trim(),
            age: form.age,
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
            <el-form :model="form" label-position="right" label-width="96px">
                <el-form-item label="名字">
                    <el-input v-model.trim="form.name" maxlength="255" show-word-limit clearable />
                </el-form-item>

                <el-form-item label="年龄">
                    <el-input-number v-model="form.age" :min="0" :max="99999" controls-position="right" class="!w-full" />
                </el-form-item>

            </el-form>
        </el-skeleton>
        <template #footer>
            <el-button @click="show = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
        </template>
    </el-dialog>
</template>
