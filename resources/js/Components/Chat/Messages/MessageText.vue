<script setup>
/**
 * MessageText.vue
 *
 * Teks dari AI — dirender sebagai Markdown.
 * Jika teks sangat panjang, ditawarkan tombol "Lihat Selengkapnya" (Collapsible).
 */
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import MarkdownRenderer from './MarkdownRenderer.vue'

const { t } = useI18n()

const props = defineProps({
    component: { type: Object, required: true },
})

const isExpanded = ref(false)
const threshold = 600 // limit karakter sebelum memicu collapsible

const isLongText = computed(() => {
    const text = props.component.text ?? ''
    return text.length > threshold
})

function toggleExpand() {
    isExpanded.value = !isExpanded.value
}
</script>

<template>
    <div class="relative group/text w-full">
        <div 
            :class="[
                'transition-all duration-300 ease-in-out overflow-hidden',
                isLongText && !isExpanded ? 'max-h-[220px] pb-8' : 'max-h-[5000px]'
            ]"
        >
            <MarkdownRenderer
                :content="component.text ?? ''"
                :class="component.bold ? '[&_strong]:text-[var(--color-text-primary)] [&_b]:text-[var(--color-text-primary)] font-semibold' : ''"
            />
            
            <!-- Fade-out overlay when collapsed -->
            <div 
                v-if="isLongText && !isExpanded"
                class="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-gray-800 via-gray-800/80 to-transparent pointer-events-none"
            />
        </div>

        <!-- Toggle Button -->
        <div v-if="isLongText" class="mt-2 flex justify-center">
            <button 
                type="button" 
                @click="toggleExpand"
                class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-[var(--color-brand)]/10 border border-purple-500/20 text-xs font-semibold text-purple-300 hover:text-[var(--color-text-primary)] hover:bg-[var(--color-brand)]/25 hover:border-purple-500/40 transition-all cursor-pointer active:scale-95 shadow-sm"
            >
                <span>{{ isExpanded ? t('chat.collapse') : t('chat.showMore') }}</span>
                <!-- Chevron Icon -->
                <svg 
                    xmlns="http://www.w3.org/2000/svg" 
                    fill="none" 
                    viewBox="0 0 24 24" 
                    stroke-width="2.5" 
                    stroke="currentColor" 
                    :class="['w-3.5 h-3.5 transition-transform duration-200', isExpanded ? 'rotate-180' : '']"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
        </div>
    </div>
</template>
