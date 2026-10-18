<script setup>
/**
 * M3AmountKeypad — keypad numerik Material Design 3 Expressive.
 * Paritas perilaku dengan AmountKeypad Default (urutan tombol & nilai key
 * yang di-emit identik: '0'-'9', '000', 'del', 'C').
 *
 * Keputusan desain (di luar spec M3 — keypad angka tidak punya komponen
 * resmi): tombol `surface container high`, radius md, tinggi 3rem,
 * state layer currentColor; digit `title large`, ikon hapus
 * `on surface variant` (bukan merah — merah di aplikasi ini hanya untuk
 * pengeluaran).
 */
import { useI18n } from 'vue-i18n'

defineEmits(['key'])

const { t } = useI18n()

const keys = [
    '1', '2', '3',
    '4', '5', '6',
    '7', '8', '9',
    '000', '0', 'del',
]

const labelFor = (k) =>
    k === 'del'
        ? t('transaction.keypadDelete')
        : k === '000'
            ? t('transaction.keypadThousand')
            : `${t('transaction.keypadDigit')} ${k}`
</script>

<template>
    <div class="m3-keypad" role="group" :aria-label="t('transaction.keypadLabel')">
        <button
            v-for="k in keys"
            :key="k"
            type="button"
            class="m3-keypad__key"
            :aria-label="labelFor(k)"
            @click="$emit('key', k)"
        >
            <svg
                v-if="k === 'del'"
                class="m3-keypad__del"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M22 3H7L2 12l5 9h15a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z" />
                <line x1="17" y1="9" x2="11" y2="15" stroke-linecap="round" />
                <line x1="11" y1="9" x2="17" y2="15" stroke-linecap="round" />
            </svg>
            <span v-else class="m3-keypad__digit" :class="{ 'm3-keypad__digit--accent': k === '000' }">{{ k }}</span>
        </button>
    </div>
</template>

<style scoped>
.m3-keypad {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--m3-space-2) var(--m3-space-3);
}

.m3-keypad__key {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: var(--m3-size-touch-target-lg);
    border: none;
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface);
    cursor: pointer;
    overflow: hidden;
    transition: transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-keypad__key::after {
    content: '';
    position: absolute;
    inset: 0;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-keypad__key:hover::after {
    opacity: 0.08;
}

.m3-keypad__key:active::after {
    opacity: 0.12;
}

.m3-keypad__key:active {
    transform: scale(0.96);
}

.m3-keypad__key:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-keypad__digit {
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-title-large-weight);
}

.m3-keypad__digit--accent {
    color: var(--m3-color-primary);
}

.m3-keypad__del {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
    color: var(--m3-color-on-surface-variant);
}
</style>
