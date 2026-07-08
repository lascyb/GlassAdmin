<script setup lang="ts">
import * as dataApi from '@/apis/example/data'
import { postFormDataData } from '@/apis/http'
import { DATA_LEVEL_MAP, DATA_STATUS_MAP, EXTRA_OPTIONS } from '@/views/example/data/constants'
import { Plus } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import type { UploadRequestOptions } from 'element-plus'
import { onMounted, reactive, ref } from 'vue'
import { resolveStorageUrl } from '@/utils/storageUrl'

const props = defineProps<{
    id?: number
}>()

const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const form = reactive({
    id: undefined as number | undefined,
    title: '',
    code: '',
    category_id: undefined as number | undefined,
    tag_ids: [] as number[],
    content: '',
    amount: 0,
    quantity: 0,
    is_top: 0,
    status: 1,
    level: 1,
    color: '#409EFF',
    cover: '',
    publish_time: null as number | null,
    expire_date: '',
    extra: [] as string[],
    remark: '',
    sort: 0,
})

const loading = ref(false)
const saving = ref(false)
const title = ref('新增示例数据')
const categories = ref<Array<{ id: number; name: string }>>([])
const tags = ref<Array<{ id: number; name: string }>>([])

const resetForm = () => {
    Object.assign(form, {
        id: undefined,
        title: '',
        code: '',
        category_id: undefined,
        tag_ids: [],
        content: '',
        amount: 0,
        quantity: 0,
        is_top: 0,
        status: 1,
        level: 1,
        color: '#409EFF',
        cover: '',
        publish_time: null,
        expire_date: '',
        extra: [],
        remark: '',
        sort: 0,
    })
}

const loadOptions = async () => {
    const res = await dataApi.fetchOptions()
    categories.value = res.categories
    tags.value = res.tags
}

const loadDetail = async () => {
    if (props.id) {
        title.value = '编辑示例数据'
        loading.value = true
        try {
            const d = await dataApi.fetchRead(props.id)
            Object.assign(form, {
                id: d.id,
                title: d.title,
                code: d.code,
                category_id: d.category_id ?? undefined,
                tag_ids: d.tag_ids ?? d.tags?.map((t) => t.id) ?? [],
                content: d.content ?? '',
                amount: d.amount,
                quantity: d.quantity,
                is_top: d.is_top,
                status: d.status,
                level: d.level,
                color: d.color ?? '#409EFF',
                cover: d.cover ?? '',
                publish_time: d.publish_time ?? null,
                expire_date: d.expire_date ?? '',
                extra: d.extra ?? [],
                remark: d.remark ?? '',
                sort: d.sort,
            })
        } finally {
            loading.value = false
        }
    } else {
        title.value = '新增示例数据'
        resetForm()
    }
}

onMounted(async () => {
    await loadOptions()
    await loadDetail()
})

const onUploadCover = async (options: UploadRequestOptions) => {
    const fd = new FormData()
    fd.append('file', options.file)
    try {
        const res = await postFormDataData<{ path: string }>(dataApi.UPLOAD_COVER_ACTION, fd)
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
            tag_ids: form.tag_ids,
            content: form.content.trim(),
            amount: form.amount,
            quantity: form.quantity,
            is_top: form.is_top,
            status: form.status,
            level: form.level,
            color: form.color,
            cover: form.cover,
            publish_time: form.publish_time,
            expire_date: form.expire_date || undefined,
            extra: form.extra,
            remark: form.remark.trim(),
            sort: form.sort,
        }
        if (form.id) {
            await dataApi.updateData(form.id, payload)
        } else {
            await dataApi.saveData(payload)
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
                <!-- input -->
                <el-form-item label="标题" required>
                    <el-input v-model.trim="form.title" maxlength="100" show-word-limit clearable />
                </el-form-item>
                <el-form-item label="编码" required>
                    <el-input v-model.trim="form.code" maxlength="50" show-word-limit clearable />
                </el-form-item>

                <!-- select / belongs_to -->
                <el-form-item label="分类">
                    <el-select v-model="form.category_id" clearable placeholder="请选择分类" class="w-full">
                        <el-option v-for="c in categories" :key="c.id" :value="c.id" :label="c.name" />
                    </el-select>
                </el-form-item>

                <!-- multi_select / belongs_to_many -->
                <el-form-item label="标签">
                    <el-select
                        v-model="form.tag_ids"
                        multiple
                        collapse-tags
                        collapse-tags-tooltip
                        clearable
                        placeholder="请选择标签"
                        class="w-full"
                    >
                        <el-option v-for="t in tags" :key="t.id" :value="t.id" :label="t.name" />
                    </el-select>
                </el-form-item>

                <!-- textarea -->
                <el-form-item label="内容">
                    <el-input v-model.trim="form.content" type="textarea" :rows="3" maxlength="2000" show-word-limit />
                </el-form-item>

                <!-- number / decimal -->
                <el-form-item label="金额">
                    <el-input-number v-model="form.amount" :min="0" :step="0.01" :precision="2" controls-position="right" class="!w-full" />
                </el-form-item>
                <el-form-item label="数量">
                    <el-input-number v-model="form.quantity" :min="0" controls-position="right" class="!w-full" />
                </el-form-item>

                <!-- switch / bool -->
                <el-form-item label="置顶">
                    <el-switch v-model="form.is_top" :active-value="1" :inactive-value="0" />
                </el-form-item>

                <!-- radio / enum -->
                <el-form-item label="状态">
                    <el-radio-group v-model="form.status">
                        <el-radio v-for="(item, value) in DATA_STATUS_MAP" :key="value" :value="Number(value)">
                            {{ item.label }}
                        </el-radio>
                    </el-radio-group>
                </el-form-item>

                <!-- select / enum -->
                <el-form-item label="等级">
                    <el-select v-model="form.level" class="w-full">
                        <el-option
                            v-for="(item, value) in DATA_LEVEL_MAP"
                            :key="value"
                            :value="Number(value)"
                            :label="item.label"
                        />
                    </el-select>
                </el-form-item>

                <!-- color -->
                <el-form-item label="颜色">
                    <el-color-picker v-model="form.color" />
                </el-form-item>

                <!-- image -->
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

                <!-- datetime -->
                <el-form-item label="发布时间">
                    <el-date-picker
                        v-model="form.publish_time"
                        type="datetime"
                        value-format="X"
                        placeholder="选择日期时间"
                        class="!w-full"
                    />
                </el-form-item>

                <!-- date -->
                <el-form-item label="过期日期">
                    <el-date-picker
                        v-model="form.expire_date"
                        type="date"
                        value-format="YYYY-MM-DD"
                        placeholder="选择日期"
                        class="!w-full"
                    />
                </el-form-item>

                <!-- json / multi_select 静态选项 -->
                <el-form-item label="扩展项">
                    <el-select v-model="form.extra" multiple collapse-tags clearable placeholder="请选择" class="w-full">
                        <el-option v-for="o in EXTRA_OPTIONS" :key="o.value" :value="o.value" :label="o.label" />
                    </el-select>
                </el-form-item>

                <el-form-item label="备注">
                    <el-input v-model.trim="form.remark" type="textarea" :rows="2" maxlength="500" show-word-limit />
                </el-form-item>

                <el-form-item label="排序">
                    <el-input-number v-model="form.sort" :min="0" controls-position="right" class="!w-full" />
                </el-form-item>
            </el-form>
        </el-skeleton>
        <template #footer>
            <el-button @click="show = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
        </template>
    </el-dialog>
</template>
