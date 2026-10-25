<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import ModelBadge from '@/Components/Chat/ModelBadge.vue'
import TokenBadge from '@/Components/Chat/TokenBadge.vue'

const props = defineProps({
  metadata: { type: Object, default: () => ({}) },
  content: { type: Array, default: () => [] },
})

const { t } = useI18n()

// Format latency singkat
const latencyLabel = computed(() => {
  const ms = props.metadata?.latency_ms
  if (!ms) return null
  return ms >= 1000 ? (ms / 1000).toFixed(2) + 's' : ms + 'ms'
})

// Tangkap token dari berbagai kemungkinan nama key backend
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

const hasAny = computed(() => latencyLabel.value || tokens.value !== null || model.value || provider.value || isFallback.value)
</script>

<template>
  <div v-if="hasAny" class="flex items-center gap-1 flex-wrap min-w-0">
    <!-- Latency -->
    <span v-if="latencyLabel" class="text-2xs text-[var(--color-text-muted)] tabular-nums">{{ latencyLabel }}</span>
    
    <!-- Titik pemisah jika ada token & latency -->
    <span v-if="latencyLabel && tokens !== null" class="text-2xs text-gray-500">&bull;</span>
    
    <!-- Token badge -->
    <TokenBadge :tokens="tokens" />
    
    <!-- Titik pemisah jika ada model/provider -->
    <span v-if="(latencyLabel || tokens !== null) && (model || provider)" class="text-2xs text-gray-500">&bull;</span>
    
    <!-- Model badge -->
    <ModelBadge :model="model" :provider="provider" />

    <!-- Titik pemisah sebelum chip fallback -->
    <span v-if="isFallback && (latencyLabel || tokens !== null || model || provider)" class="text-2xs text-gray-500">&bull;</span>

    <!-- Chip fallback routing (title berisi detail rantai + alasan) -->
    <span
      v-if="isFallback"
      class="inline-flex items-center px-1.5 py-0.5 rounded-md border border-[var(--color-border-default)] bg-[var(--color-surface-muted)] text-2xs text-[var(--color-text-secondary)] whitespace-nowrap"
      :title="fallbackTitle"
    >{{ t('chat.meta_fallback') }}</span>
  </div>
</template>
