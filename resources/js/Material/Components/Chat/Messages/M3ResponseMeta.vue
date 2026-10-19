<script setup>
/**
 * M3ResponseMeta — metadata respons AI di footer bubble bot
 * (latency, token, model/provider) — paritas ResponseMeta Default.
 */
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import M3ModelBadge from './M3ModelBadge.vue'
import M3TokenBadge from './M3TokenBadge.vue'

const props = defineProps({
    metadata: { type: Object, default: () => ({}) },
    content:  { type: Array, default: () => [] },
})

const { t } = useI18n()

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

const routing = computed(() => props.metadata?.routing ?? null)

const isFallback = computed(() =>
    Boolean(routing.value?.fallback_from || routing.value?.final_status === 'local_fallback')
)

const fallbackTitle = computed(() => {
    const r = routing.value ?? {}
    const from = r.fallback_from || 'llm-pool'
    const to = r.fallback_to || [r.selected_provider ?? provider.value, r.selected_model ?? model.value].filter(Boolean).join('/') || '?'
    return `${from} → ${to}${r.fallback_reason ? ' — ' + r.fallback_reason : ''}`
})

const hasAny = computed(() =>
    latencyLabel.value || tokens.value !== null || model.value || provider.value || isFallback.value
)
</script>

<template>
    <div v-if="hasAny" class="m3-response-meta">
        <span v-if="latencyLabel" class="m3-response-meta__item">{{ latencyLabel }}</span>

        <span v-if="latencyLabel && tokens !== null" class="m3-response-meta__sep" aria-hidden="true">&bull;</span>

        <M3TokenBadge :tokens="tokens" />

        <span v-if="(latencyLabel || tokens !== null) && (model || provider)" class="m3-response-meta__sep" aria-hidden="true">&bull;</span>

        <M3ModelBadge :model="model" :provider="provider" />

        <span v-if="isFallback && (latencyLabel || tokens !== null || model || provider)" class="m3-response-meta__sep" aria-hidden="true">&bull;</span>

        <span v-if="isFallback" class="m3-response-meta__fallback" :title="fallbackTitle">{{ t('chat.meta_fallback') }}</span>
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

.m3-response-meta__fallback {
    display: inline-flex;
    align-items: center;
    padding: 0.125rem var(--m3-space-2);
    border-radius: var(--m3-radius-sm);
    border: 1px solid color-mix(in srgb, var(--m3-color-outline) 40%, transparent);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    white-space: nowrap;
}
</style>
