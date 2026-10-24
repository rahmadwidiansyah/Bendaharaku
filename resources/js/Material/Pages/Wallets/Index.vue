<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility'
import { getM3WalletIconClass } from '@/Material/utils/m3IconColor.js'
import { formatNumber } from '@/utils/format.js'

const { t } = useI18n()
const { isBalanceVisible, toggleVisibility } = useBalanceVisibility()

const props = defineProps({
    wallets: { type: Array, default: () => [] },
    totalHutang: { type: Number, default: 0 },
    totalPiutang: { type: Number, default: 0 },
})

const liquidWallets = computed(() => props.wallets.filter((wallet) => wallet.group_type === 'Liquid'))
const assetWallets = computed(() => props.wallets.filter((wallet) => wallet.group_type === 'Asset'))

const totalLiquidBalance = computed(() =>
    liquidWallets.value.reduce((sum, wallet) => sum + parseFloat(wallet.balance), 0),
)
const totalAssetBalance = computed(() =>
    assetWallets.value.reduce((sum, wallet) => sum + parseFloat(wallet.balance), 0),
)
const totalBalance = computed(() => totalLiquidBalance.value + totalAssetBalance.value)

const displayAmount = (amount) => (isBalanceVisible.value ? formatNumber(amount) : '••••••••')

const balanceClass = (balance) =>
    parseFloat(balance) < 0 && isBalanceVisible.value ? 'm3-wallets__balance--negative' : ''

// ─── List-detail (layar lebar >= 100rem): pilih dompet → panel kanan ───
const selectedWallet = ref(null)
const WIDE_MQ = '(min-width: 100rem)'
const isWideLayout = ref(typeof window !== 'undefined' && window.matchMedia(WIDE_MQ).matches)
let wideMq = null
const onWideMqChange = (e) => { isWideLayout.value = e.matches }

onMounted(() => {
    wideMq = window.matchMedia(WIDE_MQ)
    isWideLayout.value = wideMq.matches
    wideMq.addEventListener('change', onWideMqChange)
})

onUnmounted(() => {
    wideMq?.removeEventListener('change', onWideMqChange)
})

// Di layout lebar, klik baris memilih dompet untuk panel kanan
// (href dikosongkan → M3ListItem merender div role=button, bukan link);
// di bawahnya baris tetap link ke halaman dompet.
const onWalletClick = (wallet) => {
    selectedWallet.value = selectedWallet.value?.id === wallet.id ? null : wallet
}

const selectedGroupLabel = computed(() =>
    selectedWallet.value?.group_type === 'Asset'
        ? t('wallet.groupTypes.asset')
        : t('wallet.groupTypes.liquid'),
)

const groups = computed(() => [
    {
        key: 'liquid',
        label: t('wallet.groupTypes.liquid'),
        empty: t('wallet.emptyLiquid'),
        wallets: liquidWallets.value,
    },
    {
        key: 'asset',
        label: t('wallet.groupTypes.asset'),
        empty: t('wallet.emptyAsset'),
        wallets: assetWallets.value,
    },
])
</script>

