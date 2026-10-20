<script setup>
/**
 * M3InfoPopover — tombol ikon + panel keterangan (tema M3).
 * Perilaku buka/tutup/fokus ditangani useInfoPopover; panel diteleport.
 */
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import { useInfoPopover } from '@/Composables/useInfoPopover'

const props = defineProps({
    label: { type: String, required: true },
    tone: {
        type: String,
        default: 'neutral',
        validator: (v) => ['neutral', 'success', 'warning', 'danger'].includes(v),
    },
    icon: { type: String, default: '' },
})

const { t } = useI18n()
const { isOpen, rootRef, panelRef, panelId, titleId, panelStyle, close, toggle } = useInfoPopover()

const toneStyle = computed(() => ({
    color: {
        neutral: 'var(--m3-color-on-surface-variant)',
        success: 'var(--m3-color-primary)',
        warning: 'var(--m3-color-tertiary)',
        danger: 'var(--m3-color-error)',
    }[props.tone],
}))

const triggerIcon = computed(() => {
    if (props.icon) return props.icon
    return ['warning', 'danger'].includes(props.tone) ? 'alert' : 'info'
})
</script>

<template>
    <span ref="rootRef" class="m3-info-popover__root">
        <M3IconButton
            data-popover-trigger
            :style="toneStyle"
            :aria-label="label"
            :aria-expanded="isOpen"
            :aria-controls="panelId"
            aria-haspopup="dialog"
            @click="toggle"
        >
            <svg v-if="triggerIcon === 'alert'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3h.008v.008H12v-.008z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86 2.82 16.6A2.25 2.25 0 0 0 4.75 20h14.5a2.25 2.25 0 0 0 1.93-3.4L13.71 3.86a2.25 2.25 0 0 0-3.42 0Z" />
            </svg>
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25h.75v5.25m-.75 0h1.5M12 7.5h.008v.008H12V7.5Z" />
                <circle cx="12" cy="12" r="9" />
            </svg>
        </M3IconButton>
    </span>

    <Teleport to="body">
        <div
            v-if="isOpen"
            :id="panelId"
            ref="panelRef"
            :style="panelStyle"
            role="dialog"
            :aria-labelledby="titleId"
            tabindex="-1"
            class="m3-info-popover"
        >
            <div class="m3-info-popover__head">
                <p :id="titleId" class="m3-info-popover__title">{{ label }}</p>
                <M3IconButton :aria-label="t('common.close')" class="m3-info-popover__close" @click="close()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 18 12-12M6 6l12 12" />
                    </svg>
                </M3IconButton>
            </div>

            <div class="m3-info-popover__body">
                <slot />
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.m3-info-popover__root {
    display: inline-flex;
    flex-shrink: 0;
}

.m3-info-popover {
    position: fixed;
    z-index: 50;
    display: flex;
    max-height: 70vh;
    flex-direction: column;
    gap: var(--m3-space-2);
    overflow-y: auto;
    padding: var(--m3-space-3);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-high);
    box-shadow: var(--m3-elevation-2);
    color: var(--m3-color-on-surface);
    outline: none;
}

.m3-info-popover__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--m3-space-2);
}

.m3-info-popover__title {
    margin: 0;
    padding-top: var(--m3-space-1);
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    letter-spacing: var(--m3-type-title-small-tracking);
    color: var(--m3-color-on-surface);
}

.m3-info-popover__close {
    margin: calc(var(--m3-space-1) * -1);
}

.m3-info-popover__body {
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}
</style>
