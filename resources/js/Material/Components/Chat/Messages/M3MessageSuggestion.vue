<script setup>
/**
 * M3MessageSuggestion — chip saran di dalam bubble bot (paritas Default).
 * Klik → emit 'suggest' dengan insert_text (teks masuk composer, bisa diedit).
 */
const props = defineProps({
    component: { type: Object, required: true },
})

const emit = defineEmits(['suggest'])

function handleClick() {
    const text = props.component.insert_text || props.component.message || ''
    if (text) emit('suggest', text)
}
</script>

<template>
    <button type="button" class="m3-suggestion" @click="handleClick">
        {{ component.message }}
    </button>
</template>

<style scoped>
.m3-suggestion {
    display: block;
    width: 100%;
    text-align: start;
    padding: var(--m3-space-2) var(--m3-space-3);
    margin-top: var(--m3-space-0-5);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface);
    font-family: inherit;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-suggestion:hover {
    border-color: var(--m3-color-primary);
    background: color-mix(in srgb, var(--m3-color-primary) 8%, var(--m3-color-surface-container-high));
}

.m3-suggestion:active {
    transform: scale(0.98);
}

.m3-suggestion:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}
</style>
