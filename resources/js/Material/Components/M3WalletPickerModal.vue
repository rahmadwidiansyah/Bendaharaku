<script setup>
/**
 * M3WalletPickerModal — pemilih dompet Material Design 3 Expressive.
 * Daftar (urut frekuensi pemakaian dari useTransactionForm) dengan ikon,
 * nama, dan saldo. Baris terpilih ditandai centang + warna `primary`.
 * Presentasi: dialog di desktop, bottom sheet di mobile (M3Modal).
 */
import AppIcon from '@/Components/AppIcon.vue'
import M3Modal from '@/Material/Components/M3Modal.vue'
import { getM3WalletIconClass } from '@/Material/utils/m3IconColor.js'

defineProps({
    show: Boolean,
    wallets: { type: Array, default: () => [] },
    selectedId: { type: [Number, String], default: null },
    title: { type: String, default: '' },
})

const emit = defineEmits(['select', 'close'])

const formatBalance = (value) => new Intl.NumberFormat('id-ID').format(value)
</script>

<template>
    <M3Modal
        :show="show"
        :title="title || $t('transaction.chooseWallet')"
        variant="dialog"
        mobile-variant="sheet"
        max-width="sm"
        @close="emit('close')"
    >
        <div v-if="wallets.length" class="m3-wpm">
            <button
                v-for="w in wallets"
                :key="w.id"
                type="button"
                :class="['m3-wpm__row', selectedId === w.id ? 'm3-wpm__row--selected' : '']"
                :aria-pressed="selectedId === w.id"
                @click="emit('select', w)"
            >
                <span class="m3-wpm__icon" aria-hidden="true">
                    <AppIcon :icon="w.icon" fallback="wallet" :class="getM3WalletIconClass()" />
                </span>
                <span class="m3-wpm__body">
                    <span class="m3-wpm__name">{{ w.name }}</span>
                    <span class="m3-wpm__balance">Rp {{ formatBalance(w.balance) }}</span>
                </span>
                <svg
                    v-if="selectedId === w.id"
                    class="m3-wpm__check"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="3"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </button>
        </div>
        <p v-else class="m3-wpm__empty">{{ $t('transaction.noWallet') }}</p>
    </M3Modal>
</template>

<style scoped>
.m3-wpm {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-wpm__row {
    position: relative;
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    width: 100%;
    min-height: var(--m3-size-touch-target-lg);
    padding: var(--m3-space-2) var(--m3-space-3);
    border: none;
    border-radius: var(--m3-radius-md);
    background: transparent;
    color: var(--m3-color-on-surface);
    font-family: inherit;
    text-align: left;
    cursor: pointer;
    overflow: hidden;
    transition: background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-wpm__row::after {
    content: '';
    position: absolute;
    inset: 0;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-wpm__row:hover::after {
    opacity: 0.08;
}

.m3-wpm__row:active::after {
    opacity: 0.1;
}

.m3-wpm__row:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: -2px;
}

.m3-wpm__row--selected {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-wpm__row--selected::after {
    display: none;
}

.m3-wpm__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-highest);
}

.m3-wpm__icon :deep(svg) {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-wpm__body {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
}

.m3-wpm__name {
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
    font-weight: var(--m3-type-body-large-weight);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-wpm__balance {
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
    font-variant-numeric: tabular-nums;
}

.m3-wpm__row--selected .m3-wpm__balance {
    color: inherit;
}

.m3-wpm__check {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
    flex-shrink: 0;
}

.m3-wpm__empty {
    margin: 0;
    padding: var(--m3-space-5) 0;
    text-align: center;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}
</style>
