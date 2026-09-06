<script setup>
/**
 * TextInput.vue
 *
 * Input field reusable untuk seluruh form aplikasi.
 * Menggantikan pola hardcode yang diulang di Login, Register, Profile, dsb:
 *   "w-full bg-gradient-to-br from-gray-900 to-gray-800 border text-white rounded-xl
 *    p-4 text-sm focus:outline-none focus:ring-1 transition-all"
 *
 * Fitur bawaan:
 *   - Error state (border merah + pesan error)
 *   - Hint text
 *   - Icon slot kiri/kanan
 *   - Semua native input type didukung
 *   - Accessible (aria-invalid, aria-describedby otomatis)
 *
 * Props:
 *   id          — ID unik input, wajib untuk aksesibilitas (wajib)
 *   modelValue  — Nilai v-model
 *   type        — Native input type: 'text' | 'email' | 'password' | 'number' |
 *                 'date' | 'tel' | 'url' | 'search' | 'textarea'
 *                 (default: 'text')
 *   placeholder — Placeholder teks (opsional)
 *   error       — Pesan error dari server/validasi (opsional)
 *   hint        — Teks petunjuk di bawah input (opsional)
 *   disabled    — Nonaktifkan input (default: false)
 *   required    — Native required attribute (default: false)
 *   rows        — Jumlah baris untuk textarea (default: 4)
 *   autocomplete— Native autocomplete attribute (opsional)
 *
 * Slots:
 *   icon-left   — Icon di dalam input sisi kiri (opsional)
 *   icon-right  — Icon di dalam input sisi kanan (opsional)
 *
 * Emits:
 *   update:modelValue — v-model binding
 *
 * Usage:
 *   <TextInput
 *     id="email"
 *     v-model="form.email"
 *     type="email"
 *     placeholder="email@contoh.com"
 *     :error="form.errors.email"
 *     autocomplete="email"
 *   />
 *
 *   <!-- Dengan icon -->
 *   <TextInput id="search" v-model="q" placeholder="Cari...">
 *     <template #icon-left>
 *       <SearchIcon class="w-4 h-4" />
 *     </template>
 *   </TextInput>
 *
 *   <!-- Textarea -->
 *   <TextInput id="notes" v-model="form.notes" type="textarea" :rows="3" />
 */

import { computed } from 'vue'

const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    modelValue: {
        type: [String, Number],
        default: '',
    },
    type: {
        type: String,
        default: 'text',
        validator: (v) => [
            'text', 'email', 'password', 'number', 'date',
            'tel', 'url', 'search', 'textarea',
        ].includes(v),
    },
    placeholder: {
        type: String,
        default: null,
    },
    error: {
        type: String,
        default: null,
    },
    hint: {
        type: String,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
    rows: {
        type: Number,
        default: 4,
    },
    autocomplete: {
        type: String,
        default: null,
    },
})

const emit = defineEmits(['update:modelValue'])

const isTextarea = computed(() => props.type === 'textarea')
const isDate = computed(() => props.type === 'date')

const errorId = computed(() => `${props.id}-error`)
const hintId = computed(() => `${props.id}-hint`)

// aria-describedby: gabungkan hint & error jika keduanya ada
const ariaDescribedBy = computed(() => {
    const ids = []
    if (props.hint) ids.push(hintId.value)
    if (props.error) ids.push(errorId.value)
    return ids.length ? ids.join(' ') : undefined
})

const baseInputClasses = computed(() => [
    'w-full bg-[var(--color-surface-raised)]',
    'text-[var(--color-text-primary)] rounded-xl transition-all duration-200',
    'focus:outline-none focus:ring-1',
    'placeholder:text-[var(--color-text-muted)]',
    'disabled:opacity-50 disabled:cursor-not-allowed',
    // Padding — date pakai py lebih kecil karena browser menambah native chrome
    isDate.value ? 'px-3 sm:px-4 py-2.5 sm:py-3 text-sm' : 'p-3 sm:p-4 text-sm',
    // Date input butuh color-scheme dark agar calendar picker dark
    isDate.value ? '[color-scheme:dark]' : '',
    // Padding kiri tambahan jika ada icon kiri
    props.$slots?.['icon-left'] ? 'pl-10' : '',
    // Error vs Normal border
    props.error
        ? 'border border-[var(--color-expense-text)] focus:border-[var(--color-expense-text)] focus:ring-[var(--color-expense-text)]'
        : 'border border-[var(--color-border-default)] focus:border-[var(--color-brand)] focus:ring-[var(--color-brand)]',
].filter(Boolean).join(' '))

