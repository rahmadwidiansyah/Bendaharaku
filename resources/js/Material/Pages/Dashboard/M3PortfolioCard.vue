<script setup>
/**
 * M3PortfolioCard — hero card Dashboard M3 (total kekayaan + dompet + hutang/piutang).
 * Perilaku identik PortfolioCard Default: props, emit, localStorage keys,
 * route tujuan, dan util format yang sama. Hanya presentasi yang memakai
 * token `--m3-*`. Hero: elevated card + headline-medium emphasized.
 */
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Link } from '@inertiajs/vue3'
import { formatNumber } from '@/utils/format.js'
import AppIcon from '@/Components/AppIcon.vue'
import { getM3WalletIconClass } from '@/Material/utils/m3IconColor.js'
import M3Card from '@/Material/Components/M3Card.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'

const { t } = useI18n()

const props = defineProps({
    totalPortfolio: { type: Number, default: 0 },
    isVisible: { type: Boolean, default: true },
    wallets: { type: Array, default: () => [] },
    totalHutang: { type: Number, default: 0 },
    totalPiutang: { type: Number, default: 0 },
    walletsExpanded: { type: Boolean, default: false },
})

defineEmits(['toggle-visibility'])

const displayAmount = (n) => props.isVisible ? formatNumber(n) : '••••••••'

const collapsedSections = ref({
    wallets: !props.walletsExpanded,
    hutang: true,
    piutang: true,
})
const hutangVisible = ref(localStorage.getItem('dashboard_hutang_visible') !== 'false')
const piutangVisible = ref(localStorage.getItem('dashboard_piutang_visible') !== 'false')

watch(hutangVisible, (val) => {
    localStorage.setItem('dashboard_hutang_visible', String(val))
})

watch(piutangVisible, (val) => {
    localStorage.setItem('dashboard_piutang_visible', String(val))
})

const toggleSection = (key) => {
    collapsedSections.value[key] = !collapsedSections.value[key]
}
const displayLocal = (n, visible) => visible ? formatNumber(n) : '••••••••'
</script>

