<script setup lang="ts">
import * as apis from '@/apis/demo/item'
import GaSelectRemote from '@/components/ga-select-remote/index.vue'
import type { UploadRequestOptions } from 'element-plus'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { onMounted, reactive, ref } from 'vue'
import { postFormDataData } from '@/apis/http'
import { resolveStorageUrl } from '@/utils/storageUrl'
import { STATUS_MAP } from '@/views/demo/item/constants'

const props = defineProps<{ id?: number }>()
const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const form = reactive({
    id: undefined as number | undefined,
    title: '',
    code: '',
    category_id: undefined as number | undefined,
    quantity: 0,
    is_top: 0,
    status: 1,
    cover: '',
    publish_time: null as number | null,
    remark: '',
    sort: 0,
})

const loading = ref(false)
const saving = ref(false)
const title = ref('新增演示条目')
const categoryInit = ref<Array<{ id: number; name: string }> | undefined>()
const detailReady = ref(false)

const resetForm = () => {
    Object.assign(form, {
        id: undefined,
        title: '',
        code: '',
        category_id: undefined as number | undefined,
        quantity: 0,
        is_top: 0,
        status: 1,
        cover: '',
        publish_time: null as number | null,
        remark: '',
        sort: 0,
    })
    categoryInit.value = undefined
}

const loadDetail = async () => {
    detailReady.value = false
    if (props.id) {
        title.value = '编辑演示条目'
        loading.value = true
        try {
            const d = await apis.read(props.id)
            Object.assign(form, {
                id: d.id,
                title: d.title ?? '',
                code: d.code ?? '',
                category_id: d.category_id ?? undefined,
                quantity: d.quantity,
                is_top: d.is_top,
                status: d.status,
                cover: d.cover ?? '',
                publish_time: d.publish_time ?? null,
                remark: d.remark ?? '',
                sort: d.sort,
            })
            categoryInit.value = d.category ? [d.category] : undefined
        } finally {
            loading.value = false
        }
    } else {
        title.value = '新增演示条目'
        resetForm()
    }
    detailReady.value = true
}

onMounted(() => {
    void loadDetail()
})

const onUploadCover = async (options: UploadRequestOptions) => {
    const fd = new FormData()
    fd.append('file', options.file)
    try {
        const res = await postFormDataData<{ path: string }>(apis.UPLOAD_COVER_ACTION, fd)
        form.cover = res.path
        ElMessage.success('上传成功')
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '上传失败')
    }
}

const submit = async () => {
    if (!form.title.trim()) {
        ElMessage.warning('请输入标题')
        return
    }
    if (!form.code.trim()) {
        ElMessage.warning('请输入编码')
        return
    }
    saving.value = true
    try {
        const payload = {
            title: form.title.trim(),
            code: form.code.trim(),
            category_id: form.category_id ?? null,
            quantity: form.quantity,
            is_top: form.is_top,
            status: form.status,
            cover: form.cover,
            publish_time: form.publish_time,
            remark: form.remark.trim(),
            sort: form.sort,
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
                <el-form-item label="标题" required>
                    <el-input v-model.trim="form.title" maxlength="100" show-word-limit clearable />
                </el-form-item>

                <el-form-item label="编码" required>
                    <el-input v-model.trim="form.code" maxlength="50" show-word-limit clearable />
                </el-form-item>

                <el-form-item label="分类">
                    <ga-select-remote
                        v-if="detailReady"
                        :key="`rel-${form.id ?? 'new'}`"
                        v-model="form.category_id"
                        :remote-url="'example.Category/list'"
                        :props="{ value: 'id', label: 'name' }"
                        :init-options="categoryInit"
                        clearable
                        placeholder="输入关键词搜索分类"
                        class="w-full"
                    />
                </el-form-item>

                <el-form-item label="数量">
                    <el-input-number v-model="form.quantity" :min="0" :max="99999" controls-position="right" class="!w-full" />
                </el-form-item>

                <el-form-item label="置顶">
                    <el-switch v-model="form.is_top" :active-value="1" :inactive-value="0" />
                </el-form-item>

                <el-form-item label="状态">
                    <el-radio-group v-model="form.status">
                        <el-radio v-for="(item, value) in STATUS_MAP" :key="value" :value="Number(value)">
                            {{ item.label }}
                        </el-radio>
                    </el-radio-group>
                </el-form-item>

                <el-form-item label="封面">
                    <div class="flex items-center gap-3">
                        <el-upload :show-file-list="false" accept="image/*" :http-request="onUploadCover">
                            <el-image
                                v-if="form.cover"
                                :src="resolveStorageUrl(form.cover)"
                                fit="cover"
                                class="h-20 w-20 rounded border border-[var(--el-border-color)]"
                            />
                            <el-button v-else :icon="Plus">上传封面</el-button>
                        </el-upload>
                        <el-button v-if="form.cover" link type="danger" @click="form.cover = ''">移除</el-button>
                    </div>
                </el-form-item>

                <el-form-item label="发布时间">
                    <el-date-picker
                        v-model="form.publish_time"
                        type="datetime"
                        value-format="X"
                        placeholder="选择日期时间"
                        class="!w-full"
                    />
                </el-form-item>

                <el-form-item label="备注">
                    <el-input v-model.trim="form.remark" type="textarea" :rows="3" maxlength="500" show-word-limit />
                </el-form-item>

                <el-form-item label="排序">
                    <el-input-number v-model="form.sort" :min="0" :max="99999" controls-position="right" class="!w-full" />
                </el-form-item>

            </el-form>
        </el-skeleton>
        <template #footer>
            <el-button @click="show = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
        </template>
    </el-dialog>
</template>
