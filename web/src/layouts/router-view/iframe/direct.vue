<script setup lang="ts">
import IframeBlock from '@/layouts/router-view/iframe/index.vue'
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const iframeSrc = computed(() => String(route.meta.url ?? '').trim())
const loading = ref(true)

const onLoad = (): void => {
    loading.value = false
}
</script>

<template>
    <IframeBlock
        :key="iframeSrc || '__empty__'"
        :loading="loading && !!iframeSrc"
        :has-src="!!iframeSrc"
    >
        <iframe
            v-if="iframeSrc"
            :src="iframeSrc"
            frameborder="0"
            allow="fullscreen; unload *"
            @load="onLoad"
        />
    </IframeBlock>
</template>
