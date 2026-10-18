<script setup>
/**
 * M3ChatEmptyState — empty state chat M3 (paritas ChatEmptyState Default).
 * Saran ditampilkan sebagai assist chip; hint lewat title (a11y).
 */
import M3BotAvatar from './M3BotAvatar.vue'
import M3Chip from '@/Material/Components/M3Chip.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
    botName:   { type: String, default: 'Ken-Chan' },
    botAvatar: { type: String, default: null },
})

defineEmits(['select'])

const suggestions = [
    { label: 'Makan 20 ribu',              hintKey: 'chat.suggestionExpense' },
    { label: 'Gaji 5 juta BCA',            hintKey: 'chat.suggestionIncome' },
    { label: 'Transfer BCA ke Dana 100rb', hintKey: 'chat.suggestionTransfer' },
    { label: '/saldo',                     hintKey: 'chat.suggestionBalance' },
    { label: '/ringkasan',                 hintKey: 'chat.suggestionSummary' },
    { label: '/laporan',                   hintKey: 'chat.suggestionReport' },
    { label: '/statistik',                 hintKey: 'chat.suggestionStats' },
    { label: '/help',                      hintKey: 'chat.suggestionHelp' },
]
</script>

<template>
    <div class="m3-chat-empty">
        <M3BotAvatar :src="botAvatar" :name="botName" size="lg" variant="bot" shape="rounded" :online="true" class="m3-chat-empty__avatar" />

        <h2 class="m3-chat-empty__name">{{ botName }}</h2>
        <p class="m3-chat-empty__tagline">{{ t('chat.emptyState') }}</p>

        <p class="m3-chat-empty__label">{{ t('chat.gettingStarted') }}</p>
        <div class="m3-chat-empty__chips">
            <M3Chip
                v-for="s in suggestions"
                :key="s.label"
                variant="assist"
                :title="t(s.hintKey)"
                @click="$emit('select', s.label)"
            >
                {{ s.label }}
            </M3Chip>
        </div>
    </div>
</template>

<style scoped>
.m3-chat-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--m3-space-8) var(--m3-space-6);
    text-align: center;
    user-select: none;
}

.m3-chat-empty__avatar {
    margin-bottom: var(--m3-space-5);
    box-shadow: var(--m3-elevation-2);
}

.m3-chat-empty__name {
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    color: var(--m3-color-on-surface);
    margin-bottom: var(--m3-space-1);
}

.m3-chat-empty__tagline {
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
    max-width: 20rem;
    margin-bottom: var(--m3-space-8);
}

.m3-chat-empty__label {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
    margin-bottom: var(--m3-space-3);
}

.m3-chat-empty__chips {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--m3-space-2);
    max-width: 28rem;
}
</style>
