<script setup>
/**
 * M3ResponseMeta — metadata respons AI di footer bubble bot
 * (latency, token, model/provider) — paritas ResponseMeta Default.
 */
import { computed } from 'vue'
import M3ModelBadge from './M3ModelBadge.vue'
import M3TokenBadge from './M3TokenBadge.vue'

const props = defineProps({
    metadata: { type: Object, default: () => ({}) },
    content:  { type: Array, default: () => [] },
})

const latencyLabel = computed(() => {
    const ms = props.metadata?.latency_ms
    if (!ms) return null
    return ms >= 1000 ? (ms / 1000).toFixed(2) + 's' : ms + 'ms'
})

const tokens = computed(() =>
    props.metadata?.total_tokens ??
    props.metadata?.tokens ??
    props.metadata?.usage?.total_tokens ??
    null
)

const model = computed(() => props.metadata?.model ?? '')
const provider = computed(() => props.metadata?.provider ?? '')

const hasAny = computed(() => latencyLabel.value || tokens.value !== null || model.value || provider.value)
</script>

<template>
    <div v-if="hasAny" class="m3-response-meta">
        <span v-if="latencyLabel" class="m3-response-meta__item">{{ latencyLabel }}</span>

        <span v-if="latencyLabel && tokens !== null" class="m3-response-meta__sep" aria-hidden="true">&bull;</span>

        <M3TokenBadge :tokens="tokens" />

        <span v-if="(latencyLabel || tokens !== null) && (model || provider)" class="m3-response-meta__sep" aria-hidden="true">&bull;</span>

        <M3ModelBadge :model="model" :provider="provider" />
    </div>
</template>

<style scoped>
.m3-response-meta {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1);
    flex-wrap: wrap;
    min-width: 0;
}

.m3-response-meta__item {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.m3-response-meta__sep {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-outline);
}
</style>
