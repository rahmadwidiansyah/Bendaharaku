<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import MarkdownRenderer from './MarkdownRenderer.vue'

const { t } = useI18n()

const props = defineProps({
    component: { type: Object, required: true },
})

const isWarning = computed(() => props.component.severity === 'warning')

// Hapus properti 'icon' dari config karena sudah dari backend
const config = computed(() => isWarning.value ? { text: 'text-debt-text', raw: 'text-debt-text/80' } : { text: 'text-expense-text', raw: 'text-expense-text/80' })
</script>

<template>
    <div class="px-3.5 py-3 flex gap-2.5">
        <!-- Bagian <span class="text-sm mt-0.5 shrink-0">{{ config.icon }}</span> SUDAH DIHAPUS -->

        <div class="flex-1 min-w-0">
            <!-- Index label -->
            <p v-if="component.index !== null && component.index !== undefined"
                class="text-2xs font-bold text-[var(--color-text-muted)] mb-1">{{ t('chat.errorItem') }}{{ component.index }}</p>

            <!-- Raw input -->
            <p v-if="component.raw" :class="['text-xs font-mono mb-1 truncate', config.raw]">
                "{{ component.raw }}"
            </p>

            <!-- Message: render markdown (*bold*, \n, dll) dengan warna mengikuti config -->
            <div :class="config.text">
                <MarkdownRenderer :content="component.message" :style="{ color: 'currentColor' }" />
            </div>
        </div>
    </div>
</template>