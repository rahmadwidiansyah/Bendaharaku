<script setup>
/**
 * M3Chip — Material Design 3 Expressive chip.
 * Dashboard phase 1 only needs the `filter` variant (calendar filter:
 * income / total / expense). `assist` included for small actions.
 * Selected = secondary-container fill; unselected = outline + on-surface-variant.
 */
import { computed } from 'vue'

const props = defineProps({
    variant: { type: String, default: 'filter' }, // filter | assist
    selected: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
})

defineEmits(['click'])

const classes = computed(() => [
    'm3-chip',
    `m3-chip--${props.variant}`,
    props.selected ? 'm3-chip--selected' : '',
])
</script>

<template>
    <button
        type="button"
        :class="classes"
        :disabled="disabled"
        :aria-pressed="variant === 'filter' ? selected : undefined"
        @click="$emit('click', $event)"
    >
        <svg
            v-if="variant === 'filter' && selected"
            class="m3-chip__check"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="3"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
        <span class="m3-chip__label"><slot /></span>
    </button>
</template>

<style scoped>
.m3-chip {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-1);
    min-height: 2rem;
    /* 14px (0.875rem) tidak ada di skala --m3-space-* → dipetakan ke 16px */
    padding: 0 var(--m3-space-4);
    border-radius: var(--m3-radius-full);
    border: 1px solid var(--m3-color-outline);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-label-large-weight);
    letter-spacing: var(--m3-type-label-large-tracking);
    cursor: pointer;
    overflow: hidden;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

/* State layer via ::after — currentColor 8% hover / 10% pressed */
.m3-chip::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-chip:hover::after {
    opacity: 0.08;
}

.m3-chip:active::after {
    opacity: 0.1;
}

.m3-chip:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-chip__check {
    width: 1.125rem;
    height: 1.125rem;
    flex-shrink: 0;
}

.m3-chip--selected {
    background: var(--m3-color-secondary-container);
    border-color: transparent;
    color: var(--m3-color-on-secondary-container);
}

.m3-chip--selected::after {
    background: var(--m3-color-on-secondary-container);
}

.m3-chip--selected:hover::after {
    opacity: 0.08;
}

.m3-chip--selected:active::after {
    opacity: 0.1;
}

.m3-chip:disabled {
    opacity: 0.38;
    cursor: not-allowed;
}

.m3-chip__label {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
