<script setup>
import { Head } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3CircularProgress from '@/Material/Components/M3CircularProgress.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3TextField from '@/Material/Components/M3TextField.vue'
import M3TransactionDetailModal from '@/Material/Components/M3TransactionDetailModal.vue'
import AppIcon from '@/Components/AppIcon.vue'
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
</script>

<template>
    <M3Layout :title="t('nav.search')">
        <Head :title="t('nav.search')" />

        <div class="m3-search" :aria-busy="loading">
            <M3PageTitle :title="t('nav.search')" />

            <M3Card variant="outlined" padding="sm" class="m3-search__form-card">
                <form class="m3-search__form" role="search" @submit.prevent="doSearch">
                    <div class="m3-search__field">
                        <M3TextField
                            :model-value="searchQuery"
                            :label="t('nav.search')"
                            :placeholder="t('common.search')"
                            type="search"
                            inputmode="search"
                            @update:model-value="onQueryInput"
                        />
                    </div>

                    <M3IconButton
                        type="submit"
                        variant="filled"
                        :aria-label="t('common.search')"
                    >
                        <AppIcon icon="arrow-right" />
                    </M3IconButton>

                    <M3IconButton
                        :aria-label="t('header.toggleBalance')"
                        @click="toggleVisibility"
                    >
                        <AppIcon :icon="isBalanceVisible ? 'eye' : 'eye-off'" />
                    </M3IconButton>
                </form>
            </M3Card>

            <div v-if="loading" class="m3-search__status" role="status">
                <M3CircularProgress size="md" />
                <span class="m3-search__status-text">{{ t('common.loading') }}</span>
            </div>

            <M3Card
                v-else-if="error"
                variant="outlined"
                padding="lg"
                class="m3-search__state"
                role="alert"
            >
                <AppIcon icon="circle-alert" class="m3-search__state-icon m3-search__state-icon--error" />
                <p class="m3-search__state-title">{{ error }}</p>
            </M3Card>

            <M3Card v-else-if="showNoResults" variant="outlined" padding="lg" class="m3-search__state" role="status">
                <AppIcon icon="search-x" class="m3-search__state-icon" />
                <p class="m3-search__state-title">
                    {{ t('search.noResults') }}
                    <span class="m3-search__state-query">"{{ searchQuery }}"</span>
                </p>
            </M3Card>

            <div v-else-if="!searched" class="m3-search__intro">
                <AppIcon icon="search" class="m3-search__intro-icon" />
                <p class="m3-search__intro-text">{{ t('nav.searchDesc') }}</p>
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
.m3-search {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
    min-width: 0;
}

.m3-search__form-card,
.m3-search__list {
    min-width: 0;
}

.m3-search__list {
    overflow: hidden;
}

.m3-search__form {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-search__field {
    flex: 1;
    min-width: 0;
}

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

.m3-search__state,
.m3-search__intro {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-2);
    text-align: center;
}

.m3-search__intro {
    padding: var(--m3-space-8) var(--m3-space-4);
}

.m3-search__state-icon,
.m3-search__intro-icon {
    width: var(--m3-size-icon-lg);
    height: var(--m3-size-icon-lg);
    color: var(--m3-color-on-surface-variant);
}

.m3-search__state-icon--error {
    color: var(--m3-color-error);
}

.m3-search__state-title,
.m3-search__intro-text {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    font-weight: var(--m3-type-body-medium-weight);
    color: var(--m3-color-on-surface-variant);
}

.m3-search__state-query {
    color: var(--m3-color-on-surface);
}

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

@media (max-width: 420px) {
    .m3-search__form {
        gap: var(--m3-space-1);
    }

    .m3-search__amount-currency {
        display: none;
    }
}
</style>
