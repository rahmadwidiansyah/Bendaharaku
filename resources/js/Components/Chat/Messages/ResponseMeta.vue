<script setup>
import { computed } from 'vue'
import ModelBadge from '@/Components/Chat/ModelBadge.vue'
import TokenBadge from '@/Components/Chat/TokenBadge.vue'

const props = defineProps({
  metadata: { type: Object, default: () => ({}) },
  content: { type: Array, default: () => [] },
})

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

const hasAny = computed(() => latencyLabel.value || tokens.value !== null || model.value || provider.value)
</script>

<template>
  <div v-if="hasAny" class="flex items-center gap-1 flex-wrap min-w-0">
    <!-- Latency -->
    <span v-if="latencyLabel" class="text-2xs text-gray-600 tabular-nums">{{ latencyLabel }}</span>
    
    <!-- Titik pemisah jika ada token & latency -->
    <span v-if="latencyLabel && tokens !== null" class="text-2xs text-gray-700">&bull;</span>
    
    <!-- Token badge -->
    <TokenBadge :tokens="tokens" />
    
    <!-- Titik pemisah jika ada model/provider -->
    <span v-if="(latencyLabel || tokens !== null) && (model || provider)" class="text-2xs text-gray-700">&bull;</span>
    
    <!-- Model badge -->
    <ModelBadge :model="model" :provider="provider" />
  </div>
</template>
