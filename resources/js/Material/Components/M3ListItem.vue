<script setup>
/**
 * M3ListItem — Material Design 3 Expressive list item.
 * Leading icon + headline + supporting text + trailing content.
 * Divider uses outline-variant (never plain outline on multi-element rows).
 */
defineProps({
    clickable: { type: Boolean, default: false },
    dense: { type: Boolean, default: false },
    divider: { type: Boolean, default: false },
})

defineEmits(['click'])
</script>

<template>
    <div
        :class="['m3-list-item', clickable ? 'm3-list-item--clickable' : '', dense ? 'm3-list-item--dense' : '', divider ? 'm3-list-item--divider' : '']"
        :role="clickable ? 'button' : undefined"
        :tabindex="clickable ? 0 : undefined"
        @click="$emit('click', $event)"
        @keydown.enter="$emit('click', $event)"
        @keydown.space.prevent="$emit('click', $event)"
    >
        <span v-if="$slots.icon" class="m3-list-item__leading" aria-hidden="true">
            <slot name="icon" />
        </span>
        <span class="m3-list-item__body">
            <span class="m3-list-item__headline"><slot /></span>
            <span v-if="$slots.supporting" class="m3-list-item__supporting"><slot name="supporting" /></span>
        </span>
        <span v-if="$slots.trailing" class="m3-list-item__trailing">
            <slot name="trailing" />
        </span>
    </div>
</template>

<style scoped>
.m3-list-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--m3-color-surface);
    color: var(--m3-color-on-surface);
    overflow: hidden;
}

.m3-list-item--dense {
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
}

.m3-list-item--divider {
    border-bottom: 1px solid var(--m3-color-outline-variant);
}

.m3-list-item--clickable {
    cursor: pointer;
}

.m3-list-item--clickable::after {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--m3-color-on-surface);
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-list-item--clickable:hover::after {
    opacity: 0.08;
}

.m3-list-item--clickable:active::after {
    opacity: 0.1;
}

.m3-list-item--clickable:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: -2px;
}

.m3-list-item__leading {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: var(--m3-radius-full);
}

.m3-list-item__body {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
    line-height: 1.25rem;
}

.m3-list-item__headline {
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
    font-weight: var(--m3-type-body-large-weight);
    color: var(--m3-color-on-surface);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-list-item__supporting {
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    font-weight: var(--m3-type-body-medium-weight);
    letter-spacing: var(--m3-type-body-medium-tracking);
    color: var(--m3-color-on-surface-variant);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-list-item__trailing {
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
}
</style>
