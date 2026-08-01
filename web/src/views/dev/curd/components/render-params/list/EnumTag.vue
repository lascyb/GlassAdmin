<script setup lang="ts">
import { useDevCurdField } from '@/pinia/dev/curdField'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import { listRenderNeedsEnum } from '../../../fieldExt'
import ListWidthFields from './ListWidthFields.vue'

const emit = defineEmits<{ 'edit-enum': [] }>()

const { selectedField: field } = storeToRefs(useDevCurdField())

const showEnum = computed(() => listRenderNeedsEnum(field.value, field.value?.list.render))
</script>

<template>
    <div class="space-y-2">
        <ListWidthFields />
        <el-form v-if="showEnum" size="small">
            <el-form-item label="枚举">
                <el-button size="small" type="primary" link @click="emit('edit-enum')">编辑枚举</el-button>
            </el-form-item>
        </el-form>
    </div>
</template>
