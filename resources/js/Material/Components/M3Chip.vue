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
        <span class="m3-chip__label"><slot /></span>
    </button>
</template>

<style scoped>
.m3-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2rem;
    padding: 0 0.875rem;
    border-radius: var(--m3-radius-full);
    border: 1px solid var(--m3-color-outline);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-label-large-weight);
    letter-spacing: var(--m3-type-label-large-tracking);
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-chip:hover {
    background: rgba(227, 226, 230, 0.08);
}

.m3-chip:active {
    background: rgba(227, 226, 230, 0.1);
}

.m3-chip--selected {
    background: var(--m3-color-secondary-container);
    border-color: transparent;
    color: var(--m3-color-on-secondary-container);
}

.m3-chip--selected:hover {
    background: var(--m3-color-secondary-container);
    filter: brightness(1.12);
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
