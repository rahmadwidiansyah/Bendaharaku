<script setup>
/**
 * M3SelectSheet — pemilih satu nilai dengan M3Modal.
 *
 * Di desktop tampil sebagai dialog; di layar <1024px otomatis menjadi
 * bottom sheet. Opsi memakai baris besar agar ramah sentuh dan screen reader.
 */
import M3Modal from '@/Material/Components/M3Modal.vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'close'])

function choose(value) {
    emit('update:modelValue', value)
    emit('close')
}
</script>

<template>
    <M3Modal
        :show="props.show"
        :title="props.title"
        max-width="sm"
        mobile-variant="sheet"
        @close="$emit('close')"
    >
        <div class="m3-select-sheet" role="listbox" :aria-label="props.title">
            <button
                v-for="option in props.options"
                :key="option.value"
                type="button"
                role="option"
                :aria-selected="option.value === props.modelValue"
                :class="['m3-select-sheet__option', option.value === props.modelValue ? 'm3-select-sheet__option--selected' : '']"
                @click="choose(option.value)"
            >
                <span class="m3-select-sheet__text">
                    <span class="m3-select-sheet__label">{{ option.label }}</span>
                    <span v-if="option.description" class="m3-select-sheet__desc">{{ option.description }}</span>
                </span>
                <svg
                    v-if="option.value === props.modelValue"
                    class="m3-select-sheet__check"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </button>
        </div>
    </M3Modal>
</template>

<style scoped>
.m3-select-sheet {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-select-sheet__option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-4);
    width: 100%;
    min-height: var(--m3-size-touch-target-lg);
    padding: var(--m3-space-3) var(--m3-space-4);
    border: none;
    border-radius: var(--m3-radius-md);
    background: transparent;
    color: var(--m3-color-on-surface);
    text-align: start;
    cursor: pointer;
    transition: background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-select-sheet__option:hover {
    background: var(--m3-color-surface-container-highest);
}

.m3-select-sheet__option:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: -2px;
}

.m3-select-sheet__option--selected {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-select-sheet__text {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-0-5);
    min-width: 0;
}

.m3-select-sheet__label {
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
    font-weight: var(--m3-type-body-large-weight);
}

.m3-select-sheet__desc {
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-select-sheet__option--selected .m3-select-sheet__desc {
    color: var(--m3-color-on-secondary-container);
}

.m3-select-sheet__check {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
    flex-shrink: 0;
    color: var(--m3-color-primary);
}

.m3-select-sheet__option--selected .m3-select-sheet__check {
    color: var(--m3-color-on-secondary-container);
}
</style>
