<script setup>
/**
 * M3JumpButton — FAB "ke pesan terbaru" (paritas tombol di Chat/Index Default).
 * Posisi bottom diatur dari parent (adaptif terhadap tinggi composer).
 */
import { useI18n } from 'vue-i18n'

defineProps({
    show:        { type: Boolean, default: false },
    unreadCount: { type: Number,  default: 0 },
    bottom:      { type: String,  default: '76px' },
})

defineEmits(['click'])

const { t } = useI18n()
</script>

<template>
    <Transition name="m3-jump">
        <button
            v-if="show"
            type="button"
            class="m3-jump"
            :style="{ bottom }"
            :aria-label="t('chat.scrollToBottom')"
            @click="$emit('click')"
        >
            <span class="m3-jump__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </span>
            <span class="m3-jump__label">
                <template v-if="unreadCount > 0">{{ unreadCount }} {{ t('chat.newMessages') }}</template>
                <template v-else>{{ t('chat.latest') }}</template>
            </span>
        </button>
    </Transition>
</template>

<style scoped>
.m3-jump {
    position: absolute;
    right: var(--m3-space-4);
    z-index: 20;
    display: flex;
    align-items: center;
    gap: var(--m3-space-1-5);
    padding: var(--m3-space-1-5) var(--m3-space-3) var(--m3-space-1-5) var(--m3-space-2);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-full);
    background: color-mix(in srgb, var(--m3-color-surface-container-high) 88%, transparent);
    backdrop-filter: blur(var(--m3-blur-glass));
    -webkit-backdrop-filter: blur(var(--m3-blur-glass));
    box-shadow: var(--m3-elevation-2);
    color: var(--m3-color-on-surface);
    cursor: pointer;
    transition: box-shadow var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-jump:hover {
    box-shadow: var(--m3-elevation-3);
}

.m3-jump:active {
    transform: scale(0.96);
}

.m3-jump:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-jump__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.25rem;
    height: 1.25rem;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-primary);
    color: var(--m3-color-on-primary);
    flex-shrink: 0;
}

.m3-jump__icon svg {
    width: 0.75rem;
    height: 0.75rem;
}

.m3-jump__label {
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-emphasized-label-large-weight);
    white-space: nowrap;
}

/* Transisi M3: muncul naik + memudar */
.m3-jump-enter-active {
    transition:
        opacity var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects),
        transform var(--m3-motion-default-spatial-duration) var(--m3-motion-default-spatial);
}

.m3-jump-leave-active {
    transition:
        opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-jump-enter-from,
.m3-jump-leave-to {
    opacity: 0;
    transform: translateY(0.75rem) scale(0.9);
}
</style>