<template>
    <M3Layout :title="t('wallet.title')">
        <Head :title="t('wallet.title')" />

        <div class="m3-wallets">
          <div class="m3-wallets__primary">
            <M3PageTitle :title="t('wallet.title')" />

            <M3Card variant="elevated" padding="lg" class="m3-wallets__hero">
                <div class="m3-wallets__hero-head">
                    <p class="m3-wallets__eyebrow">{{ t('portfolio.title') }}</p>
                    <M3IconButton
                        :aria-label="t('header.toggleBalance')"
                        @click="toggleVisibility"
                    >
                        <AppIcon :icon="isBalanceVisible ? 'eye' : 'eye-off'" />
                    </M3IconButton>
                </div>

                <div class="m3-wallets__hero-body">
                    <p class="m3-wallets__hero-label">{{ t('wallet.totalWealth') }}</p>
                    <p class="m3-wallets__total" aria-live="polite">
                        <span class="m3-wallets__currency">{{ t('common.currency') }}</span>
                        {{ displayAmount(totalBalance) }}
                    </p>
                </div>

                <div class="m3-wallets__summary">
                    <Link :href="route('loans.index', { type: 'hutang' })" class="m3-wallets__summary-link">
                        <span class="m3-wallets__summary-label">{{ t('wallet.totalDebt') }}</span>
                        <span class="m3-wallets__summary-value m3-wallets__summary-value--debt">
                            {{ t('common.currency') }} {{ displayAmount(totalHutang) }}
                        </span>
                    </Link>
                    <Link :href="route('loans.index', { type: 'piutang' })" class="m3-wallets__summary-link">
                        <span class="m3-wallets__summary-label">{{ t('wallet.totalReceivable') }}</span>
                        <span class="m3-wallets__summary-value m3-wallets__summary-value--receivable">
                            {{ t('common.currency') }} {{ displayAmount(totalPiutang) }}
                        </span>
                    </Link>
                </div>
            </M3Card>

            <M3Button
                variant="tonal"
                size="lg"
                full-width
                :href="route('wallets.create')"
            >
                <template #leading>
                    <AppIcon icon="plus" />
                </template>
                {{ t('wallet.addNew') }}
            </M3Button>

            <div class="m3-wallets__groups">
                <section v-for="group in groups" :key="group.key" class="m3-wallets__group">
                    <header class="m3-wallets__group-head">
                        <span class="m3-wallets__group-dot" :class="`m3-wallets__group-dot--${group.key}`" aria-hidden="true" />
                        <h2 class="m3-wallets__group-title">{{ group.label }}</h2>
                        <span class="m3-wallets__group-count">{{ group.wallets.length }}</span>
                    </header>

                    <M3Card variant="outlined" padding="none">
                        <M3ListItem
                            v-for="(wallet, index) in group.wallets"
                            :key="wallet.id"
                            :href="isWideLayout ? '' : route('wallets.show', wallet.id)"
                            clickable
                            :divider="index < group.wallets.length - 1"
                            :class="{ 'm3-wallets__row--selected': isWideLayout && selectedWallet?.id === wallet.id }"
                            :aria-current="isWideLayout && selectedWallet?.id === wallet.id ? 'true' : undefined"
                            @click="onWalletClick(wallet)"
                        >
                            <template #icon>
                                <span class="m3-wallets__icon-wrap">
                                    <AppIcon
                                        :icon="wallet.icon"
                                        fallback="wallet"
                                        :class="['m3-wallets__icon', getM3WalletIconClass()]"
                                    />
                                </span>
                            </template>

                            {{ wallet.name }}

                            <template #trailing>
                                <span class="m3-wallets__balance" :class="balanceClass(wallet.balance)">
                                    <span class="m3-wallets__balance-currency">{{ t('common.currency') }}</span>
                                    {{ displayAmount(wallet.balance) }}
                                </span>
                            </template>
                        </M3ListItem>

                        <div v-if="group.wallets.length === 0" class="m3-wallets__empty">
                            <span class="m3-wallets__empty-icon-wrap" aria-hidden="true">
                                <AppIcon icon="wallet" class="m3-wallets__empty-icon" />
                            </span>
                            <p class="m3-wallets__empty-text">{{ group.empty }}</p>
                        </div>
                    </M3Card>
                </section>
            </div>
          </div>

          <!-- Panel ringkasan dompet (hanya layar lebar >= 100rem) -->
          <aside class="m3-wallets__detail" :aria-label="t('wallet.title')">
              <M3Card variant="elevated" padding="lg" class="m3-wallets__detail-card">
                  <template v-if="selectedWallet">
                      <div class="m3-wallets__detail-head">
                          <span class="m3-wallets__detail-icon">
                              <AppIcon
                                  :icon="selectedWallet.icon"
                                  fallback="wallet"
                                  :class="['m3-wallets__icon', getM3WalletIconClass()]"
                              />
                          </span>
                          <div class="m3-wallets__detail-titles">
                              <span class="m3-wallets__detail-group">{{ selectedGroupLabel }}</span>
                              <h2 class="m3-wallets__detail-name">{{ selectedWallet.name }}</h2>
                          </div>
                      </div>

                      <div class="m3-wallets__detail-balance">
                          <span class="m3-wallets__detail-balance-label">{{ t('wallet.totalWealth') }}</span>
                          <p class="m3-wallets__detail-balance-value" aria-live="polite">
                              <span class="m3-wallets__detail-balance-currency">{{ t('common.currency') }}</span>
                              {{ displayAmount(selectedWallet.balance) }}
                          </p>
                      </div>

                      <div class="m3-wallets__detail-actions">
                          <M3Button variant="filled" full-width :href="route('wallets.show', selectedWallet.id)">
                              <template #leading>
                                  <AppIcon icon="arrow-right" />
                              </template>
                              {{ t('wallet.openDetail') }}
                          </M3Button>
                          <M3Button variant="outlined" full-width :href="route('wallets.edit', selectedWallet.id)">
                              <template #leading>
                                  <AppIcon icon="pencil" />
                              </template>
                              {{ t('common.edit') }}
                          </M3Button>
                      </div>
                  </template>

                  <div v-else class="m3-wallets__detail-empty">
                      <span class="m3-wallets__empty-icon-wrap" aria-hidden="true">
                          <AppIcon icon="wallet" class="m3-wallets__empty-icon" />
                      </span>
                      <p class="m3-wallets__detail-empty-text">{{ t('wallet.selectHint') }}</p>
                  </div>
              </M3Card>
          </aside>
        </div>
    </M3Layout>
