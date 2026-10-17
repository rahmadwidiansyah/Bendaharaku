<script setup>
/**
 * M3IconButton — Material Design 3 Expressive icon button.
 * Per references/components/buttons.md: 40dp container, full shape,
 * 8% hover / 10% pressed state layers, 38% disabled opacity.
 */
import { computed } from 'vue'

const props = defineProps({
    variant: { type: String, default: 'standard' }, // standard | tonal | filled
    ariaLabel: { type: String, required: true },
    disabled: { type: Boolean, default: false },
    type: { type: String, default: 'button' },
})

defineEmits(['click'])

const variantClass = computed(() => `m3-icon-btn--${props.variant}`)
</script>

<template>
    <button
        :type="type"
        :aria-label="ariaLabel"
        :disabled="disabled"
        :class="['m3-icon-btn', variantClass]"
        @click="$emit('click', $event)"
    >
        <span class="m3-icon-btn__content" aria-hidden="true">
            <slot />
        </span>
    </button>
</template>

<style scoped>
.m3-icon-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    padding: 0.5rem;
    border: none;
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    cursor: pointer;
    overflow: hidden;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

/* Touch target expansion to 48x48dp minimum per accessibility guideline */
.m3-icon-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    min-width: 48px;
    min-height: 48px;
    width: 100%;
    height: 100%;
    pointer-events: auto;
}

.m3-icon-btn__content {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
}

.m3-icon-btn__content :deep(svg) {
    width: 1.5rem;
    height: 1.5rem;
}

/* State layer via ::after (8% hover, 10% pressed of currentColor) */
.m3-icon-btn::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-icon-btn:hover::after {
    opacity: 0.08;
}

.m3-icon-btn:active::after {
    opacity: 0.1;
}

.m3-icon-btn:active {
    transform: scale(0.94);
}

.m3-icon-btn:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-icon-btn--tonal {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-icon-btn--filled {
    background: var(--m3-color-primary);
    color: var(--m3-color-on-primary);
}

.m3-icon-btn:disabled {
    opacity: 0.38;
    cursor: not-allowed;
    transform: none;
}

.m3-icon-btn:disabled::after {
    display: none;
}
</style>
