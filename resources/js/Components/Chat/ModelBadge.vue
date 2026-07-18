<script setup>
/**
 * ModelBadge.vue
 *
 * Badge kecil yang menampilkan nama model AI.
 * Warna otomatis berdasarkan provider.
 *
 * Usage:
 *   <ModelBadge model="gemini-1.5-flash" provider="gemini" />
 */

import { computed } from 'vue'

const props = defineProps({
    model:    { type: String, default: '' },
    provider: { type: String, default: '' },
})

const label = computed(() => {
    const m = props.model.toLowerCase()
    const p = props.provider.toLowerCase()
    if (!m && !p) return null

    if (m.includes('gemini-2.5-flash')) return 'Gemini 2.5 Flash'
    if (m.includes('gemini-2.5-pro'))   return 'Gemini 2.5 Pro'
    if (m.includes('gemini-2.0-flash')) return 'Gemini 2.0 Flash'
    if (m.includes('gemini-1.5-flash')) return 'Gemini Flash'
    if (m.includes('gemini-1.5-pro'))   return 'Gemini Pro'
    if (m.includes('gemini'))           return 'Gemini'
    if (m.includes('gpt-4o-mini'))      return 'GPT-4o Mini'
    if (m.includes('gpt-4o'))           return 'GPT-4o'
    if (m.includes('gpt-4'))            return 'GPT-4'
    if (m.includes('deepseek-r1'))      return 'DeepSeek R1'
    if (m.includes('deepseek'))         return 'DeepSeek'
    if (p.includes('python'))           return 'Python NLP'
    return props.model || props.provider
})

const colorClass = computed(() => {
    const m = props.model.toLowerCase()
    const p = props.provider.toLowerCase()
    if (m.includes('gemini') || p.includes('gemini'))     return 'text-blue-400 bg-blue-500/8 border-blue-500/20'
    if (m.includes('gpt') || p.includes('openai'))        return 'text-emerald-400 bg-emerald-500/8 border-emerald-500/20'
    if (m.includes('deepseek') || p.includes('deepseek')) return 'text-cyan-400 bg-cyan-500/8 border-cyan-500/20'
    if (p.includes('python'))                              return 'text-yellow-400 bg-yellow-500/8 border-yellow-500/20'
    return 'text-gray-400 bg-gray-500/8 border-gray-500/20'
})
</script>

<template>
    <span
        v-if="label"
        :class="['inline-flex items-center text-2xs font-medium px-1.5 py-0.5 rounded border', colorClass]"
    >{{ label }}</span>
</template>
