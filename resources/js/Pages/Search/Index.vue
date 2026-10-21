<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from '@/Components/AppIcon.vue'
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility'
import { useGlobalSearch } from '@/Composables/useGlobalSearch'
import { formatNumber } from '@/utils/format.js'

const { t } = useI18n()
const { isBalanceVisible, toggleVisibility } = useBalanceVisibility()

const props = defineProps({
    query: { type: String, default: '' },
})

const {
    searchQuery,
    loading,
    searched,
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

const pageTitle = computed(() =>
    searchQuery.value.trim()
        ? `${t('search.results')} · ${searchQuery.value.trim()}`
        : t('nav.search'),
)

const amountClass = (item) => ({
    income: 'text-[var(--color-income-text)]',
    transfer: 'text-[var(--color-transfer-text)]',
    expense: 'text-[var(--color-expense-text)]',
}[amountTone(item)])

const displayAmount = (item) => (isBalanceVisible.value ? formatNumber(item.amount) : '••••')
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="pageTitle" />

        <div class="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto">
            <form class="relative mb-6 animate-fade-in-up" role="search" @submit.prevent="doSearch">
                <div class="flex items-center gap-2 sm:gap-3 bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-2xl px-3 sm:px-4 py-3 shadow-2xl shadow-black/50">
                    <svg class="w-5 h-5 text-[var(--color-text-secondary)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="8" />
                        <path d="M21 21l-4.35-4.35" />
                    </svg>

                    <input
                        :value="searchQuery"
                        type="search"
                        :placeholder="t('common.search')"
                        :aria-label="t('common.search')"
                        class="flex-1 min-w-0 bg-transparent text-[var(--color-text-primary)] text-sm sm:text-base placeholder:text-[var(--color-text-muted)] outline-none caret-[var(--color-brand)]"
                        autocomplete="off"
                        @input="onQueryInput($event.target.value)"
                        @keydown.enter.prevent="doSearch"
                    >

                    <button
                        type="button"
                        class="shrink-0 p-1.5 rounded-lg text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors active:scale-95"
                        :aria-label="t('header.toggleBalance')"
                        @click="toggleVisibility"
                    >
                        <AppIcon :icon="isBalanceVisible ? 'eye' : 'eye-off'" class="w-5 h-5" />
                    </button>

                    <button
                        type="submit"
                        class="shrink-0 w-8 h-8 flex items-center justify-center rounded-xl bg-[var(--color-brand)] text-white hover:bg-[var(--color-brand-hover)] transition-colors active:scale-95"
                        :aria-label="t('common.search')"
                    >
                        <AppIcon icon="arrow-right" class="w-4 h-4" />
                    </button>
                </div>
            </form>

            <div v-if="loading" class="flex items-center justify-center gap-3 py-12 sm:py-16" role="status">
                <svg class="w-6 h-6 text-[var(--color-brand)] animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <span class="sr-only">{{ t('common.loading') }}</span>
            </div>

            <div v-else-if="error" class="flex flex-col items-center gap-2 text-center py-12 sm:py-16 px-3 sm:px-4" role="alert">
                <AppIcon icon="circle-alert" class="w-6 h-6 text-[var(--color-expense-text)]" />
                <p class="text-[var(--color-text-muted)] text-sm sm:text-base">{{ error }}</p>
            </div>

            <div v-else-if="showNoResults" class="text-center py-12 sm:py-16 px-3 sm:px-4" role="status">
                <p class="text-[var(--color-text-muted)] text-sm sm:text-base">
                    {{ t('search.noResults') }}
                    <span class="text-[var(--color-text-secondary)]">"{{ searchQuery }}"</span>
                </p>
            </div>

            <div v-else-if="!searched" class="flex flex-col items-center gap-2 text-center py-12 sm:py-16 px-3 sm:px-4">
                <AppIcon icon="search" class="w-6 h-6 text-[var(--color-text-muted)]" />
                <p class="text-[var(--color-text-muted)] text-sm sm:text-base">{{ t('nav.searchDesc') }}</p>
            </div>

            <div v-else class="space-y-8 animate-fade-in-up">
                <div v-for="(items, type) in grouped" :key="type">
                    <div class="flex items-center gap-2 mb-3 px-1">
                        <div class="w-1.5 h-1.5 rounded-full bg-[var(--color-brand)]" />
                        <h2 class="text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest">
                            {{ groupMeta(type).label }}
                        </h2>
                        <span class="text-2xs text-[var(--color-text-muted)] font-bold">({{ items.length }})</span>
                        <div class="flex-1 h-px bg-gradient-to-r from-[var(--color-border-default)] to-transparent" />
                    </div>

                    <div v-if="type !== 'Transaksi'" class="space-y-1.5">
                        <Link v-for="item in items" :key="item.id" :href="item.route"
                            class="flex items-center gap-3 p-2.5 sm:p-3 rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] transition-all active:scale-[0.98] group hover:border-[var(--color-brand-border)]">
                            <AppIcon :icon="item.icon" fallback="search"
                                class="w-6 h-6 shrink-0 text-[var(--color-text-secondary)]" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-[var(--color-text-primary)] truncate group-hover:text-[var(--color-brand)] transition-colors">
                                    {{ item.label }}
                                </p>
                                <p v-if="item.description" class="text-2xs text-[var(--color-text-muted)] truncate mt-0.5">
                                    {{ item.description }}
                                </p>
                            </div>
                            <svg class="w-4 h-4 text-[var(--color-text-muted)] group-hover:text-[var(--color-text-secondary)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>

                    <div v-if="type === 'Transaksi'" class="space-y-1.5">
                        <button v-for="item in items" :key="item.id" @click="openTransaction(item)"
                            class="w-full text-left bg-[var(--color-surface-raised)] p-2.5 sm:p-3 rounded-xl border border-[var(--color-border-default)] hover:border-[var(--color-brand-border)] active:scale-[0.98] transition-all relative overflow-hidden group">
                            <div class="absolute inset-0 bg-[var(--color-surface-muted)]/50 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none" />

                            <div class="flex items-center gap-3 relative z-10">
                                <AppIcon :icon="item.category?.icon" fallback="file-text"
                                    class="w-6 h-6 shrink-0 text-[var(--color-brand)]" />

                                <div class="flex-1 min-w-0 pr-2">
                                    <p class="text-xs font-bold text-[var(--color-text-primary)] leading-tight truncate">
                                        {{ displayTitle(item) }}
                                    </p>
                                    <p v-if="item.notes" class="text-2xs text-[var(--color-text-muted)] truncate mt-0.5 italic">
                                        {{ item.notes }}
                                    </p>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span class="text-[var(--color-text-secondary)] text-2xs tracking-wide font-medium truncate">
                                            {{ item.category?.category_name || getTypeName(item.transaction_type?.name) }}
                                        </span>
                                        <span class="text-[var(--color-text-muted)]">•</span>
                                        <span class="text-[var(--color-text-secondary)] text-2xs tracking-wide font-medium truncate">
                                            {{ getWalletName(item) }}
                                        </span>
                                        <span v-if="item.is_draft"
                                            class="shrink-0 inline-flex items-center gap-0.5 text-2xs font-black uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-[var(--color-surface-muted)] text-[var(--color-text-secondary)] border border-[var(--color-border-default)]">
                                            {{ t('transaction.draft') }}
                                        </span>
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <p class="text-2xs font-black" :class="amountClass(item)">
                                        <span v-if="isBalanceVisible">{{ amountPrefix(item) }}</span>{{ displayAmount(item) }}
                                    </p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <TransactionDetailModal :show="showModal" :transaction="selectedTransaction" @close="closeTransaction" />
    </AuthenticatedLayout>
</template>
