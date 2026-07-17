<script setup lang="ts">
import type { FormRenderType } from '@/views/dev/curd/types'
import { useDevCurdField } from '@/pinia/dev/curdField'
import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import {
    FORM_CONTROL_GROUPS,
    FORM_CONTROL_NEED_ENUM,
    FORM_CONTROL_NEED_REMOTE,
    FORM_CONTROL_OPTIONS,
} from '../../constants/form-render'
import InlineOptionsEditor from '../InlineOptionsEditor.vue'
import RemoteConfigDialog from '../RemoteConfigDialog.vue'

const fieldStore = useDevCurdField()
const { selectedField: field, hasForm, formCfg, hasColumn } = storeToRefs(fieldStore)

const controlValueSet = new Set(FORM_CONTROL_OPTIONS.map((o) => o.value))
const enumNeedSet = new Set<string>(FORM_CONTROL_NEED_ENUM)
const remoteNeedSet = new Set<string>(FORM_CONTROL_NEED_REMOTE)

const enumVisible = ref(false)
const remoteVisible = ref(false)

/** formControlDisplay 仅当现有 render 落在 UI 选项内时展示 */
const formControlDisplay = computed((): string | undefined => {
    const render = formCfg.value?.render
    if (!render) return undefined
    return controlValueSet.has(render) ? render : undefined
})

/** onFormControlChange 写回 form.render（不联动其它层） */
const onFormControlChange = (v: string | undefined) => {
    if (!v || !controlValueSet.has(v)) return
    fieldStore.setFormRender(v as FormRenderType)
}

const showNumber = computed(() => formControlDisplay.value === 'number')

/** showFormEnumEdit 当前表单控件是否需要编辑枚举 */
const showFormEnumEdit = computed(
    () => !!formControlDisplay.value && enumNeedSet.has(formControlDisplay.value),
)

/** showFormRemoteEdit 当前表单控件是否需要编辑远程 */
const showFormRemoteEdit = computed(
    () => !!formControlDisplay.value && remoteNeedSet.has(formControlDisplay.value),
)
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
                <el-form  size="small">
                    <el-form-item label="表单控件" required>
                        <div class="flex flex-wrap items-center gap-2">
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
                            <el-button
                                v-if="showFormEnumEdit"
                                size="small"
                                type="primary"
                                link
                                @click="enumVisible = true"
                            >
                                编辑枚举
                            </el-button>
                            <el-button
                                v-if="showFormRemoteEdit"
                                size="small"
                                type="primary"
                                link
                                @click="remoteVisible = true"
                            >
                                编辑远程
                            </el-button>
                        </div>
                    </el-form-item>
                    <el-form-item label="必填">
                        <el-switch v-model="formCfg.required" />
                    </el-form-item>
                    <el-form-item v-if="hasColumn" label="最大长度">
                        <el-input-number
                            v-model="field.column.length"
                            :min="0"
                            controls-position="right"
                            class="!w-36"
                        />
                    </el-form-item>
                </el-form>
            </section>

            <section v-if="showNumber" class="space-y-2">
                <el-form  size="small">
                    <el-form-item label="最小值">
                        <el-input-number
                            v-model="formCfg.numberMin"
                            controls-position="right"
                            class="!w-36"
                        />
                    </el-form-item>
                    <el-form-item label="最大值">
                        <el-input-number
                            v-model="formCfg.numberMax"
                            controls-position="right"
                            class="!w-36"
                        />
                    </el-form-item>
                    <el-form-item label="步进">
                        <el-input-number
                            v-model="formCfg.numberStep"
                            controls-position="right"
                            class="!w-36"
                        />
                    </el-form-item>
                </el-form>
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
