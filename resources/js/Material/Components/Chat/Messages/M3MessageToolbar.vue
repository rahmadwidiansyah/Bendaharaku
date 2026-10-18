<script setup>
/**
 * M3MessageToolbar — toolbar aksi di samping bubble bot (paritas Default).
 * Berisi: Salin teks, Generate Ulang, Coba Lagi (error only).
 */
import { useI18n } from 'vue-i18n'
import AppIcon from '@/Components/AppIcon.vue'
import { useClipboard } from '@/Composables/useClipboard.js'

const { t } = useI18n()

const props = defineProps({
    message:       { type: Object,  required: true },
    isError:       { type: Boolean, default: false },
    canRegenerate: { type: Boolean, default: true },
})

const emit = defineEmits(['copy', 'retry', 'regenerate'])

const { copied, copy } = useClipboard()

function extractText(message) {
    return (message.content ?? [])
        .filter(c => c.type === 'text' && c.text?.trim())
        .map(c => c.text)
        .join('\n')
        .trim()
}

async function handleCopy() {
    const text = extractText(props.message)
    if (!text) return
    await copy(text)
    emit('copy')
}
</script>

<template>
    <div class="m3-msg-toolbar">
        <button
            type="button"
            class="m3-msg-toolbar__btn"
            :class="{ 'm3-msg-toolbar__btn--copied': copied }"
            :title="copied ? t('chatTransaction.copied') : t('chatTransaction.copyMessage')"
            :aria-label="t('chatTransaction.copyMessage')"
            @click.stop="handleCopy"
        >
            <AppIcon :icon="copied ? 'check' : 'copy'" iconClass="m3-msg-toolbar__icon" fallback="copy" />
        </button>

        <button
            v-if="canRegenerate && !isError"
            type="button"
            class="m3-msg-toolbar__btn m3-msg-toolbar__btn--regen"
            :title="t('chatTransaction.regenerateAnswer')"
            :aria-label="t('chatTransaction.regenerate')"
            @click.stop="emit('regenerate', message)"
        >
            <AppIcon icon="refresh-cw" iconClass="m3-msg-toolbar__icon" fallback="refresh-cw" />
        </button>

        <button
            v-if="isError"
            type="button"
            class="m3-msg-toolbar__btn m3-msg-toolbar__btn--retry"
            :title="t('chatTransaction.retry')"
            :aria-label="t('chatTransaction.retrySend')"
            @click.stop="emit('retry', message)"
        >
            <AppIcon icon="rotate-ccw" iconClass="m3-msg-toolbar__icon" fallback="rotate-ccw" />
            <span class="m3-msg-toolbar__label">{{ t('chatTransaction.retry') }}</span>
        </button>
    </div>
</template>

<style scoped>
.m3-msg-toolbar {
    display: flex;
    align-items: center;
    gap: var(--m3-space-0-5);
}

.m3-msg-toolbar__btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-1);
    height: 1.75rem;
    min-width: 1.75rem;
    padding: 0 var(--m3-space-1);
    border: none;
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

/* Ekspansi touch target ke 40dp minimum (pola M3IconButton) */
.m3-msg-toolbar__btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    min-width: var(--m3-size-touch-target);
    min-height: var(--m3-size-touch-target);
    width: 100%;
    height: 100%;
}

.m3-msg-toolbar__btn:hover {
    background: color-mix(in srgb, var(--m3-color-on-surface) 8%, transparent);
    color: var(--m3-color-on-surface);
}

.m3-msg-toolbar__btn:active {
    background: color-mix(in srgb, var(--m3-color-on-surface) 10%, transparent);
}

.m3-msg-toolbar__btn:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-msg-toolbar__btn--copied {
    color: var(--m3-color-income);
}

.m3-msg-toolbar__btn--regen:hover {
    color: var(--m3-color-primary);
    background: color-mix(in srgb, var(--m3-color-primary) 8%, transparent);
}

.m3-msg-toolbar__btn--retry {
    width: auto;
    color: var(--m3-color-error);
}

.m3-msg-toolbar__btn--retry:hover {
    background: color-mix(in srgb, var(--m3-color-error) 8%, transparent);
    color: var(--m3-color-error);
}

.m3-msg-toolbar__icon {
    width: var(--m3-size-icon-2xs);
    height: var(--m3-size-icon-2xs);
    flex-shrink: 0;
}

.m3-msg-toolbar__label {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
}

@media (prefers-reduced-motion: reduce) {
    .m3-msg-toolbar__btn {
        transition: none;
    }
}
</style>
