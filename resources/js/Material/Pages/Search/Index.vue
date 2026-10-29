<script setup>
/**
 * Material/Pages/Search/Index.vue
 *
 * Halaman pencarian global, tema M3. Mobile-first mengikuti spesifikasi
 * Search (input.md): search bar mandiri 56dp (surface-container-high,
 * radius full, leading icon search, hint text, trailing clear + eye),
 * hasil dalam list compact berdivider per grup.
 *
 * Leading judul memakai tombol kembali ke halaman asal (goBack util,
 * fallback Dashboard) — layar pencarian M3 tanpa navigation bar sendiri
 * membawa aksi navigasi pada leading edge.
 */
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3CircularProgress from '@/Material/Components/M3CircularProgress.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3TransactionDetailModal from '@/Material/Components/M3TransactionDetailModal.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { goBack } from '@/Material/utils/goBack.js'
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility'
import { useGlobalSearch } from '@/Composables/useGlobalSearch'
import { getM3CategoryIconClass, getM3WalletIconClass } from '@/Material/utils/m3IconColor.js'
import { formatNumber } from '@/utils/format.js'

const { t } = useI18n()
const { isBalanceVisible, toggleVisibility } = useBalanceVisibility()

const props = defineProps({
    query: { type: String, default: '' },
})

const {
    searchQuery,
    loading,
    error,
    grouped,
    searched,
    showNoResults,
    showModal,
    selectedTransaction,
    doSearch,
    onQueryInput,
    openTransaction,
    closeTransaction,
    displayTitle,
    getWalletName,
    getTypeName,
    amountTone,
    amountPrefix,
    groupMeta,
} = useGlobalSearch(props.query)

// Judul tab dinamis: "Hasil Pencarian · {query}" saat ada query (meniru
// Default UI), "Pencarian" saat kosong.
const pageTitle = computed(() => {
    const q = searchQuery.value.trim()
    return q ? `${t('search.results')} · ${q}` : t('nav.search')
})

const searchInputRef = ref(null)

// Clear: kosongkan input dan kembalikan UI ke keadaan intro
// (resetSearch dipanggil oleh onQueryInput untuk nilai kosong), lalu fokus kembali.
const clearQuery = () => {
    onQueryInput('')
    searchInputRef.value?.focus()
}
</script>

