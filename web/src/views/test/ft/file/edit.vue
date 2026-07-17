<script setup lang="ts">
import * as apis from '@/apis/test/ft/file'
import type { UploadRequestOptions } from 'element-plus'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { onMounted, reactive, ref } from 'vue'
import { postFormDataData } from '@/apis/http'
import { resolveStorageUrl } from '@/utils/storageUrl'

const props = defineProps<{ id?: number }>()
const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const form = reactive({
    id: undefined as number | undefined,
    attachment: '',
})

const loading = ref(false)
const saving = ref(false)
const title = ref('新增文件')

const resetForm = () => {
    Object.assign(form, {
        id: undefined,
        attachment: '',
    })
}

const loadDetail = async () => {
    if (props.id) {
        title.value = '编辑文件'
        loading.value = true
        try {
            const d = await apis.read(props.id)
            Object.assign(form, {
                id: d.id,
                attachment: d.attachment ?? '',
            })
        } finally {
            loading.value = false
        }
    } else {
        title.value = '新增文件'
        resetForm()
    }
}

onMounted(() => {
    void loadDetail()
})

const onUploadAttachment = async (options: UploadRequestOptions) => {
    const fd = new FormData()
    fd.append('file', options.file)
    try {
        const res = await postFormDataData<{ path: string }>(apis.UPLOAD_ATTACHMENT_ACTION, fd)
        form.attachment = res.path
        ElMessage.success('上传成功')
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '上传失败')
    }
}

const submit = async () => {
    saving.value = true
    try {
        const payload = {
            attachment: form.attachment,
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
                <el-form-item label="附件">
                    <div class="flex items-center gap-3">
                        <el-upload :show-file-list="false" accept="image/*" :http-request="onUploadAttachment">
                            <el-image
                                v-if="form.attachment"
                                :src="resolveStorageUrl(form.attachment)"
                                fit="cover"
                                class="h-20 w-20 rounded border border-[var(--el-border-color)]"
                            />
                            <el-button v-else :icon="Plus">上传附件</el-button>
                        </el-upload>
                        <el-button v-if="form.attachment" link type="danger" @click="form.attachment = ''">移除</el-button>
                    </div>
                </el-form-item>

            </el-form>
        </el-skeleton>
        <template #footer>
            <el-button @click="show = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
        </template>
    </el-dialog>
</template>
