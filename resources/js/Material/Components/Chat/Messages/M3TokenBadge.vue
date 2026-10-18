<script setup>
/**
 * M3TokenBadge — jumlah token AI yang dipakai (paritas TokenBadge Default).
 */
import { computed } from 'vue'

const props = defineProps({
    tokens: { type: Number, default: null },
})

const label = computed(() => {
    if (!props.tokens) return null
    return props.tokens >= 1000
        ? (props.tokens / 1000).toFixed(1) + 'k tk'
        : props.tokens + ' tk'
})
</script>

<template>
    <span v-if="label" class="m3-token-badge" :title="`${tokens} token`">{{ label }}</span>
</template>

<style scoped>
.m3-token-badge {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
</style>
