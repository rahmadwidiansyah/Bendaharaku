<script setup>
/**
 * M3ListItem — Material Design 3 Expressive list item.
 * Leading icon + headline + supporting text + trailing content.
 * Divider uses outline-variant (never plain outline on multi-element rows).
 *
 * `href` merender sebagai Inertia <Link> (semantik anchor, keyboard-aware);
 * `clickable` tanpa href merender div role="button" dengan emit click.
 */
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    clickable: { type: Boolean, default: false },
    dense: { type: Boolean, default: false },
    divider: { type: Boolean, default: false },
    href: { type: String, default: '' },
})

const emit = defineEmits(['click'])

const isInteractive = computed(() => props.clickable || !!props.href)

function onEnter(e) {
    if (!props.href && props.clickable) emit('click', e)
}

function onSpace(e) {
    if (!props.href && props.clickable) {
        e.preventDefault()
        emit('click', e)
    }
}
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href || undefined"
        :class="['m3-list-item', isInteractive ? 'm3-list-item--clickable' : '', dense ? 'm3-list-item--dense' : '', divider ? 'm3-list-item--divider' : '']"
        :role="!href && clickable ? 'button' : undefined"
        :tabindex="!href && clickable ? 0 : undefined"
        @click="$emit('click', $event)"
        @keydown.enter="onEnter"
        @keydown.space="onSpace"
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
    </component>
</template>

<style scoped>
.m3-list-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: var(--m3-space-4);
    width: 100%;
    padding: var(--m3-space-3) var(--m3-space-4);
    background: var(--m3-color-surface);
    color: var(--m3-color-on-surface);
    overflow: hidden;
    text-decoration: none;
}

.m3-list-item--dense {
    padding-top: var(--m3-space-2);
    padding-bottom: var(--m3-space-2);
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
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
}

.m3-list-item__body {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
    line-height: var(--m3-type-body-medium-line-height);
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
