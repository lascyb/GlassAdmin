<script setup lang="ts">
import * as apis from '@/apis/test/ft/remote_multi_select'
import GaSelectRemote from '@/components/ga-select-remote/index.vue'
import { ElMessage } from 'element-plus'
import { onMounted, reactive, ref } from 'vue'

const props = defineProps<{ id?: number }>()
const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const form = reactive({
    id: undefined as number | undefined,
    tag_ids: [] as number[],
})

const loading = ref(false)
const saving = ref(false)
const title = ref('新增远程多选')
const tagsInit = ref<Array<{ id: number; name: string }> | undefined>()
const detailReady = ref(false)

const resetForm = () => {
    Object.assign(form, {
        id: undefined,
        tag_ids: [] as number[],
    })
    tagsInit.value = undefined
}

const loadDetail = async () => {
    detailReady.value = false
    if (props.id) {
        title.value = '编辑远程多选'
        loading.value = true
        try {
            const d = await apis.read(props.id)
            Object.assign(form, {
                id: d.id,
                tag_ids: d.tag_ids ?? d.tags?.map((t) => t.id) ?? [],
            })
            tagsInit.value = d.tags?.length ? d.tags : undefined
        } finally {
            loading.value = false
        }
    } else {
        title.value = '新增远程多选'
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
            tag_ids: form.tag_ids,
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
                <el-form-item label="标签">
                    <ga-select-remote
                        v-if="detailReady"
                        :key="`tag-${form.id ?? 'new'}`"
                        v-model="form.tag_ids"
                        :remote-url="'test.ft.Text/list'"
                        :props="{ value: 'id', label: 'title' }"
                        :init-options="tagsInit"
                        multiple
                        collapse-tags
                        collapse-tags-tooltip
                        clearable
                        placeholder="输入关键词搜索标签"
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
