<script setup>
/**
 * MessageRenderer.vue
 *
 * Dispatcher komponen pesan berdasarkan `component.type`.
 * Ini adalah single point of entry untuk render semua jenis konten bot.
 *
 * Untuk menambahkan tipe baru:
 * 1. Buat komponen baru di Messages/
 * 2. Import di sini
 * 3. Tambahkan ke componentMap
 *
 * Tidak ada kondisi template di luar komponen ini untuk render tipe pesan.
 */

import MessageText           from './MessageText.vue'
import MessageDivider        from './MessageDivider.vue'
import MessageTransactionCard from './MessageTransactionCard.vue'
import MessageSummaryCard    from './MessageSummaryCard.vue'
import MessageErrorCard      from './MessageErrorCard.vue'
import MessageSuggestion     from './MessageSuggestion.vue'

defineProps({
    component: {
        type: Object,
        required: true,
    },
})

const componentMap = {
    text:             MessageText,
    divider:          MessageDivider,
    transaction_card: MessageTransactionCard,
    summary_card:     MessageSummaryCard,
    error:            MessageErrorCard,
    warning:          MessageErrorCard,    // Reuse ErrorCard, severity prop menentukan style
    suggestion:       MessageSuggestion,
    // Future:
    // quick_reply:   MessageQuickReply,
    // image:         MessageImage,
    // code:          MessageCode,
    // markdown:      MessageMarkdown,
}
</script>

<template>
    <component
        :is="componentMap[component.type]"
        v-if="componentMap[component.type]"
        :component="component"
    />
    <!-- Fallback: tipe tidak dikenal, tampilkan teks mentah (debug only) -->
    <div v-else class="text-2xs text-gray-600 font-mono px-2 py-1">
        [{{ component.type }}]
    </div>
</template>
