<script setup lang="ts">
import type { CrudFieldFormConfig, FormRenderType } from '@/crud/types'
import { useDevCurd } from '@/pinia/dev/curd'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import { FORM_RENDER_OPTIONS } from '../../helpers'

const store = useDevCurd()
const { selectedField: field } = storeToRefs(store)

/** formEnabled 是否进表单 */
const formEnabled = computed({
    get: () => field.value != null && field.value.form !== false && field.value.form != null,
    set: (v: boolean) => {
        if (!field.value) return
        if (v) {
            if (!field.value.form || field.value.form === false) {
                field.value.form = { render: 'input' }
            }
        } else {
            field.value.form = false
        }
    },
})

/** formCfg 表单配置（启用时） */
const formCfg = computed(() => {
    const f = field.value?.form
    if (!f || f === false) return null
    return f
})

/** ensureForm 保证 form 对象 */
const ensureForm = (): CrudFieldFormConfig | null => {
    if (!field.value || !formEnabled.value) return null
    if (!field.value.form || field.value.form === false) {
        field.value.form = { render: 'input' }
    }
    return field.value.form
}

/** onFormRenderChange 切换表单控件 */
const onFormRenderChange = (render: FormRenderType) => {
    const f = ensureForm()
    if (f) f.render = render
}
</script>

<template>
    <el-card v-if="field" shadow="never" class="!border">
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-medium">表单</span>
                <el-switch v-model="formEnabled" active-text="进表单" inactive-text="不进表单" />
            </div>
        </template>
        <el-form v-if="formCfg" label-width="80px" size="small">
            <el-form-item label="表单控件" required>
                <el-select
                    :model-value="formCfg.render"
                    class="!w-52 max-w-full"
                    @update:model-value="onFormRenderChange"
                >
                    <el-option
                        v-for="o in FORM_RENDER_OPTIONS"
                        :key="o.value"
                        :label="o.label"
                        :value="o.value"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="必填">
                <el-switch v-model="formCfg.required" />
            </el-form-item>
            <el-form-item label="占位符">
                <el-input v-model="formCfg.placeholder" class="!w-52 max-w-full" />
            </el-form-item>
            <el-form-item label="选项键">
                <el-input
                    v-model="formCfg.optionsKey"
                    class="!w-52 max-w-full"
                    placeholder="远程 API 或静态键"
                />
            </el-form-item>
            <el-form-item label="状态映射">
                <el-input v-model="formCfg.statusMapKey" class="!w-52 max-w-full" />
            </el-form-item>
            <el-form-item label="最小值">
                <el-input-number v-model="formCfg.numberMin" controls-position="right" class="!w-36" />
            </el-form-item>
            <el-form-item label="最大值">
                <el-input-number v-model="formCfg.numberMax" controls-position="right" class="!w-36" />
            </el-form-item>
            <el-form-item label="步进">
                <el-input-number v-model="formCfg.numberStep" controls-position="right" class="!w-36" />
            </el-form-item>
            <el-form-item label="最大长度">
                <el-input-number
                    v-model="formCfg.maxLength"
                    :min="0"
                    controls-position="right"
                    class="!w-36"
                />
            </el-form-item>
        </el-form>
    </el-card>
</template>