</template>

<style scoped>
.m3-wallets {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
    width: 100%;
    max-width: var(--m3-size-content-max);
    margin: 0 auto;
    padding: var(--m3-space-4);
}

.m3-wallets__hero {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
}

.m3-wallets__hero-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
}

.m3-wallets__eyebrow {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
}

.m3-wallets__hero-body {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-wallets__hero-label {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

.m3-wallets__total {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: var(--m3-space-2);
    margin: 0;
    max-width: 100%;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-headline-medium-size);
    line-height: var(--m3-type-headline-medium-line-height);
    font-weight: var(--m3-type-emphasized-headline-medium-weight);
    letter-spacing: var(--m3-type-headline-medium-tracking);
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
}

.m3-wallets__currency {
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-title-medium-weight);
}

.m3-wallets__summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--m3-space-2);
    padding-top: var(--m3-space-4);
    border-top: 1px solid var(--m3-color-outline-variant);
}

.m3-wallets__summary-link {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    min-width: 0;
    padding: var(--m3-space-3);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface);
    text-decoration: none;
    transition: background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-wallets__summary-link:hover {
    background: var(--m3-color-surface-container-highest);
}

.m3-wallets__summary-link:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-wallets__summary-label {
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-label-medium-weight);
}

.m3-wallets__summary-value {
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    font-variant-numeric: tabular-nums;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-wallets__summary-value--debt {
    color: var(--m3-color-debt);
}

.m3-wallets__summary-value--receivable {
    color: var(--m3-color-receivable);
}

.m3-wallets__groups {
    display: grid;
    gap: var(--m3-space-5);
    min-width: 0;
}

.m3-wallets__group {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-wallets__group-head {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    padding: 0 var(--m3-space-1);
}

.m3-wallets__group-dot {
    width: var(--m3-space-2);
    height: var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    flex-shrink: 0;
}

.m3-wallets__group-dot--liquid {
    background: var(--m3-color-transfer);
}

.m3-wallets__group-dot--asset {
    background: var(--m3-color-primary);
}

.m3-wallets__group-title {
    margin: 0;
    min-width: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-title-medium-weight);
    letter-spacing: var(--m3-type-title-medium-tracking);
}

.m3-wallets__group-count {
    margin-left: auto;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-label-medium-weight);
    font-variant-numeric: tabular-nums;
}

