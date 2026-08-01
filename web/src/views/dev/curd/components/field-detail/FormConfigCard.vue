<script setup lang="ts">
import type { FormRenderType } from '@/views/dev/curd/types'
import { useDevCurdField } from '@/pinia/dev/curdField'
import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import { FORM_CONTROL_GROUPS, FORM_CONTROL_OPTIONS } from '../../constants/form-render'
import InlineOptionsEditor from '../InlineOptionsEditor.vue'
import RemoteConfigDialog from '../RemoteConfigDialog.vue'
import { formParamCanonical, resolveFormParamComponent } from '../render-params/registry'

const fieldStore = useDevCurdField()
const { selectedField: field, hasForm, formCfg } = storeToRefs(fieldStore)

const controlValueSet = new Set(FORM_CONTROL_OPTIONS.map((o) => o.value))

const enumVisible = ref(false)
const remoteVisible = ref(false)

/** formControlDisplay 仅当现有 render 落在 UI 选项内时展示 */
const formControlDisplay = computed((): string | undefined => {
    const render = formCfg.value?.render
    if (!render) return undefined
    return controlValueSet.has(render) ? render : undefined
})

/** formParamKey 入参组件规范键（用于 :key 刷新） */
const formParamKey = computed(() =>
    formControlDisplay.value ? formParamCanonical(formControlDisplay.value) : '_empty',
)

/** formParamComp 当前表单入参组件 */
const formParamComp = computed(() => resolveFormParamComponent(formControlDisplay.value))

/** onFormControlChange 写回 form.render（不联动其它层） */
const onFormControlChange = (v: string | undefined) => {
    if (!v || !controlValueSet.has(v)) return
    fieldStore.setFormRender(v as FormRenderType)
}
</script>

<template>
    <el-card v-if="field" shadow="never">
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-medium">表单</span>
                <el-switch v-model="hasForm" active-text="进表单" inactive-text="不进表单" />
            </div>
        </template>

        <div v-if="formCfg" class="space-y-4">
            <section class="space-y-2">
                <el-form size="small">
                    <el-form-item label="表单控件" required>
                        <el-select
                            :model-value="formControlDisplay"
                            class="!w-52 max-w-full"
                            clearable
                            placeholder="表单控件"
                            popper-class="field-type-select-popper"
                            @update:model-value="onFormControlChange"
                        >
                            <el-option-group
                                v-for="g in FORM_CONTROL_GROUPS"
                                :key="g.label"
                                :label="g.label"
                            >
                                <el-option
                                    v-for="o in g.options"
                                    :key="o.value"
                                    :label="o.label"
                                    :value="o.value"
                                />
                            </el-option-group>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="必填">
                        <el-switch v-model="formCfg.required" />
                    </el-form-item>
                </el-form>
            </section>

            <section>
                <component
                    :is="formParamComp"
                    :key="formParamKey"
                    @edit-enum="enumVisible = true"
                    @edit-remote="remoteVisible = true"
                />
            </section>
        </div>

        <el-dialog
            v-model="enumVisible"
            title="编辑枚举选项"
            width="560px"
            append-to-body
            destroy-on-close
        >
            <InlineOptionsEditor />
        </el-dialog>

        <RemoteConfigDialog v-model="remoteVisible" />
    </el-card>
</template>
