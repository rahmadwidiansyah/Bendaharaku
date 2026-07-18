<script setup>
/**
 * ResponseMeta.vue
 *
 * Menampilkan metadata respons AI di footer bubble:
 * latency · token · model
 *
 * Sekarang reuse ModelBadge + TokenBadge agar konsisten.
 */

import { computed }  from 'vue'
import ModelBadge    from '@/Components/Chat/ModelBadge.vue'
import TokenBadge    from '@/Components/Chat/TokenBadge.vue'

const props = defineProps({
    metadata: { type: Object, default: () => ({}) },
    content:  { type: Array,  default: () => [] },
})

// Format latency singkat: 1240ms → "1.24s", 450ms → "450ms"
const latencyLabel = computed(() => {
    const ms = props.metadata?.latency_ms
    if (!ms) return null
    return ms >= 1000 ? (ms / 1000).toFixed(2) + 's' : ms + 'ms'
})

// Sudah ada ModelBadge yang handle label — kirim raw nilai ke badge
const model    = computed(() => props.metadata?.model    ?? '')
const provider = computed(() => props.metadata?.provider ?? '')
const tokens   = computed(() => props.metadata?.total_tokens ?? null)

const hasAny = computed(() => latencyLabel.value || tokens.value || model.value || provider.value)
</script>

<template>
    <div v-if="hasAny" class="flex items-center gap-1 flex-wrap min-w-0">

        <!-- Latency -->
        <span v-if="latencyLabel" class="text-2xs text-gray-600 tabular-nums">{{ latencyLabel }}</span>

        <span v-if="latencyLabel && tokens" class="text-2xs text-gray-700">·</span>

        <!-- Token badge -->
        <TokenBadge :tokens="tokens" />

        <span v-if="(latencyLabel || tokens) && (model || provider)" class="text-2xs text-gray-700">·</span>

        <!-- Model badge -->
        <ModelBadge :model="model" :provider="provider" />

    </div>
</template>
