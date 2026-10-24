<script setup>
/**
 * ToggleSwitch — switch tema Default.
 * Menggantikan markup toggle (input.sr-only peer + track) yang sebelumnya
 * diduplikasi manual di halaman-halaman Settings.
 *
 * Aksesibilitas: input checkbox asli tetap jadi sumber state (keyboard
 * Space bekerja), ditambah role="switch" + aria-checked.
 */
defineProps({
    modelValue: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    ariaLabel: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'change'])
</script>

<template>
    <label class="relative inline-flex items-center cursor-pointer" :class="disabled ? 'opacity-60 cursor-not-allowed' : ''">
        <input
            type="checkbox"
            class="sr-only peer"
            role="switch"
            :aria-checked="modelValue"
            :checked="modelValue"
            :disabled="disabled"
            :aria-label="ariaLabel || undefined"
            @change="emit('update:modelValue', $event.target.checked); emit('change', $event)"
        />
        <div class="w-11 h-6 bg-[var(--color-surface-muted)] peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[var(--color-brand)] rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--color-brand)]"></div>
    </label>
</template>