const hasIconLeft = computed(() => false) // dihandle via slot check di template
const hasIconRight = computed(() => false)
</script>

<template>
    <div class="w-full">
        <!-- Wrapper posisi relatif untuk icon -->
        <div class="relative">
            <!-- Icon kiri -->
            <div
                v-if="$slots['icon-left']"
                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-[var(--color-text-muted)]"
                aria-hidden="true"
            >
                <slot name="icon-left" />
            </div>

            <!-- Textarea -->
            <textarea
                v-if="isTextarea"
                :id="id"
                :value="modelValue"
                :placeholder="placeholder ?? undefined"
                :disabled="disabled"
                :required="required"
                :rows="rows"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="ariaDescribedBy"
                :aria-required="required ? 'true' : undefined"
                :class="[
                    'w-full bg-[var(--color-surface-raised)]',
                    'text-[var(--color-text-primary)] rounded-xl transition-all duration-200 resize-none',
                    'focus:outline-none focus:ring-1',
                    'placeholder:text-[var(--color-text-muted)]',
                    'disabled:opacity-50 disabled:cursor-not-allowed',
                    'p-3 sm:p-4 text-sm',
                    $slots['icon-left'] ? 'pl-10' : '',
                    error
                        ? 'border border-[var(--color-expense-text)] focus:border-[var(--color-expense-text)] focus:ring-[var(--color-expense-text)]'
                        : 'border border-[var(--color-border-default)] focus:border-[var(--color-brand)] focus:ring-[var(--color-brand)]',
                ].filter(Boolean).join(' ')"
                @input="$emit('update:modelValue', $event.target.value)"
                v-bind="$attrs"
            />

            <!-- Input biasa -->
            <input
                v-else
                :id="id"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder ?? undefined"
                :disabled="disabled"
                :required="required"
                :autocomplete="autocomplete ?? undefined"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="ariaDescribedBy"
                :aria-required="required ? 'true' : undefined"
                :class="[
                    'w-full bg-[var(--color-surface-raised)]',
                    'text-[var(--color-text-primary)] rounded-xl transition-all duration-200',
                    'focus:outline-none focus:ring-1',
                    'placeholder:text-[var(--color-text-muted)]',
                    'disabled:opacity-50 disabled:cursor-not-allowed',
                    type === 'date' ? 'px-3 sm:px-4 py-2.5 sm:py-3 text-sm [color-scheme:dark]' : 'p-3 sm:p-4 text-sm',
                    $slots['icon-left'] ? 'pl-10' : '',
                    $slots['icon-right'] ? 'pr-10' : '',
                    error
                        ? 'border border-[var(--color-expense-text)] focus:border-[var(--color-expense-text)] focus:ring-[var(--color-expense-text)]'
                        : 'border border-[var(--color-border-default)] focus:border-[var(--color-brand)] focus:ring-[var(--color-brand)]',
                ].filter(Boolean).join(' ')"
                @input="$emit('update:modelValue', $event.target.value)"
                v-bind="$attrs"
            />

            <!-- Icon kanan -->
            <div
                v-if="$slots['icon-right']"
                class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-[var(--color-text-muted)]"
                aria-hidden="true"
            >
                <slot name="icon-right" />
            </div>
        </div>

        <!-- Hint text -->
        <p
            v-if="hint && !error"
            :id="hintId"
            class="text-2xs text-[var(--color-text-muted)] mt-1 ml-1"
        >
            {{ hint }}
        </p>

        <!-- Error message -->
        <p
            v-if="error"
            :id="errorId"
            role="alert"
            class="text-2xs text-[var(--color-expense-text)] mt-1 ml-1 font-bold"
        >
            {{ error }}
        </p>
    </div>
</template>
