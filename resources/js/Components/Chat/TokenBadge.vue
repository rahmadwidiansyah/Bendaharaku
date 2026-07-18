<script setup>
/**
 * TokenBadge.vue
 *
 * Badge untuk menampilkan jumlah token yang digunakan AI.
 *
 * Usage:
 *   <TokenBadge :tokens="412" />
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
    <span
        v-if="label"
        class="text-2xs text-gray-600 tabular-nums"
        :title="`${tokens} token`"
    >{{ label }}</span>
</template>
