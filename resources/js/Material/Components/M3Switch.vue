<script setup>
/**
 * M3Switch — Material Design 3 switch.
 * Track 52×32, thumb 24 (16 saat off) sesuai spesifikasi M3.
 * State layer 10% hover/pressed + focus ring 2px; motion spatial fast
 * untuk thumb dan effects fast untuk warna. Kontrak: v-model boolean.
 */
const props = defineProps({
    modelValue: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    ariaLabel: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

function toggle() {
    if (props.disabled) return
    emit('update:modelValue', !props.modelValue)
}
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="modelValue"
        :aria-label="ariaLabel || undefined"
        :disabled="disabled"
        :class="['m3-switch', { 'm3-switch--on': modelValue }]"
        @click="toggle"
    >
        <span class="m3-switch__track" aria-hidden="true">
            <span class="m3-switch__state"></span>
            <span class="m3-switch__thumb"></span>
        </span>
    </button>
</template>

<style scoped>
.m3-switch {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
    padding: 0;
    border: none;
    background: transparent;
    cursor: pointer;
    flex-shrink: 0;
}

.m3-switch:disabled {
    cursor: not-allowed;
    opacity: 0.38;
}

.m3-switch__track {
    position: relative;
    display: inline-flex;
    align-items: center;
    width: 3.25rem;
    height: 2rem;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-highest);
    border: 2px solid var(--m3-color-outline);
    box-sizing: border-box;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-switch--on .m3-switch__track {
    background: var(--m3-color-primary);
    border-color: var(--m3-color-primary);
}

.m3-switch__state {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 2.5rem;
    height: 2.5rem;
    transform: translate(-50%, -50%);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-on-surface);
    opacity: 0;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-switch:hover:not(:disabled) .m3-switch__state {
    opacity: 0.08;
}

.m3-switch:active:not(:disabled) .m3-switch__state {
    opacity: 0.1;
}

.m3-switch:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
    border-radius: var(--m3-radius-full);
}

.m3-switch__thumb {
    position: relative;
    width: 1rem;
    height: 1rem;
    margin-left: 0.375rem;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-outline);
    transition:
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial),
        width var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial),
        height var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial),
        margin var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial),
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-switch--on .m3-switch__thumb {
    width: 1.5rem;
    height: 1.5rem;
    margin-left: 1.25rem;
    background: var(--m3-color-on-primary);
}
</style>
