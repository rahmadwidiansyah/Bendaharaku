<script setup>
import { computed } from 'vue'

const props = defineProps({
  model: { type: String, default: '' },
  provider: { type: String, default: '' },
})

const providerType = computed(() => {
  const m = props.model.toLowerCase()
  const p = props.provider.toLowerCase()

  if (m.includes('gemini') || p.includes('gemini')) return 'gemini'
  if (m.includes('gpt') || p.includes('openai')) return 'openai'
  if (m.includes('deepseek') || p.includes('deepseek')) return 'deepseek'
  if (p.includes('python')) return 'python'
  return 'default'
})

// Label diubah: Nama provider dihapus karena diganti oleh ikon SVG
// Hanya menyisakan versi spesifik dari AI-nya saja
const label = computed(() => {
  const m = props.model.toLowerCase()
  const p = props.provider.toLowerCase()
  if (!m && !p) return null

  if (m.includes('gemini-2.5-flash')) return '2.5 Flash'
  if (m.includes('gemini-2.5-pro')) return '2.5 Pro'
  if (m.includes('gemini-2.0-flash')) return '2.0 Flash'
  if (m.includes('gemini-1.5-flash')) return '1.5 Flash'
  if (m.includes('gemini-1.5-pro')) return '1.5 Pro'
  if (m.includes('gemini')) return 'Gemini'

  if (m.includes('gpt-4o-mini')) return '4o Mini'
  if (m.includes('gpt-4o')) return '4o'
  if (m.includes('gpt-4')) return 'GPT-4'

  if (m.includes('deepseek-r1')) return 'R1'
  if (m.includes('deepseek')) return 'DeepSeek'

  if (p.includes('python')) return 'Script'

  return props.model || props.provider
})

const colorClass = computed(() => {
  switch (providerType.value) {
    case 'gemini': return 'text-blue-400 bg-blue-500/8 border-blue-500/20'
    case 'openai': return 'text-emerald-400 bg-emerald-500/8 border-emerald-500/20'
    case 'deepseek': return 'text-cyan-400 bg-cyan-500/8 border-cyan-500/20'
    case 'python': return 'text-yellow-400 bg-yellow-500/8 border-yellow-500/20'
    default: return 'text-gray-400 bg-gray-500/8 border-gray-500/20'
  }
})
</script>

<template>
  <span v-if="label" :class="['inline-flex items-center gap-1.25 text-2xs font-medium pl-1.5 pr-2 py-0.5 rounded border', colorClass]">
    
    <!-- Gemini (Sparkle Icon) -->
    <svg v-if="providerType === 'gemini'" class="w-3 h-3 shrink-0" viewBox="0 0 24 24" fill="currentColor">
      <path d="M12 22C12 16.477 7.523 12 2 12C7.523 12 12 7.523 12 2C12 7.523 16.477 12 22 12C16.477 12 12 16.477 12 22Z"/>
    </svg>

    <!-- OpenAI (ChatGPT Logo) -->
    <svg v-else-if="providerType === 'openai'" class="w-3 h-3 shrink-0" viewBox="0 0 24 24" fill="currentColor">
      <path d="M22.28 9.82a8.82 8.82 0 0 0-.58-5.3 8.84 8.84 0 0 0-8-5.75c-1.3 0-2.54.3-3.66.82A8.83 8.83 0 0 0 1.9 4.88a8.85 8.85 0 0 0-.82 7.73 8.84 8.84 0 0 0 3.32 6.55 8.84 8.84 0 0 0 5.4 1.94 8.83 8.83 0 0 0 8.08-5.52c1.3.4 2.76.2 3.96-.54a8.85 8.85 0 0 0 4.14-5.22h-3.7zm-10.28-7.7c2.37 0 4.54 1.25 5.75 3.3l-5.75 3.33V2.12zm-4.7 1.76a6.83 6.83 0 0 1 7.82-1.35l-2.88 4.97-5.74-3.32a6.8 6.8 0 0 1 .8-3zm-4.66 8.12a6.83 6.83 0 0 1 1.94-7.56l2.87 4.98v6.64l-4.81-4.06zm1.36 5.82a6.83 6.83 0 0 1-1.36-7.83l5.75 3.32v6.64l-4.39-2.13zm13.12 3.33a6.83 6.83 0 0 1-7.82 1.35l2.88-4.97 5.74 3.32a6.8 6.8 0 0 1-.8 3zm4.66-8.12a6.83 6.83 0 0 1-1.94 7.56l-2.87-4.98V8.92l4.81 4.06zm-1.36-5.82a6.83 6.83 0 0 1 1.36 7.83l-5.75-3.32V4.57l4.39 2.13zM12 15.14a3.14 3.14 0 1 1 0-6.28 3.14 3.14 0 0 1 0 6.28z"/>
    </svg>

    <!-- DeepSeek (AI Node/Bot Icon) -->
    <svg v-else-if="providerType === 'deepseek'" class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 8V4H8"/>
      <rect width="16" height="12" x="4" y="8" rx="2"/>
      <path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/>
    </svg>

    <!-- Python (Python Logo) -->
    <svg v-else-if="providerType === 'python'" class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
      <path d="M12.05.51c-5.46 0-5.22 2.37-5.22 2.37l.03 2.45h5.27v.75H6.26S3.74 5.92 3.74 9.38c0 3.47 2.21 4.5 2.21 4.5l1.64.38v-2.2s-.05-3.08 3.12-3.08h3.36s2.9-.1 2.9-2.7V3.53S17.4.51 12.05.51zM9.54 2.1a1.05 1.05 0 1 1 0 2.1 1.05 1.05 0 0 1 0-2.1zM17.74 9.12s2.52.17 2.52 3.63c0 3.46-2.65 3.32-2.65 3.32l-2.48.04v-2.38h-4.3v2.85s-2.92.1-2.92-2.3v-1.46s-2.6-1.12-2.6-4.59h5.88v1.07s.11 2.72 2.92 2.72h3.45s2.32-.05 2.32-2.52v-2.8s.23-2.6-5.22-2.6l.03 2.32h5.13v.85h-.01zM14.46 20.3a1.05 1.05 0 1 1 0-2.1 1.05 1.05 0 0 1 0 2.1z"/>
    </svg>

    <span>{{ label }}</span>
  </span>
</template>
