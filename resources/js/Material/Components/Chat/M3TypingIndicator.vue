<script setup>
/**
 * M3TypingIndicator — tiga titik saat bot memproses (paritas Default).
 * Bubble surface-container-high, titik on-surface-variant.
 * prefers-reduced-motion: global m3-tokens mematikan durasi animasi.
 */
import M3BotAvatar from './M3BotAvatar.vue'

defineProps({
    botAvatar: { type: String, default: null },
    botName:   { type: String, default: 'Ken-Chan' },
})
</script>

<template>
    <div class="m3-typing">
        <M3BotAvatar :src="botAvatar" :name="botName" size="sm" variant="bot" shape="circle" class="m3-typing__avatar" />
        <div class="m3-typing__bubble" role="status" :aria-label="$t('chat.typing')">
            <span class="m3-typing__dot" />
            <span class="m3-typing__dot m3-typing__dot--2" />
            <span class="m3-typing__dot m3-typing__dot--3" />
        </div>
    </div>
</template>

<style scoped>
.m3-typing {
    display: flex;
    align-items: flex-end;
    gap: var(--m3-space-2);
    padding: var(--m3-space-1) var(--m3-space-3);
}

/* Muncul setelah pesan user (awal grup bot): jarak antar-pengirim */
.m3-typing--group-start {
    padding-top: var(--m3-space-2-5);
}

.m3-typing__avatar {
    align-self: flex-end;
    margin-bottom: var(--m3-space-0-5);
}

.m3-typing__bubble {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1);
    padding: var(--m3-space-2-5) var(--m3-space-3);
    background: var(--m3-color-surface-container-high);
    border-radius: var(--m3-radius-lg-inc);
    border-top-left-radius: var(--m3-radius-xs);
    box-shadow: var(--m3-elevation-1);
}

.m3-typing__dot {
    width: 0.375rem;
    height: 0.375rem;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-on-surface-variant);
    animation: m3-typing-bounce 1s infinite;
}

.m3-typing__dot--2 { animation-delay: 120ms; }
.m3-typing__dot--3 { animation-delay: 240ms; }

@keyframes m3-typing-bounce {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
    30% { transform: translateY(-0.25rem); opacity: 1; }
}
</style>
