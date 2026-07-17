<script setup lang="ts">
import * as apis from '@/apis/test/ft/multi_select'
import { ElMessage } from 'element-plus'
import { onMounted, reactive, ref } from 'vue'
import { EXTRA_OPTIONS } from '@/views/test/ft/multi_select/constants'

const props = defineProps<{ id?: number }>()
const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const form = reactive({
    id: undefined as number | undefined,
    extra: [] as string[],
})

const loading = ref(false)
const saving = ref(false)
const title = ref('新增下拉多选')

const resetForm = () => {
    Object.assign(form, {
        id: undefined,
        extra: [] as string[],
    })
}

const loadDetail = async () => {
    if (props.id) {
        title.value = '编辑下拉多选'
        loading.value = true
        try {
            const d = await apis.read(props.id)
            Object.assign(form, {
                id: d.id,
                extra: d.extra ?? [],
            })
        } finally {
            loading.value = false
        }
    } else {
        title.value = '新增下拉多选'
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
            extra: form.extra,
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
                <el-form-item label="附加项">
                    <el-select v-model="form.extra" multiple collapse-tags clearable placeholder="请选择" class="w-full">
                        <el-option v-for="o in EXTRA_OPTIONS" :key="o.value" :value="o.value" :label="o.label" />
                    </el-select>
                </el-form-item>

            </el-form>
        </el-skeleton>
        <template #footer>
            <el-button @click="show = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
        </template>
    </el-dialog>
</template>
