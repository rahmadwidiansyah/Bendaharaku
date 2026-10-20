<script setup>
/**
 * M3Textarea — filled text area Material Design 3 Expressive.
 * Kontrak visual mengikuti M3TextField (surface container highest,
 * label mengambang, indikator primary 2px, supporting/error body small),
 * dengan perilaku auto-resize yang sama seperti field keyword Default.
 */
import { computed, nextTick, onMounted, ref, useId, watch } from 'vue'

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, required: true },
    placeholder: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    maxHeight: { type: Number, default: 160 },
    autocomplete: { type: String, default: 'off' },
})

const emit = defineEmits(['update:modelValue'])

const uid = useId()
const inputId = computed(() => `m3-textarea-${uid}`)
const describedBy = computed(() => (props.error ? `${inputId.value}-error` : props.hint ? `${inputId.value}-hint` : ''))

const textareaRef = ref(null)
const focused = ref(false)
const floated = computed(() => focused.value || String(props.modelValue ?? '') !== '')

const resize = () => {
    const el = textareaRef.value
    if (!el) return
    el.style.height = 'auto'
    el.style.height = `${Math.min(el.scrollHeight, props.maxHeight)}px`
}

watch(() => props.modelValue, () => nextTick(resize))
watch(floated, () => nextTick(resize))
onMounted(resize)

const onInput = (e) => {
    emit('update:modelValue', e.target.value)
    resize()
}
</script>

<template>
    <div
        class="m3-textarea"
        :class="{ 'm3-textarea--error': !!error, 'm3-textarea--floated': floated }"
    >
        <div class="m3-textarea__container">
            <textarea
                :id="inputId"
                ref="textareaRef"
                :value="modelValue"
                :placeholder="floated ? placeholder : ''"
                :autocomplete="autocomplete"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="describedBy || undefined"
                rows="1"
                class="m3-textarea__input"
                @input="onInput"
                @focus="focused = true"
                @blur="focused = false"
            />
            <label :for="inputId" class="m3-textarea__label">{{ label }}</label>
        </div>
        <p v-if="error" :id="`${inputId}-error`" class="m3-textarea__supporting m3-textarea__supporting--error" role="alert">
            <svg class="m3-textarea__error-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
                <path stroke-linecap="round" d="M12 8v5" />
                <path stroke-linecap="round" d="M12 16.5h.01" />
            </svg>
            <span>{{ error }}</span>
        </p>
        <p v-else-if="hint" :id="`${inputId}-hint`" class="m3-textarea__supporting">{{ hint }}</p>
    </div>
</template>

<style scoped>
.m3-textarea {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    width: 100%;
}

.m3-textarea__container {
    position: relative;
    display: flex;
    align-items: stretch;
    min-height: var(--m3-size-text-field);
    padding: 0 var(--m3-space-4);
    background: var(--m3-color-surface-container-highest);
    border-radius: var(--m3-radius-xs) var(--m3-radius-xs) 0 0;
    transition: box-shadow var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-textarea__container:focus-within {
    box-shadow: inset 0 -2px 0 0 var(--m3-color-primary);
}

.m3-textarea__input {
    flex: 1;
    min-width: 0;
    width: 100%;
    min-height: var(--m3-size-text-field);
    max-height: 10rem;
    padding: var(--m3-space-4) 0;
    border: none;
    background: transparent;
    color: var(--m3-color-on-surface);
    font-family: inherit;
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
    letter-spacing: var(--m3-type-body-large-tracking);
    outline: none;
    box-shadow: none;
    resize: none;
    overflow-y: auto;
    appearance: none;
    transition: padding-top var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-textarea--floated .m3-textarea__input {
    padding-top: var(--m3-space-6);
}

.m3-textarea__input::placeholder {
    color: var(--m3-color-on-surface-variant);
    opacity: 1;
}

.m3-textarea__label {
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

.m3-textarea--floated .m3-textarea__label {
    top: var(--m3-space-2);
    transform: none;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
}

.m3-textarea__container:focus-within .m3-textarea__label {
    color: var(--m3-color-primary);
}

.m3-textarea--error .m3-textarea__container {
    box-shadow: inset 0 -2px 0 0 var(--m3-color-error);
}

.m3-textarea--error .m3-textarea__label,
.m3-textarea--error .m3-textarea__container:focus-within .m3-textarea__label {
    color: var(--m3-color-error);
}

.m3-textarea__supporting {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1);
    margin: 0;
    padding: var(--m3-space-1) var(--m3-space-4) 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    font-weight: var(--m3-type-body-small-weight);
}

.m3-textarea__supporting--error {
    color: var(--m3-color-error);
}

.m3-textarea__error-icon {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    flex-shrink: 0;
}
</style>
