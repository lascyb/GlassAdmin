<script setup lang="ts">
import type { SystemConfigRow } from '@/apis/system/config'
import * as systemApi from '@/apis/system/config'
import { ElMessage } from 'element-plus'
import { onMounted, reactive, ref, computed } from 'vue'

const props = defineProps<{
    id?: number
    groups?: string[]
}>()

const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const loading = ref(false)
const saving = ref(false)
const title = ref('新增配置')

const typeLabels: Record<string, string> = {
    input: '单行文本',
    textarea: '多行文本',
    select: '下拉单选',
    multi_select: '下拉多选',
    switch: '开关',
    number: '数值',
    color: '颜色选择',
}

const isBuiltin = computed(() => form.status === 2 && !!props.id)

const form = reactive<{
    key: string
    value: string
    group: string
    name: string
    description: string
    type: SystemConfigRow['type']
    options: string
    sort: number
    status: number
}>({
    key: '',
    value: '',
    group: '',
    name: '',
    description: '',
    type: 'input',
    options: '',
    sort: 0,
    status: 1,
})

const loadDetail = async () => {
    if (!props.id) {
        title.value = '新增配置'
        Object.assign(form, {
            key: '', value: '', group: '', name: '', description: '',
            type: 'input', options: '', sort: 0, status: 1,
        })
        return
    }
    title.value = '编辑配置'
    loading.value = true
    try {
        const d = await systemApi.fetchRead(props.id)
        Object.assign(form, {
            key: d.key,
            value: d.value,
            group: d.group,
            name: d.name,
            description: d.description ?? '',
            type: d.type,
            options: d.options ?? '',
            sort: d.sort,
            status: d.status,
        })
    } finally {
        loading.value = false
    }
}

onMounted(loadDetail)

const submit = async () => {
    if (isBuiltin.value) {
        saving.value = true
        try {
            await systemApi.updateConfig(props.id!, {
                value: form.value,
                description: form.description.trim() || undefined,
                sort: form.sort,
            })
            ElMessage.success('已保存')
            emit('saved')
            show.value = false
        } catch (e: unknown) {
            ElMessage.error(e instanceof Error ? e.message : '保存失败')
        } finally {
            saving.value = false
        }
        return
    }

    if (!form.name.trim()) {
        ElMessage.warning('请输入配置名称')
        return
    }
    if (!form.group.trim()) {
        ElMessage.warning('请输入配置分组')
        return
    }
    if (!form.key.trim() && !props.id) {
        ElMessage.warning('请输入配置键名')
        return
    }
    saving.value = true
    try {
        const payload = {
            key: form.key.trim(),
            value: form.value,
            group: form.group.trim(),
            name: form.name.trim(),
            description: form.description.trim() || undefined,
            type: form.type,
            options: form.options.trim() || undefined,
            sort: form.sort,
            status: form.status,
        }
        if (props.id) {
            await systemApi.updateConfig(props.id, payload)
        } else {
            await systemApi.saveConfig(payload)
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
    <el-dialog v-model="show" :title="title" draggable destroy-on-close lock-scroll top="5vh" width="580px">
        <el-skeleton :loading="loading" animated :rows="6">
            <el-form :model="form" label-position="right" label-width="96px">
              <el-form-item label="分组" required>
                <el-select v-model="form.group" class="w-full" placeholder="请选择或输入分组" filterable allow-create default-first-option :disabled="isBuiltin">
                    <el-option v-for="g in (props.groups || [])" :key="g" :label="g" :value="g" />
                </el-select>
              </el-form-item>
              <el-form-item label="键名" required>
                    <el-input v-model.trim="form.key" placeholder="唯一键名，如 site_title" :disabled="!!id || isBuiltin" maxlength="100" show-word-limit clearable />
                </el-form-item>
                <el-form-item label="名称" required>
                    <el-input v-model.trim="form.name" placeholder="中文展示名称" :disabled="isBuiltin" maxlength="100" show-word-limit clearable />
                </el-form-item>

                <el-form-item label="说明">
                    <el-input v-model.trim="form.description" type="textarea" :rows="2" placeholder="配置项说明" maxlength="255" show-word-limit />
                </el-form-item>
                <el-form-item label="类型" required>
                    <el-select v-model="form.type" class="w-full" :disabled="isBuiltin">
                        <el-option v-for="(label, value) in typeLabels" :key="value" :label="label" :value="value" />
                    </el-select>
                </el-form-item>
                <el-form-item v-if="(form.type === 'select' || form.type === 'multi_select') && !isBuiltin" label="选项">
                    <el-input v-model="form.options" type="textarea" :rows="2" placeholder='JSON 数组，如 [&quot;选项A&quot;,&quot;选项B&quot;]' />
                </el-form-item>
                <el-form-item label="默认值">
                    <el-input v-if="form.type === 'input'" v-model="form.value" placeholder="请输入" clearable />
                    <el-input v-else-if="form.type === 'textarea'" v-model="form.value" type="textarea" :rows="3" placeholder="请输入" />
                    <el-input v-else-if="form.type === 'number'" v-model="form.value" type="number" placeholder="请输入数值" />
                    <el-color-picker v-else-if="form.type === 'color'" v-model="form.value" />
                    <el-switch v-else-if="form.type === 'switch'" v-model="form.value" :active-value="'1'" :inactive-value="'0'" />
                    <el-select v-else-if="form.type === 'select'" v-model="form.value" placeholder="请选择" clearable>
                        <el-option v-for="opt in (form.options ? JSON.parse(form.options) : [])" :key="opt" :label="opt" :value="opt" />
                    </el-select>
                    <el-select v-else-if="form.type === 'multi_select'" v-model="form.value" multiple placeholder="请选择">
                        <el-option v-for="opt in (form.options ? JSON.parse(form.options) : [])" :key="opt" :label="opt" :value="opt" />
                    </el-select>
                    <el-input v-else v-model="form.value" placeholder="请输入" clearable />
                </el-form-item>
                <el-form-item label="排序">
                    <el-input-number v-model="form.sort" :min="0" />
                </el-form-item>
                <el-form-item label="状态">
                    <template v-if="isBuiltin">
                        <el-tag type="warning">系统内置</el-tag>
                    </template>
                    <el-radio-group v-else v-model="form.status">
                        <el-radio :value="1">启用</el-radio>
                        <el-radio :value="0">禁用</el-radio>
                    </el-radio-group>
                </el-form-item>
            </el-form>
        </el-skeleton>
        <template #footer>
            <el-button @click="show = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
        </template>
    </el-dialog>
</template>
