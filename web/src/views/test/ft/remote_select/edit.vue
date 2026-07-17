<script setup lang="ts">
import * as apis from '@/apis/test/ft/remote_select'
import GaSelectRemote from '@/components/ga-select-remote/index.vue'
import { ElMessage } from 'element-plus'
import { onMounted, reactive, ref } from 'vue'

const props = defineProps<{ id?: number }>()
const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const form = reactive({
    id: undefined as number | undefined,
    category_id: undefined as number | undefined,
})

const loading = ref(false)
const saving = ref(false)
const title = ref('新增远程下拉')
const categoryInit = ref<Array<{ id: number; name: string }> | undefined>()
const detailReady = ref(false)

const resetForm = () => {
    Object.assign(form, {
        id: undefined,
        category_id: undefined as number | undefined,
    })
    categoryInit.value = undefined
}

const loadDetail = async () => {
    detailReady.value = false
    if (props.id) {
        title.value = '编辑远程下拉'
        loading.value = true
        try {
            const d = await apis.read(props.id)
            Object.assign(form, {
                id: d.id,
                category_id: d.category_id ?? undefined,
            })
            categoryInit.value = d.category ? [d.category] : undefined
        } finally {
            loading.value = false
        }
    } else {
        title.value = '新增远程下拉'
        resetForm()
    }
    detailReady.value = true
}

onMounted(() => {
    void loadDetail()
})

const submit = async () => {
    saving.value = true
    try {
        const payload = {
            category_id: form.category_id ?? null,
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
                <el-form-item label="分类">
                    <ga-select-remote
                        v-if="detailReady"
                        :key="`rel-${form.id ?? 'new'}`"
                        v-model="form.category_id"
                        :remote-url="'test.ft.Text/list'"
                        :props="{ value: 'id', label: 'title' }"
                        :init-options="categoryInit"
                        clearable
                        placeholder="输入关键词搜索分类"
                        class="w-full"
                    />
                </el-form-item>

            </el-form>
        </el-skeleton>
        <template #footer>
            <el-button @click="show = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
        </template>
    </el-dialog>
</template>
