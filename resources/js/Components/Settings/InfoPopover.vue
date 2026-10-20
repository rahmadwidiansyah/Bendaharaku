<script setup>
/**
 * InfoPopover.vue — tombol ikon kecil + panel keterangan (tema Default).
 * Panel diteleport ke <body> oleh useInfoPopover agar tidak terpotong kartu.
 */
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from '@/Components/AppIcon.vue'
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

const toneClasses = computed(() => ({
    neutral: 'text-[var(--color-text-muted)] hover:bg-[var(--color-surface-muted)]',
    success: 'text-[var(--color-income-text)] hover:bg-[var(--color-income-bg)]',
    warning: 'text-[var(--color-debt-text)] hover:bg-[var(--color-debt-bg)]',
    danger: 'text-[var(--color-expense-text)] hover:bg-[var(--color-expense-bg)]',
}[props.tone]))

const triggerIcon = computed(() => {
    if (props.icon) return props.icon
    return ['warning', 'danger'].includes(props.tone) ? 'circle-alert' : 'info'
})
</script>

<template>
    <span ref="rootRef" class="relative inline-flex shrink-0">
        <button
            type="button"
            data-popover-trigger
            :aria-label="label"
            :aria-expanded="isOpen"
            :aria-controls="panelId"
            aria-haspopup="dialog"
            :class="[
                'info-trigger relative inline-flex h-7 w-7 items-center justify-center rounded-full transition-colors',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-brand)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--color-surface-raised)]',
                toneClasses,
            ]"
            @click="toggle"
        >
            <AppIcon :icon="triggerIcon" class="h-4 w-4" />
        </button>
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
            class="fixed z-50 flex max-h-[70vh] flex-col gap-2 overflow-y-auto rounded-[var(--radius-lg)] border border-[var(--color-border-default)] bg-[var(--color-surface-overlay)] p-3 text-[var(--color-text-secondary)] shadow-dropdown backdrop-blur-xl outline-none"
        >
            <div class="flex items-start justify-between gap-2">
                <p :id="titleId" class="text-2xs font-bold uppercase tracking-widest text-[var(--color-text-primary)]">
                    {{ label }}
                </p>
                <button
                    type="button"
                    class="relative -mr-1 -mt-1 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[var(--color-text-muted)] transition-colors hover:bg-[var(--color-surface-muted)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-brand)]"
                    :aria-label="t('common.close')"
                    @click="close()"
                >
                    <AppIcon icon="x" class="h-4 w-4" />
                </button>
            </div>

            <div class="text-2xs leading-relaxed">
                <slot />
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.info-trigger::before {
    content: '';
    position: absolute;
    inset: calc(var(--spacing-sm) * -1);
    border-radius: var(--radius-pill);
}
</style>