.m3-wallets__icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
}

.m3-wallets__icon {
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
}

.m3-wallets__balance {
    display: inline-flex;
    align-items: baseline;
    gap: var(--m3-space-1);
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.m3-wallets__balance--negative {
    color: var(--m3-color-expense);
}

.m3-wallets__balance-currency {
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
}

.m3-wallets__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-8) var(--m3-space-4);
    text-align: center;
}

.m3-wallets__empty-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface-variant);
}

.m3-wallets__empty-icon {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-wallets__empty-text {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

@media (max-width: 30rem) {
    .m3-wallets__summary {
        grid-template-columns: minmax(0, 1fr);
    }
}

@media (min-width: 64rem) {
    .m3-wallets__total {
        font-size: var(--m3-type-display-small-size);
        line-height: var(--m3-type-display-small-line-height);
        font-weight: var(--m3-type-emphasized-display-small-weight);
        letter-spacing: var(--m3-type-display-small-tracking);
    }

    .m3-wallets__groups {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: start;
    }
}

/* Kolom utama (hero + aksi + grup dompet). Wrapper tetap berperilaku
   seperti anak langsung .m3-wallets di bawah breakpoint list-detail. */
.m3-wallets__primary {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
    min-width: 0;
}

/* ── List-detail: layar lebar (>= 100rem / 1600px) ────────────────
   Klik baris dompet memilihnya untuk panel ringkasan kanan; di bawah
   breakpoint baris tetap navigasi ke halaman dompet. */
.m3-wallets__detail {
    display: none;
}

.m3-wallets__row--selected {
    background: var(--m3-color-secondary-container);
}

@media (min-width: 100rem) {
    .m3-wallets {
        display: grid;
        grid-template-columns: minmax(0, var(--m3-size-content-max)) 24rem;
        gap: var(--m3-space-6);
        justify-content: center;
        align-items: start;
        max-width: none;
    }

    .m3-wallets__primary {
        display: flex;
        flex-direction: column;
        gap: var(--m3-space-5);
        min-width: 0;
    }

    .m3-wallets__detail {
        display: block;
        position: sticky;
        top: calc(var(--m3-size-appbar) + var(--m3-space-4));
        max-height: calc(100dvh - var(--m3-size-appbar) - var(--m3-space-8));
        overflow-y: auto;
    }

    .m3-wallets__detail-card {
        display: flex;
        flex-direction: column;
    }
}

.m3-wallets__detail-head {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-wallets__detail-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container);
}

.m3-wallets__detail-titles {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-0-5);
    min-width: 0;
}

.m3-wallets__detail-group {
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    color: var(--m3-color-on-surface-variant);
}

.m3-wallets__detail-name {
    margin: 0;
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    color: var(--m3-color-on-surface);
    overflow-wrap: anywhere;
}

.m3-wallets__detail-balance {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    padding: var(--m3-space-4);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container);
}

.m3-wallets__detail-balance-label {
    margin: 0;
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
}

.m3-wallets__detail-balance-value {
    display: flex;
    align-items: baseline;
    gap: var(--m3-space-1);
    margin: 0;
    font-size: var(--m3-type-headline-medium-size);
    line-height: var(--m3-type-headline-medium-line-height);
    font-weight: var(--m3-type-emphasized-headline-medium-weight);
    letter-spacing: var(--m3-type-headline-medium-tracking);
    font-variant-numeric: tabular-nums;
    color: var(--m3-color-on-surface);
    overflow-wrap: anywhere;
}

.m3-wallets__detail-balance-currency {
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    color: var(--m3-color-on-surface-variant);
}

.m3-wallets__detail-actions {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-wallets__detail-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-3);
    padding: var(--m3-space-8) var(--m3-space-4);
    text-align: center;
}

.m3-wallets__detail-empty-text {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}
</style>