<template>
    <M3Layout :title="pageTitle">
        <Head :title="pageTitle" />

        <div class="m3-search" :aria-busy="loading">
            <M3PageTitle :title="t('nav.search')" :subtitle="t('search.subtitle')">
                <template #leading>
                    <M3IconButton :aria-label="t('common.back')" @click="goBack()">
                        <AppIcon icon="chevron-left" />
                    </M3IconButton>
                </template>
            </M3PageTitle>

            <!-- Search bar M3: 56dp, surface-container-high, radius full.
                 leading = search icon (dekoratif); trailing = clear (muncul
                 saat ada teks) + eye. Enter / tombol "search" keyboard
                 menjalankan pencarian. -->
            <form class="m3-search__bar" role="search" @submit.prevent="doSearch">
                <AppIcon icon="search" class="m3-search__bar-leading" />

                <input
                    ref="searchInputRef"
                    :value="searchQuery"
                    class="m3-search__bar-input"
                    type="search"
                    inputmode="search"
                    enterkeyhint="search"
                    autocomplete="off"
                    :placeholder="t('search.hint')"
                    :aria-label="t('common.search')"
                    @input="onQueryInput($event.target.value)"
                />

                <M3IconButton
                    v-if="searchQuery.length"
                    :aria-label="t('search.clear')"
                    @click="clearQuery"
                >
                    <AppIcon icon="x" />
                </M3IconButton>

                <M3IconButton
                    :aria-label="t('header.toggleBalance')"
                    @click="toggleVisibility"
                >
                    <AppIcon :icon="isBalanceVisible ? 'eye' : 'eye-off'" />
                </M3IconButton>
            </form>

            <div v-if="loading" class="m3-search__status" role="status">
                <M3CircularProgress size="md" />
                <span class="m3-search__status-text">{{ t('common.loading') }}</span>
            </div>

            <M3Card
                v-else-if="error"
                variant="filled"
                padding="lg"
                class="m3-search__state-card"
                role="alert"
            >
                <div class="m3-search__state">
                    <span class="m3-search__state-icon m3-search__state-icon--error" aria-hidden="true">
                        <AppIcon icon="circle-alert" />
                    </span>
                    <p class="m3-search__state-title">{{ error }}</p>
                </div>
            </M3Card>

            <M3Card
                v-else-if="showNoResults"
                variant="filled"
                padding="lg"
                class="m3-search__state-card"
                role="status"
            >
                <div class="m3-search__state">
                    <span class="m3-search__state-icon" aria-hidden="true">
                        <AppIcon icon="search-x" />
                    </span>
                    <p class="m3-search__state-title">
                        {{ t('search.noResults') }}
                        <span class="m3-search__state-query">"{{ searchQuery }}"</span>
                    </p>
                    <p class="m3-search__state-desc">{{ t('search.noResultsHint') }}</p>
                </div>
            </M3Card>

            <!-- Keadaan awal (belum mencari): flat tanpa card, dekat search bar -->
            <div v-else-if="!searched" class="m3-search__state m3-search__state--intro">
                <span class="m3-search__state-icon" aria-hidden="true">
                    <AppIcon icon="search" />
                </span>
                <p class="m3-search__state-title">{{ t('search.introTitle') }}</p>
                <p class="m3-search__state-desc">{{ t('nav.searchDesc') }}</p>
            </div>

            <div v-else class="m3-search__results">
                <section
                    v-for="(items, type) in grouped"
                    :key="type"
                    class="m3-search__group"
                    :aria-label="groupMeta(type).label"
                >
                    <header class="m3-search__group-head">
                        <span class="m3-search__group-dot" aria-hidden="true"></span>
                        <h2 class="m3-search__group-title">{{ groupMeta(type).label }}</h2>
                        <span class="m3-search__group-count">{{ items.length }}</span>
                    </header>

                    <M3Card variant="outlined" padding="none" class="m3-search__list">
                        <template v-for="(item, index) in items" :key="item.id">
                            <M3ListItem
                                v-if="type !== 'Transaksi'"
                                :href="item.route"
                                :divider="index < items.length - 1"
                            >
                                <template #icon>
                                    <AppIcon
                                        :icon="item.icon"
                                        fallback="search"
                                        class="m3-search__row-icon"
                                        :class="type === 'Wallet' ? getM3WalletIconClass() : 'm3-ico--plain'"
                                    />
                                </template>

                                <template #supporting v-if="item.description">
                                    {{ item.description }}
                                </template>

                                <template #trailing>
                                    <AppIcon icon="chevron-right" class="m3-search__chevron" />
                                </template>

                                {{ item.label }}
                            </M3ListItem>

                            <M3ListItem
                                v-else
                                clickable
                                :divider="index < items.length - 1"
                                :title="item.notes || undefined"
                                @click="openTransaction(item)"
                            >
                                <template #icon>
                                    <AppIcon
                                        :icon="item.category?.icon"
                                        fallback="file-text"
                                        class="m3-search__row-icon"
                                        :class="getM3CategoryIconClass(item.transaction_type?.name)"
                                    />
                                </template>

                                <template #supporting>
                                    <span class="m3-search__meta">
                                        <span class="m3-search__meta-item">
                                            {{ item.category?.category_name || getTypeName(item.transaction_type?.name) }}
                                        </span>
                                        <template v-if="getWalletName(item)">
                                            <span class="m3-search__meta-sep" aria-hidden="true">·</span>
                                            <span class="m3-search__meta-item">{{ getWalletName(item) }}</span>
                                        </template>
                                        <span v-if="item.is_draft" class="m3-search__draft">
                                            {{ t('transaction.draft') }}
                                        </span>
                                    </span>
                                </template>

                                <template #trailing>
                                    <span class="m3-search__amount" :class="`m3-search__amount--${amountTone(item)}`">
                                        <template v-if="isBalanceVisible">
                                            <span v-if="amountPrefix(item)" class="m3-search__amount-sign">{{ amountPrefix(item) }}</span>
                                            <span class="m3-search__amount-currency">{{ t('common.currency') }}</span>
                                            <span class="m3-search__amount-value">{{ formatNumber(item.amount) }}</span>
                                        </template>
                                        <span v-else class="m3-search__amount-value">••••</span>
                                    </span>
                                </template>

                                {{ displayTitle(item) }}
                            </M3ListItem>
                        </template>
                    </M3Card>
                </section>
            </div>
        </div>

        <M3TransactionDetailModal
            :show="showModal"
            :transaction="selectedTransaction"
            @close="closeTransaction"
        />
    </M3Layout>
</template>

