<script setup lang="ts">
<%importBlock%>
const props = defineProps<{ id?: <%pkTs%> }>()
const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const form = reactive({
    id: undefined as <%pkTs%> | undefined,
<%formLines%>
})

const loading = ref(false)
const saving = ref(false)
const title = ref('新增<%title%>')
<%initRefsBlock%><%detailReady%>
const resetForm = () => {
    Object.assign(form, {
        id: undefined,
<%resetLines%>
    })
<%initResetBlock%>}

const loadDetail = async () => {
<%detailReadySetFalse%>    if (props.id) {
        title.value = '编辑<%title%>'
        loading.value = true
        try {
            const d = await apis.read(props.id)
            Object.assign(form, {
                id: d.<%pk%>,
<%assignLines%>
            })
<%initAssignBlock%>        } finally {
            loading.value = false
        }
    } else {
        title.value = '新增<%title%>'
        resetForm()
    }
<%detailReadySetTrue%>}

onMounted(() => {
    void loadDetail()
})
<%uploadFns%>
const submit = async () => {
<%requiredChecks%>    saving.value = true
    try {
        const payload = {
<%payloadLines%>
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
<%formItems%>            </el-form>
        </el-skeleton>
        <template #footer>
            <el-button @click="show = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
        </template>
    </el-dialog>
</template>
