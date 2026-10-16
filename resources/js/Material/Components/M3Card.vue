<script setup>
/**
 * M3Card — Material Design 3 Expressive card.
 * Per references/components/containment.md: 12dp shape, 16dp side padding,
 * 3 variants (filled / elevated / outlined).
 * Nested radii rule: inner = outer − padding (children use smaller steps).
 */
import { computed } from 'vue'

const props = defineProps({
    variant: { type: String, default: 'filled' }, // filled | elevated | outlined
    padding: { type: String, default: 'md' }, // none | sm | md | lg
    clickable: { type: Boolean, default: false },
})

const classes = computed(() => [
    'm3-card',
    `m3-card--${props.variant}`,
    `m3-card--pad-${props.padding}`,
    props.clickable ? 'm3-card--clickable' : '',
])
</script>

<template>
    <div :class="classes" :role="clickable ? 'button' : undefined" :tabindex="clickable ? 0 : undefined">
        <slot />
    </div>
</template>

<style scoped>
/* Compact-finance language: cards use 16dp (lg), not the M3 card-spec
   12dp — hierarchy: cards 16–20, controls 12–16, chips pill. */
.m3-card {
    border-radius: var(--m3-radius-lg);
    color: var(--m3-color-on-surface);
    transition:
        box-shadow var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects),
        background-color var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-card--filled {
    background: var(--m3-color-surface-container-low);
}

.m3-card--elevated {
    background: var(--m3-color-surface-container-low);
    box-shadow: var(--m3-elevation-1);
}

.m3-card--outlined {
    background: var(--m3-color-surface);
    border: 1px solid var(--m3-color-outline-variant);
}

.m3-card--pad-none {
    padding: 0;
}

.m3-card--pad-sm {
    padding: 0.75rem;
}

.m3-card--pad-md {
    padding: 1rem;
}

.m3-card--pad-lg {
    padding: 1.5rem;
}

.m3-card--clickable {
    cursor: pointer;
}

.m3-card--clickable:hover {
    box-shadow: var(--m3-elevation-2);
}

.m3-card--clickable:active {
    box-shadow: var(--m3-elevation-1);
}
</style>