<template>
    <M3Card variant="elevated" padding="md" class="m3-portfolio">
        <div class="m3-portfolio__head">
            <p class="m3-portfolio__eyebrow">{{ $t('portfolio.title') }}</p>
            <M3IconButton
                :aria-label="$t('header.toggleBalance')"
                @click="$emit('toggle-visibility')"
            >
                <svg v-if="isVisible" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                </svg>
            </M3IconButton>
        </div>

        <div class="m3-portfolio__total">
            <span class="m3-portfolio__currency">Rp</span>
            <h2 class="m3-portfolio__amount" aria-live="polite">{{ displayAmount(totalPortfolio) }}</h2>
        </div>

        <!-- Wallet list -->
        <div v-if="wallets && wallets.length > 0" class="m3-portfolio__section">
            <button type="button" class="m3-section-toggle" @click="toggleSection('wallets')" :aria-expanded="!collapsedSections.wallets">
                <svg class="m3-chevron" :class="!collapsedSections.wallets ? 'm3-chevron--open' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                <span class="m3-section-toggle__label">{{ $t('wallet.title') }} ({{ wallets.length }})</span>
            </button>
            <div class="m3-collapse" :style="{ gridTemplateRows: collapsedSections.wallets ? '0fr' : '1fr' }">
                <div class="m3-collapse__inner" :class="collapsedSections.wallets ? 'm3-collapse__inner--hidden' : ''">
                    <div class="m3-portfolio__wallets">
                        <Link
                            v-for="wallet in wallets"
                            :key="wallet.id"
                            :href="route('wallets.show', wallet.id)"
                            class="m3-wallet-row"
                        >
                            <AppIcon :icon="wallet.icon" fallback="wallet" :class="['m3-wallet-ico', getM3WalletIconClass()]" />
                            <span class="m3-wallet-row__name">{{ wallet.name }}</span>
                            <span
                                class="m3-wallet-row__balance"
                                :class="parseFloat(wallet.balance) < 0 ? 'm3-wallet-row__balance--negative' : ''"
                            >Rp {{ isVisible ? formatNumber(wallet.balance) : '••••' }}</span>
                        </Link>
                        <Link :href="route('wallets.create')" class="m3-wallet-add">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            {{ $t('wallet.addNew') }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hutang -->
        <div v-if="totalHutang > 0" class="m3-portfolio__section">
            <button type="button" class="m3-section-toggle" @click="toggleSection('hutang')" :aria-expanded="!collapsedSections.hutang">
                <svg class="m3-chevron" :class="!collapsedSections.hutang ? 'm3-chevron--open' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                <span class="m3-section-toggle__label">{{ $t('wallet.totalDebt') }}</span>
                <span class="m3-section-toggle__amount m3-section-toggle__amount--debt">Rp {{ displayLocal(totalHutang, hutangVisible) }}</span>
            </button>
            <div class="m3-collapse" :style="{ gridTemplateRows: collapsedSections.hutang ? '0fr' : '1fr' }">
                <div class="m3-collapse__inner" :class="collapsedSections.hutang ? 'm3-collapse__inner--hidden' : ''">
                    <Link :href="route('loans.index', { type: 'hutang' })" class="m3-debt-link m3-debt-link--debt">
                        <span>{{ $t('wallet.viewDebtDetail') }}</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Piutang -->
        <div v-if="totalPiutang > 0" class="m3-portfolio__section">
            <button type="button" class="m3-section-toggle" @click="toggleSection('piutang')" :aria-expanded="!collapsedSections.piutang">
                <svg class="m3-chevron" :class="!collapsedSections.piutang ? 'm3-chevron--open' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                <span class="m3-section-toggle__label">{{ $t('wallet.totalReceivable') }}</span>
                <span class="m3-section-toggle__amount m3-section-toggle__amount--receivable">Rp {{ displayLocal(totalPiutang, piutangVisible) }}</span>
            </button>
            <div class="m3-collapse" :style="{ gridTemplateRows: collapsedSections.piutang ? '0fr' : '1fr' }">
                <div class="m3-collapse__inner" :class="collapsedSections.piutang ? 'm3-collapse__inner--hidden' : ''">
                    <Link :href="route('loans.index', { type: 'piutang' })" class="m3-debt-link m3-debt-link--receivable">
                        <span>{{ $t('wallet.viewReceivableDetail') }}</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>
    </M3Card>
</template>

<style scoped>
.m3-portfolio__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.m3-portfolio__eyebrow {
    margin: 0;
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
}

.m3-portfolio__total {
    display: flex;
    align-items: baseline;
    gap: var(--m3-space-2);
    margin-bottom: var(--m3-space-3);
    min-width: 0;
}

.m3-portfolio__currency {
    font-size: var(--m3-type-title-small-size);
    color: var(--m3-color-on-surface-variant);
    flex-shrink: 0;
}

.m3-portfolio__amount {
    margin: 0;
    min-width: 0;
    font-size: var(--m3-type-headline-medium-size);
    line-height: var(--m3-type-headline-medium-line-height);
    font-weight: var(--m3-type-headline-medium-weight);
    letter-spacing: var(--m3-type-headline-medium-tracking);
    color: var(--m3-color-on-surface);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-portfolio__section {
    border-top: 1px solid var(--m3-color-outline-variant);
    padding-top: 0.25rem;
}

.m3-section-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.5rem 0;
    background: none;
    border: none;
    color: var(--m3-color-on-surface-variant);
    cursor: pointer;
    text-align: left;
}

.m3-section-toggle__label {
    font-size: var(--m3-type-label-medium-size);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-section-toggle__amount {
    font-size: var(--m3-type-title-small-size);
    font-weight: var(--m3-type-emphasized-title-small-weight);
}

.m3-section-toggle__amount--debt {
    color: var(--m3-color-debt);
}

.m3-section-toggle__amount--receivable {
    color: var(--m3-color-receivable);
}

.m3-chevron {
    width: 0.875rem;
    height: 0.875rem;
    flex-shrink: 0;
    transition: transform var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-chevron--open {
    transform: rotate(90deg);
}

.m3-collapse {
    display: grid;
    transition: grid-template-rows var(--m3-motion-default-spatial-duration) var(--m3-motion-default-spatial);
}

.m3-collapse__inner {
    overflow: hidden;
    transition: opacity var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-collapse__inner--hidden {
    opacity: 0;
}

.m3-portfolio__wallets {
    display: flex;
    flex-direction: column;
    padding: var(--m3-space-1) 0;
}

.m3-wallet-row {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-2) 0;
    border-bottom: 1px solid var(--m3-color-outline-variant);
    text-decoration: none;
}

.m3-wallet-row:last-child {
    border-bottom: none;
}

.m3-wallet-ico {
    width: 1.125rem;
    height: 1.125rem;
    flex-shrink: 0;
}

.m3-wallet-row__name {
    flex: 1;
    min-width: 0;
    font-size: var(--m3-type-body-small-size);
    font-weight: 500;
    color: var(--m3-color-on-surface-variant);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-wallet-row__balance {
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    color: var(--m3-color-on-surface);
    flex-shrink: 0;
    white-space: nowrap;
}

.m3-wallet-row__balance--negative {
    color: var(--m3-color-expense);
}

.m3-wallet-add {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.5rem;
    border-radius: var(--m3-radius-sm);
    border: 1px dashed var(--m3-color-outline);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-medium-size);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
    text-decoration: none;
}

.m3-wallet-add svg {
    width: 0.875rem;
    height: 0.875rem;
}

.m3-debt-link {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-2) 0;
    margin: var(--m3-space-1) 0;
    font-size: var(--m3-type-body-small-size);
    font-weight: 500;
    text-decoration: none;
}

.m3-debt-link svg {
    width: 0.875rem;
    height: 0.875rem;
    flex-shrink: 0;
}

.m3-debt-link span {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-debt-link--debt {
    color: var(--m3-color-on-debt-container);
}

.m3-debt-link--receivable {
    color: var(--m3-color-on-receivable-container);
}
</style>
