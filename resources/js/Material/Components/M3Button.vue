<script setup>
/**
 * M3Button — Material Design 3 Expressive button.
 * Per references/components/buttons.md:
 * - Variants: filled (default), tonal, outlined, text, elevated
 * - Sizes: sm (36dp), md (40dp default), lg (48dp)
 * - State layers: currentColor 8% hover / 12% pressed via ::after
 * - Expressive motion: spatial spring scale(0.96) on active
 * - Focus indicator: 2px solid primary, 2px offset
 * - Disabled opacity: 0.38
 */
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    variant: {
        type: String,
        default: 'filled',
        validator: (v) => ['filled', 'tonal', 'outlined', 'text', 'elevated', 'error'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    shape: {
        type: String,
        default: 'round',
        validator: (v) => ['round', 'square'].includes(v),
    },
    type: {
        type: String,
        default: 'button',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    fullWidth: {
        type: Boolean,
        default: false,
    },
    href: {
        type: String,
        default: '',
    },
})

defineEmits(['click'])

const classes = computed(() => [
    'm3-btn',
    `m3-btn--${props.variant}`,
    `m3-btn--${props.size}`,
    `m3-btn--${props.shape}`,
    props.fullWidth ? 'm3-btn--full' : '',
])
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :href="href || undefined"
        :type="href ? undefined : type"
        :disabled="href ? undefined : disabled"
        :class="classes"
        @click="$emit('click', $event)"
    >
        <span v-if="$slots.leading" class="m3-btn__leading" aria-hidden="true">
            <slot name="leading" />
        </span>
        <span class="m3-btn__label">
            <slot />
        </span>
        <span v-if="$slots.trailing" class="m3-btn__trailing" aria-hidden="true">
            <slot name="trailing" />
        </span>
    </component>
</template>

<style scoped>
.m3-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border: none;
    font-family: inherit;
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-label-large-weight);
    letter-spacing: var(--m3-type-label-large-tracking);
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    overflow: hidden;
    user-select: none;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        box-shadow var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

/* Shapes */
.m3-btn--round {
    border-radius: var(--m3-radius-full);
}

.m3-btn--square {
    border-radius: var(--m3-radius-md);
}

/* Sizes */
.m3-btn--sm {
    min-height: 2.25rem;
    padding: 0 0.75rem;
    font-size: var(--m3-type-label-medium-size);
}

.m3-btn--md {
    min-height: 2.5rem;
    padding: 0 1rem;
}

.m3-btn--lg {
    min-height: 3rem;
    padding: 0 1.25rem;
}

.m3-btn--full {
    width: 100%;
}

/* State layer via ::after */
.m3-btn::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-btn:hover::after {
    opacity: 0.08;
}

.m3-btn:active::after {
    opacity: 0.12;
}

.m3-btn:active:not(:disabled) {
    transform: scale(0.96);
}

.m3-btn:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

/* Color variants */
.m3-btn--filled {
    background: var(--m3-color-primary);
    color: var(--m3-color-on-primary);
}

.m3-btn--tonal {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-btn--outlined {
    background: transparent;
    border: 1px solid var(--m3-color-outline-variant);
    color: var(--m3-color-primary);
}

.m3-btn--outlined:hover {
    border-color: var(--m3-color-outline);
}

.m3-btn--text {
    background: transparent;
    color: var(--m3-color-primary);
    padding-left: 0.75rem;
    padding-right: 0.75rem;
}

.m3-btn--elevated {
    background: var(--m3-color-surface-container-low);
    color: var(--m3-color-primary);
    box-shadow: var(--m3-elevation-1);
}

.m3-btn--elevated:hover {
    box-shadow: var(--m3-elevation-2);
}

.m3-btn--error {
    background: var(--m3-color-error);
    color: var(--m3-color-on-error);
}

/* Slots */
.m3-btn__leading,
.m3-btn__trailing {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.m3-btn__leading :deep(svg),
.m3-btn__trailing :deep(svg) {
    width: 1.125rem;
    height: 1.125rem;
}

/* Disabled */
.m3-btn:disabled {
    opacity: 0.38;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.m3-btn:disabled::after {
    display: none;
}
</style>