<style scoped>
/* Container: padding inset + max-width 40rem (list) — header judul kini
   punya bleed tepi yang valid dan hasil tidak melebar liar di desktop. */
.m3-search {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
    width: 100%;
    max-width: var(--m3-size-list-max);
    margin: 0 auto;
    padding: var(--m3-density-page-inset, var(--m3-space-4));
}

/* ── Search bar (spesifikasi Search, input.md) ──────────────────────
   56dp tinggi, container surface-container-high (≥2 tone di atas
   surface), radius full, padding leading 12–16dp. */
.m3-search__bar {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1);
    min-width: 0;
    height: var(--m3-size-text-field);
    padding: 0 var(--m3-space-4) 0 var(--m3-space-3);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
}

.m3-search__bar-leading {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
    flex-shrink: 0;
    color: var(--m3-color-on-surface-variant);
}

.m3-search__bar-input {
    flex: 1;
    min-width: 0;
    height: 100%;
    padding: 0;
    border: none;
    background: transparent;
    color: var(--m3-color-on-surface);
    font-family: inherit;
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
    caret-color: var(--m3-color-primary);
}

.m3-search__bar-input::placeholder {
    color: var(--m3-color-on-surface-variant);
}

.m3-search__bar-input:focus {
    outline: none;
}

.m3-search__bar-input:focus-visible {
    outline: none;
}

/* Native X hapus disembunyikan — clear icon M3 yang tampil di trailing. */
.m3-search__bar-input::-webkit-search-cancel-button {
    display: none;
    -webkit-appearance: none;
    appearance: none;
}

/* ── Status memuat ── */
.m3-search__status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-8) 0;
    color: var(--m3-color-primary);
}

.m3-search__status-text {
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    font-weight: var(--m3-type-body-medium-weight);
    color: var(--m3-color-on-surface-variant);
}

/* ── State: ikon bulat 48dp + judul emphasized + desc ──
   Pola identik halaman list lain (Transactions/Loans). */
.m3-search__state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-3);
    text-align: center;
}

.m3-search__state-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface-variant);
}

.m3-search__state-icon :deep(svg),
.m3-search__state-icon svg {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-search__state-icon--error {
    color: var(--m3-color-error);
}

.m3-search__state-title {
    margin: 0;
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    letter-spacing: var(--m3-type-title-medium-tracking);
    color: var(--m3-color-on-surface);
}

.m3-search__state-query {
    color: var(--m3-color-on-surface-variant);
}

.m3-search__state-desc {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    font-weight: var(--m3-type-body-medium-weight);
    color: var(--m3-color-on-surface-variant);
}

/* Intro (belum mencari): flat, tanpa card, ritme dekat search bar */
.m3-search__state--intro {
    padding: var(--m3-space-6) var(--m3-space-2);
}

/* ── Hasil ── */
.m3-search__results {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-6);
    min-width: 0;
}

.m3-search__group {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-search__group-head {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    padding: 0 var(--m3-space-1);
    min-width: 0;
}

.m3-search__group-dot {
    width: var(--m3-space-2);
    height: var(--m3-space-2);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-primary);
}

.m3-search__group-title {
    margin: 0;
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-emphasized-label-large-weight);
    letter-spacing: var(--m3-type-label-large-tracking);
    color: var(--m3-color-on-surface-variant);
}

.m3-search__group-count {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    color: var(--m3-color-on-surface-variant);
}

.m3-search__row-icon {
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
}

.m3-search__chevron {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
    color: var(--m3-color-on-surface-variant);
}

.m3-search__meta {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1);
    min-width: 0;
}

.m3-search__meta-item {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-search__meta-sep {
    flex-shrink: 0;
}

.m3-search__draft {
    flex-shrink: 0;
    padding: 0 var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
}

.m3-search__amount {
    display: inline-flex;
    align-items: baseline;
    gap: var(--m3-space-0-5);
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-emphasized-label-large-weight);
    letter-spacing: var(--m3-type-label-large-tracking);
    white-space: nowrap;
}

.m3-search__amount--income {
    color: var(--m3-color-income);
}

.m3-search__amount--expense {
    color: var(--m3-color-expense);
}

.m3-search__amount--transfer {
    color: var(--m3-color-transfer);
}

.m3-search__amount-currency {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
}

.m3-search__amount-sign {
    font-variant-numeric: tabular-nums;
}

/* Desktop: hasil dipusatkan dengan inset halaman sedikit lebih lega */
@media (min-width: 64rem) {
    .m3-search {
        padding: var(--m3-space-6);
    }
}
</style>
