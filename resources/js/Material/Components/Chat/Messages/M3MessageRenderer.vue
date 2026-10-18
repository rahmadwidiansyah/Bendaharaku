<script setup>
/**
 * M3MessageRenderer — dispatcher komponen pesan berdasarkan `component.type`
 * (paritas MessageRenderer Default, tanpa alur review).
 *
 * Menambah tipe baru:
 * 1. Buat komponen di Messages/
 * 2. Import di sini
 * 3. Tambahkan ke componentMap
 */
import M3MessageText      from './M3MessageText.vue'
import M3MessageDivider   from './M3MessageDivider.vue'
import M3MessageTransactionCard from './M3MessageTransactionCard.vue'
import M3MessageSummaryCard from './M3MessageSummaryCard.vue'
import M3MessageErrorCard from './M3MessageErrorCard.vue'
import M3MessageSuggestion from './M3MessageSuggestion.vue'
import M3MessageReportSection from './M3MessageReportSection.vue'
import M3MessageImage     from './M3MessageImage.vue'
import M3BarChart         from './M3BarChart.vue'

defineProps({
    component: {
        type: Object,
        required: true,
    },
    metadata: {
        type: Object,
        default: () => ({}),
    },
    content: {
        type: Array,
        default: () => ([]),
    },
})

const emit = defineEmits(['suggest', 'retry'])

const componentMap = {
    text:           M3MessageText,
    divider:        M3MessageDivider,
    transaction_card: M3MessageTransactionCard,
    summary_card:   M3MessageSummaryCard,
    error:          M3MessageErrorCard,
    warning:        M3MessageErrorCard,
    suggestion:     M3MessageSuggestion,
    report_section: M3MessageReportSection,
    image:          M3MessageImage,
    bar_chart:      M3BarChart,
}
</script>

<template>
    <component
        :is="componentMap[component.type]"
        v-if="componentMap[component.type]"
        :component="component"
        :content="content"
        v-bind="['transaction_card', 'summary_card'].includes(component.type) ? { metadata } : {}"
        @suggest="emit('suggest', $event)"
        @retry="emit('retry', $event)"
    />
    <!-- Fallback: tipe tidak dikenal, tampilkan teks mentah (debug only) -->
    <div v-else class="m3-msg-fallback">
        [{{ component.type }}]
    </div>
</template>

<style scoped>
.m3-msg-fallback {
    padding: var(--m3-space-1) var(--m3-space-2);
    font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
}
</style>
