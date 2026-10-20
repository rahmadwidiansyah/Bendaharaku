<script setup>
/**
 * M3SkeletonBlock — blok dasar skeleton M3.
 * Warna memakai surface-container-highest; animasi pulse dikelola
 * oleh M3SkeletonPage agar semua blok berdenyut sinkron.
 */
import { computed } from 'vue'

const props = defineProps({
    width: { type: String, default: '100%' },
    height: { type: String, default: 'var(--m3-space-3)' },
    radius: { type: String, default: 'var(--m3-radius-sm)' },
    circle: { type: Boolean, default: false },
})

const style = computed(() => ({
    width: props.width,
    height: props.height,
    borderRadius: props.circle ? 'var(--m3-radius-full)' : props.radius,
}))
</script>

<template>
    <span class="m3-skel-block" :style="style" aria-hidden="true" />
</template>

<style scoped>
.m3-skel-block {
    display: block;
    flex-shrink: 0;
    background: var(--m3-color-surface-container-highest);
}
</style>
