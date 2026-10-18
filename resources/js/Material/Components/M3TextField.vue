<script setup>
/**
 * M3TextField — filled text field Material Design 3 Expressive.
 * Per references/components/input.md: container 56dp, filled = sudut atas
 * membulat + bawah siku, isi `surface container highest`, label
 * `on surface variant`, indikator aktif `primary` 2px, teksPendukung/error
 * `body small` dengan ikon error pada state error.
 *
 * Varian `centered` dipakai untuk input yang jadi fokus layar (mis. nama
 * pihak hutang/piutang); sisanya mengikuti gaya isi kiri.
 */
import { computed, ref, useId } from 'vue'

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, required: true },
    placeholder: { type: String, default: '' },
    type: { type: String, default: 'text' },
    error: { type: String, default: '' },
    uppercase: { type: Boolean, default: false },
    centered: { type: Boolean, default: false },
    inputmode: { type: String, default: undefined },
    min: { type: [String, Number], default: undefined },
    max: { type: [String, Number], default: undefined },
    autocomplete: { type: String, default: 'off' },
})

const emit = defineEmits(['update:modelValue', 'enter'])

const uid = useId()
const inputId = computed(() => `m3-field-${uid}`)
const describedBy = computed(() => (props.error ? `${inputId.value}-error` : ''))

const onInput = (e) => emit('update:modelValue', e.target.value)
const onEnter = () => emit('enter')

// Label mengambang saat field terisi atau difokuskan.
const focused = ref(false)
const floated = computed(() => focused.value || String(props.modelValue ?? '') !== '')
</script>

<template>
    <div
        class="m3-text-field"
        :class="{
            'm3-text-field--error': !!error,
            'm3-text-field--centered': centered,
            'm3-text-field--uppercase': uppercase,
        }"
    >
        <div class="m3-text-field__container" :class="floated ? 'm3-text-field__container--floated' : ''">
            <input
                :id="inputId"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :inputmode="inputmode"
                :min="min"
                :max="max"
                :autocomplete="autocomplete"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="describedBy || undefined"
                class="m3-text-field__input"
                @input="onInput"
                @focus="focused = true"
                @blur="focused = false"
                @keyup.enter="onEnter"
            >
            <label :for="inputId" class="m3-text-field__label">{{ label }}</label>
        </div>
        <p v-if="error" :id="`${inputId}-error`" class="m3-text-field__supporting" role="alert">
            <svg class="m3-text-field__error-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
                <path stroke-linecap="round" d="M12 8v5" />
                <path stroke-linecap="round" d="M12 16.5h.01" />
            </svg>
            <span>{{ error }}</span>
        </p>
    </div>
</template>

<style scoped>
.m3-text-field {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    width: 100%;
}

.m3-text-field__container {
    position: relative;
    display: flex;
    align-items: center;
    height: var(--m3-size-text-field);
    padding: 0 var(--m3-space-4);
    background: var(--m3-color-surface-container-highest);
    border-radius: var(--m3-radius-xs) var(--m3-radius-xs) 0 0;
    transition: box-shadow var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-text-field__container:focus-within {
    box-shadow: inset 0 -2px 0 0 var(--m3-color-primary);
}

.m3-text-field__input {
    flex: 1;
    min-width: 0;
    width: 100%;
    height: 100%;
    padding: 0;
    border: none;
    background: transparent;
    color: var(--m3-color-on-surface);
    font-family: inherit;
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
    letter-spacing: var(--m3-type-body-large-tracking);
    outline: none;
    appearance: none;
    transition: padding-top var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

/* Label mengambang: teks input turun agar tidak bertabrakan dengan label. */
.m3-text-field__container--floated .m3-text-field__input {
    padding-top: var(--m3-space-5);
}

.m3-text-field__input::placeholder {
    color: var(--m3-color-on-surface-variant);
    opacity: 1;
}

.m3-text-field__label {
    position: absolute;
    left: var(--m3-space-4);
    top: 50%;
    transform: translateY(-50%);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
    pointer-events: none;
    transition: all var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-text-field__container--floated .m3-text-field__label {
    top: var(--m3-space-2);
    transform: none;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
}

.m3-text-field__container:focus-within .m3-text-field__label {
    color: var(--m3-color-primary);
}

.m3-text-field--error .m3-text-field__container {
    box-shadow: inset 0 -2px 0 0 var(--m3-color-error);
}

.m3-text-field--error .m3-text-field__label,
.m3-text-field--error .m3-text-field__container:focus-within .m3-text-field__label {
    color: var(--m3-color-error);
}

.m3-text-field--centered .m3-text-field__input {
    text-align: center;
}

.m3-text-field--centered .m3-text-field__label {
    left: 0;
    width: 100%;
    text-align: center;
}

.m3-text-field--uppercase .m3-text-field__input {
    text-transform: uppercase;
}

.m3-text-field__supporting {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1);
    margin: 0;
    padding: var(--m3-space-1) var(--m3-space-4) 0;
    color: var(--m3-color-error);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    font-weight: var(--m3-type-body-small-weight);
}

.m3-text-field__error-icon {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    flex-shrink: 0;
}
</style>
