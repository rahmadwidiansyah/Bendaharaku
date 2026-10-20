<script setup>
/**
 * Material/Pages/Transactions/Edit — wizard edit transaksi/draft (M3).
 * Paritas dengan Pages/Transactions/Edit: mulai di langkah 2, aksi hapus +
 * konfirmasi, info dibuat/diperbarui, submit via transactions.update
 * (logika di useTransactionWizard + useTransactionForm).
 */
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AuthenticatedLayout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3Chip from '@/Material/Components/M3Chip.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3TextField from '@/Material/Components/M3TextField.vue'
import M3AmountKeypad from '@/Material/Components/M3AmountKeypad.vue'
import M3DatePickerModal from '@/Material/Components/M3DatePickerModal.vue'
import M3WalletPickerModal from '@/Material/Components/M3WalletPickerModal.vue'
import M3Modal from '@/Material/Components/M3Modal.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import { useTransactionWizard } from '@/Material/Composables/useTransactionWizard.js'
import { getM3CategoryIconClass } from '@/Material/utils/m3IconColor.js'
import { formatDate, isTodayDateOnly } from '@/utils/format.js'

const { t } = useI18n()
const { isDesktopLayout } = useLayoutPreference()

const props = defineProps({
    transaction: Object,
    wallets: Array,
    categories: Array,
    systemWallets: Array,
    debtSubjects: Array,
    receivableSubjects: Array,
})

const normalizeDate = (value) => {
    if (!value) return null
    if (typeof value === 'string') return value.slice(0, 10)
    if (typeof value === 'object' && value.year && value.month && value.day) {
        return `${value.year}-${String(value.month).padStart(2, '0')}-${String(value.day).padStart(2, '0')}`
    }
    return null
}

// ─── Tipe awal dari transaksi yang diedit ─────────────────────────
const initialCategory = props.categories.find(c => c.id === props.transaction.category_id)
const initialType = props.transaction.transaction_type
    ? (props.transaction.transaction_type.charAt(0).toUpperCase() + props.transaction.transaction_type.slice(1))
    : (initialCategory?.type?.name ?? 'Expense')
const initialDebtSubTab = props.transaction.debt_sub_type
    ? props.transaction.debt_sub_type
    : ((initialCategory?.system_key === 'DEBT_PAYMENT' || initialCategory?.system_key === 'RECEIVABLE') ? 'expense' : 'income')
const initialAmount = String(Math.trunc(Number(props.transaction.amount) || 0))

const form = useForm({
    category_id: props.transaction.category_id,
    source_wallet_id: props.transaction.source_wallet_id,
    destination_wallet_id: props.transaction.destination_wallet_id,
    amount: Math.trunc(Number(props.transaction.amount) || 0),
    date: normalizeDate(props.transaction.date),
    subject: props.transaction.subject || '-',
    notes: props.transaction.notes || '',
    transaction_type: initialType.toLowerCase(),
    debt_sub_type: ['Debt', 'Receivable'].includes(initialType) ? initialDebtSubTab : null,
    due_date: normalizeDate(props.transaction.due_date),
    due_date_type: props.transaction.due_date_type,
    due_date_interval: props.transaction.due_date_interval,
})

const {
    rawAmount, handleKeypad,
    mainTab, activeType, typeKey, debtSubTab, setDebtSubTab, confirmType, goToNominal,
    activeCategories, selectedCategory, selectCategory, activeSubjects, isMoneyIn,
    formStep, resetToStep, goBack,
    transferErrors, transferAll, isSwapping, swapWallets, submitTransfer,
    showWalletModal, walletModalMode, openWalletModal, selectWallet, availableWallets,
    selectedSourceWallet, selectedDestWallet,
    showDateModal, dateModalTarget, showKeypad,
    lunasi, debtBalance,
    submit,
} = useTransactionWizard({
    form,
    props,
    isDesktopLayout,
    mode: 'edit',
    initial: {
        initialMainTab: initialType,
        initialType,
        initialDebtSubTab,
        initialAmount,
    },
})

const TYPE_ITEMS = computed(() => [
    { tab: 'Expense', label: t('types.expense'), desc: t('types.expenseDesc'), icon: 'arrow-up-from-line' },
    { tab: 'Income', label: t('types.income'), desc: t('types.incomeDesc'), icon: 'arrow-down-to-line' },
    { tab: 'Transfer', label: t('types.transfer'), desc: t('types.transferDesc'), icon: 'arrow-left-right' },
    { tab: 'Debt', label: t('types.debt'), desc: t('types.debtDesc'), icon: 'circle-dollar-sign' },
    { tab: 'Receivable', label: t('types.receivable'), desc: t('types.receivableDesc'), icon: 'hand-coins' },
])

const activeTypeItem = computed(() => TYPE_ITEMS.value.find(i => i.tab === mainTab.value))

const formatAmount = computed(() => parseInt(rawAmount.value || 0).toLocaleString('id-ID'))
const formatBalance = (value) => parseInt(value || 0).toLocaleString('id-ID')
const formatTimestamp = (value) =>
    value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'

const errorMessages = computed(() => ({ ...transferErrors.value, ...form.errors }))
const hasErrors = computed(() => Object.keys(errorMessages.value).length > 0)

const dateButtonLabel = computed(() =>
    isTodayDateOnly(form.date) ? t('transaction.today') : formatDate(form.date, { day: 'numeric', month: 'short' }),
)

const dueDateButtonLabel = computed(() =>
    form.due_date
        ? formatDate(form.due_date, { day: 'numeric', month: 'short', year: 'numeric' })
        : t('transaction.chooseDate'),
)

const onSubjectInput = (value) => {
    form.subject = value.toUpperCase()
}

const pickDate = (value) => {
    if (dateModalTarget.value === 'due_date') form.due_date = value
    else form.date = value
}

// Chip langkah aktif dijaga tetap terlihat saat deretan chip menggulir.
const stepsNav = ref(null)
const scrollActiveStepIntoView = () => {
    nextTick(() => {
        stepsNav.value?.querySelector('.m3-txn__step--active')?.scrollIntoView({
            inline: 'nearest',
            block: 'nearest',
            behavior: 'smooth',
        })
    })
}

watch([formStep, mainTab], scrollActiveStepIntoView)
onMounted(scrollActiveStepIntoView)

// ─── Hapus ────────────────────────────────────────────────────────
const showDeleteConfirm = ref(false)
const confirmDelete = () => {
    showDeleteConfirm.value = false
    router.delete(route('transactions.destroy', { transaction: props.transaction.id, is_draft: props.transaction.is_draft }))
}
</script>

<template>
    <AuthenticatedLayout :fullWidth="true" :hideNav="true">
        <Head :title="t('transaction.titleEdit')" />

        <div
            class="m3-txn"
            :class="isDesktopLayout ? 'm3-txn--desktop' : 'm3-txn--fullscreen'"
        >
            <!-- ── Top bar ─────────────────────────────────────────── -->
            <header class="m3-txn__topbar">
                <M3IconButton :aria-label="t('common.close')" @click="goBack">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </M3IconButton>
                <h1 class="m3-txn__title">{{ t('transaction.titleEdit') }}</h1>
                <M3IconButton
                    class="m3-txn__delete-btn"
                    :aria-label="t('transaction.deleteTitle')"
                    @click="showDeleteConfirm = true"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </M3IconButton>
            </header>

            <!-- ── Indikator langkah ───────────────────────────────── -->
            <nav ref="stepsNav" class="m3-txn__steps" :aria-label="t('transaction.titleEdit')">
                <button
                    type="button"
                    class="m3-txn__step"
                    :class="formStep === 1 ? 'm3-txn__step--active' : 'm3-txn__step--done'"
                    @click="resetToStep(1)"
                >
                    <AppIcon :icon="activeTypeItem?.icon" />
                    {{ activeTypeItem?.label }}
                </button>

                <span class="m3-txn__step-sep" aria-hidden="true">›</span>

                <template v-if="mainTab === 'Transfer'">
                    <span class="m3-txn__step" :class="formStep === 2 ? 'm3-txn__step--active' : ''">
                        2 · {{ t('types.transfer') }}
                    </span>
                </template>

                <template v-else>
                    <button
                        type="button"
                        class="m3-txn__step"
                        :class="formStep === 2 ? 'm3-txn__step--active' : 'm3-txn__step--done'"
                        @click="resetToStep(2)"
                    >
                        <AppIcon v-if="formStep > 2 && selectedCategory" :icon="selectedCategory.icon" />
                        <template v-if="formStep > 2 && selectedCategory">{{ selectedCategory.category_name }}</template>
                        <template v-else>2 · {{ t('transaction.category') }}</template>
                    </button>

                    <template v-if="formStep >= 3">
                        <span class="m3-txn__step-sep" aria-hidden="true">›</span>
                        <span class="m3-txn__step m3-txn__step--active">3 · {{ t('transaction.amount') }}</span>
                    </template>
                </template>
            </nav>

            <!-- ── Step 1: ganti tipe ──────────────────────────────── -->
            <Transition name="m3-txn-step">
                <div v-if="formStep === 1" class="m3-txn__body m3-txn__body--center">
                    <div class="m3-txn__intro">
                        <p class="m3-txn__intro-eyebrow">
                            {{ t('transaction.stepOf', { step: 1, total: 3 }) }}
                        </p>
                        <h2 class="m3-txn__intro-title">{{ t('transaction.selectType') }}</h2>
                    </div>

                    <div class="m3-txn__type-list">
                        <button
                            v-for="item in TYPE_ITEMS"
                            :key="item.tab"
                            type="button"
                            :class="['m3-txn__type', item.tab === mainTab ? 'm3-txn__type--current' : '']"
                            @click="confirmType(item.tab)"
                        >
                            <span class="m3-txn__type-icon" :class="`m3-txn__type-icon--${item.tab.toLowerCase()}`" aria-hidden="true">
                                <AppIcon :icon="item.icon" />
                            </span>
                            <span class="m3-txn__type-body">
                                <span class="m3-txn__type-label">{{ item.label }}</span>
                                <span class="m3-txn__type-desc">{{ item.desc }}</span>
                            </span>
                            <svg
                                v-if="item.tab === mainTab"
                                class="m3-txn__type-check"
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
                </div>
            </Transition>

            <!-- ── Step 2: kategori / transfer / pihak ──────────────── -->
            <Transition name="m3-txn-step">
                <div
                    v-if="formStep === 2"
                    class="m3-txn__body"
                    :class="mainTab === 'Transfer' ? 'm3-txn__body--dock' : 'm3-txn__body--column'"
                >
                    <div v-if="['Debt', 'Receivable'].includes(mainTab)" class="m3-txn__subtabs" role="group">
                        <M3Chip
                            :selected="debtSubTab === (mainTab === 'Debt' ? 'income' : 'expense')"
                            @click="setDebtSubTab(mainTab === 'Debt' ? 'income' : 'expense')"
                        >{{ mainTab === 'Debt' ? t('transaction.debt.receive') : t('transaction.receivable.give') }}</M3Chip>
                        <M3Chip
                            :selected="debtSubTab === (mainTab === 'Debt' ? 'expense' : 'income')"
                            @click="setDebtSubTab(mainTab === 'Debt' ? 'expense' : 'income')"
                        >{{ mainTab === 'Debt' ? t('transaction.debt.pay') : t('transaction.receivable.collect') }}</M3Chip>
                    </div>

                    <div v-if="mainTab === 'Transfer'" class="m3-txn__body--dock">
                        <div class="m3-txn__scroll">
                            <p v-if="hasErrors" class="m3-txn__error" role="alert">
                                <span v-for="(err, key) in errorMessages" :key="key">{{ err }}</span>
                            </p>

                            <div class="m3-txn__wallets">
                                <p class="m3-txn__section-label">{{ t('transaction.transferFunds') }}</p>

                                <div class="m3-txn__wallet-pair">
                                    <div class="m3-txn__wallet-stack">
                                        <button
                                            type="button"
                                            :class="['m3-txn__wallet', transferErrors.source ? 'm3-txn__wallet--error' : selectedSourceWallet ? 'm3-txn__wallet--filled' : '']"
                                            @click="openWalletModal('source')"
                                        >
                                            <span class="m3-txn__wallet-icon" aria-hidden="true">
                                                <AppIcon :icon="selectedSourceWallet?.icon" :fallback="selectedSourceWallet ? 'wallet' : 'circle-plus'" />
                                            </span>
                                            <span class="m3-txn__wallet-body">
                                                <span class="m3-txn__wallet-meta">
                                                    <span class="m3-txn__wallet-label">{{ t('transaction.detail.from') }}</span>
                                                    <span v-if="selectedSourceWallet" class="m3-txn__wallet-balance">
                                                        Rp {{ formatBalance(selectedSourceWallet.balance) }}
                                                    </span>
                                                </span>
                                                <span class="m3-txn__wallet-name">{{ selectedSourceWallet?.name || t('transaction.chooseSourceWallet') }}</span>
                                            </span>
                                            <svg class="m3-txn__wallet-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>

                                        <label v-if="selectedSourceWallet" class="m3-txn__check">
                                            <input v-model="transferAll" type="checkbox" class="m3-txn__check-box">
                                            <span>{{ t('transaction.allBalance') }}</span>
                                        </label>

                                        <button
                                            type="button"
                                            :class="['m3-txn__wallet', transferErrors.dest ? 'm3-txn__wallet--error' : selectedDestWallet ? 'm3-txn__wallet--filled' : '']"
                                            @click="openWalletModal('dest')"
                                        >
                                            <span class="m3-txn__wallet-icon" aria-hidden="true">
                                                <AppIcon :icon="selectedDestWallet?.icon" :fallback="selectedDestWallet ? 'wallet' : 'circle-plus'" />
                                            </span>
                                            <span class="m3-txn__wallet-body">
                                                <span class="m3-txn__wallet-meta">
                                                    <span class="m3-txn__wallet-label">{{ t('transaction.detail.to') }}</span>
                                                    <span v-if="selectedDestWallet" class="m3-txn__wallet-balance">
                                                        Rp {{ formatBalance(selectedDestWallet.balance) }}
                                                    </span>
                                                </span>
                                                <span class="m3-txn__wallet-name">{{ selectedDestWallet?.name || t('transaction.chooseDestWallet') }}</span>
                                            </span>
                                            <svg class="m3-txn__wallet-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </div>

                                    <M3IconButton class="m3-txn__swap" :aria-label="t('transaction.transferFunds')" :class="isSwapping ? 'm3-txn__swap--on' : ''" @click="swapWallets">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4" />
                                        </svg>
                                    </M3IconButton>
                                </div>
                            </div>

                            <M3TextField
                                :model-value="form.notes"
                                :label="t('transaction.notePlaceholder')"
                                @update:model-value="form.notes = $event"
                            />
                        </div>

                        <div class="m3-txn__dock">
                            <div class="m3-txn__amount-panel">
                                <p class="m3-txn__amount-label">{{ t('transaction.amount') }}</p>
                                <p class="m3-txn__amount" :class="transferErrors.amount ? 'm3-txn__amount--error' : ''">
                                    <span class="m3-txn__amount-currency">Rp</span>{{ formatAmount }}
                                </p>
                                <div class="m3-txn__actions">
                                    <button
                                        type="button"
                                        class="m3-txn__date"
                                        :title="t('transaction.chooseDate')"
                                        @click="dateModalTarget = 'transaction'; showDateModal = true"
                                    >
                                        <AppIcon icon="calendar-days" />
                                        <span class="m3-txn__date-label">{{ dateButtonLabel }}</span>
                                    </button>
                                    <M3IconButton
                                        :aria-label="t('transaction.keypadLabel')"
                                        :variant="showKeypad ? 'tonal' : 'standard'"
                                        :aria-pressed="showKeypad ? 'true' : 'false'"
                                        @click="showKeypad = !showKeypad"
                                    >
                                        <AppIcon :icon="showKeypad ? 'keyboard-off' : 'keyboard'" />
                                    </M3IconButton>
                                    <M3Button variant="filled" size="lg" full-width :disabled="form.processing" @click="submitTransfer(true)">
                                        {{ t('common.save') }}
                                    </M3Button>
                                </div>
                            </div>

                            <M3AmountKeypad v-show="showKeypad" @key="handleKeypad" />
                        </div>
                    </div>

                    <div v-else-if="['Debt', 'Receivable'].includes(mainTab)" class="m3-txn__body--column">
                        <div class="m3-txn__intro">
                            <p class="m3-txn__intro-eyebrow" :class="`m3-txn__accent--${typeKey}`">
                                {{ t('transaction.stepOf', { step: 2, total: 3 }) }}
                            </p>
                            <h2 class="m3-txn__intro-title">{{ t('transaction.relatedParty') }}</h2>
                        </div>

                        <M3TextField
                            :model-value="form.subject"
                            :label="t('transaction.namePlaceholder')"
                            :placeholder="t('transaction.namePlaceholder')"
                            :error="form.errors.subject"
                            centered
                            uppercase
                            @update:model-value="onSubjectInput"
                        />

                        <div v-if="activeSubjects?.length" class="m3-txn__chips">
                            <M3Chip
                                v-for="sub in activeSubjects"
                                :key="sub.name"
                                :selected="form.subject === sub.name"
                                @click="form.subject = sub.name"
                            >
                                {{ sub.name }}
                                <span v-if="sub.balance" class="m3-txn__chip-balance">Rp{{ formatBalance(sub.balance) }}</span>
                            </M3Chip>
                        </div>

                        <div
                            v-if="(activeType === 'Debt' && debtSubTab === 'income') || (activeType === 'Receivable' && debtSubTab === 'expense')"
                            class="m3-txn__due"
                        >
                            <label class="m3-txn__check">
                                <input
                                    type="checkbox"
                                    class="m3-txn__check-box"
                                    :checked="form.due_date_type !== null"
                                    @change="form.due_date_type = $event.target.checked ? 'fixed' : null"
                                >
                                <span>{{ t('transaction.hasDueDate') }}</span>
                            </label>

                            <template v-if="form.due_date_type !== null">
                                <div class="m3-txn__chips" role="group">
                                    <M3Chip :selected="form.due_date_type === 'fixed'" @click="form.due_date_type = 'fixed'">{{ t('transaction.fixedDate') }}</M3Chip>
                                    <M3Chip :selected="form.due_date_type === 'monthly'" @click="form.due_date_type = 'monthly'">{{ t('transaction.everyMonth') }}</M3Chip>
                                    <M3Chip :selected="form.due_date_type === 'daily'" @click="form.due_date_type = 'daily'">{{ t('transaction.everyDay') }}</M3Chip>
                                </div>

                                <M3Button
                                    v-if="form.due_date_type === 'fixed'"
                                    variant="outlined"
                                    full-width
                                    @click="dateModalTarget = 'due_date'; showDateModal = true"
                                >
                                    {{ dueDateButtonLabel }}
                                </M3Button>

                                <M3TextField
                                    v-if="form.due_date_type === 'monthly'"
                                    :model-value="form.due_date_interval ?? ''"
                                    :label="t('transaction.dayPlaceholder')"
                                    type="number"
                                    inputmode="numeric"
                                    :min="1"
                                    :max="31"
                                    centered
                                    @update:model-value="form.due_date_interval = $event === '' ? null : $event"
                                />

                                <M3TextField
                                    v-if="form.due_date_type === 'daily'"
                                    :model-value="form.due_date_interval ?? ''"
                                    :label="t('transaction.cyclePlaceholder')"
                                    type="number"
                                    inputmode="numeric"
                                    :min="1"
                                    centered
                                    @update:model-value="form.due_date_interval = $event === '' ? null : $event"
                                />
                            </template>
                        </div>

                        <M3Button variant="filled" full-width size="lg" @click="goToNominal">
                            {{ t('transaction.nextNominal') }}
                        </M3Button>
                    </div>

                    <div v-else class="m3-txn__body--column">
                        <div class="m3-txn__intro">
                            <p class="m3-txn__intro-eyebrow" :class="`m3-txn__accent--${typeKey}`">
                                {{ t('transaction.stepOf', { step: 2, total: 3 }) }}
                            </p>
                            <h2 class="m3-txn__intro-title">{{ t('transaction.chooseCategory') }}</h2>
                        </div>

                        <div v-if="activeCategories.length" class="m3-txn__cats">
                            <button
                                v-for="cat in activeCategories"
                                :key="cat.id"
                                type="button"
                                :class="['m3-txn__cat', form.category_id === cat.id ? 'm3-txn__cat--selected' : '']"
                                :aria-pressed="form.category_id === cat.id"
                                @click="(selectCategory(cat), goToNominal())"
                            >
                                <span class="m3-txn__cat-icon" aria-hidden="true">
                                    <AppIcon :icon="cat.icon" fallback="folder" :class="getM3CategoryIconClass(cat.type?.name)" />
                                </span>
                                <span class="m3-txn__cat-name">{{ cat.category_name }}</span>
                                <span v-if="form.category_id === cat.id" class="m3-txn__cat-check" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                            </button>

                            <Link
                                :href="route('categories.create', { type: mainTab })"
                                class="m3-txn__cat m3-txn__cat--add"
                            >
                                <span class="m3-txn__cat-icon" aria-hidden="true">+</span>
                                <span class="m3-txn__cat-name">{{ t('transaction.addCategory') }}</span>
                            </Link>
                        </div>

                        <div v-else class="m3-txn__empty">
                            <p>{{ t('transaction.noCategory') }}</p>
                            <Link :href="route('categories.create', { type: mainTab })" class="m3-txn__empty-link">
                                + {{ t('transaction.addCategory') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- ── Step 3: nominal, dompet, catatan ────────────────── -->
            <Transition name="m3-txn-step">
                <form
                    v-if="formStep >= 3 && mainTab !== 'Transfer'"
                    class="m3-txn__body m3-txn__body--dock"
                    @submit.prevent="submit"
                >
                    <div class="m3-txn__scroll">
                        <p v-if="hasErrors" class="m3-txn__error" role="alert">
                            <span v-for="(err, key) in errorMessages" :key="key">{{ err }}</span>
                        </p>

                        <M3TextField
                            :model-value="form.notes"
                            :label="t('transaction.notePlaceholder')"
                            @update:model-value="form.notes = $event"
                        />

                        <button
                            type="button"
                            :class="['m3-txn__wallet', (isMoneyIn ? selectedDestWallet : selectedSourceWallet) ? 'm3-txn__wallet--filled' : '']"
                            @click="openWalletModal(isMoneyIn ? 'dest' : 'source')"
                        >
                            <span class="m3-txn__wallet-icon" aria-hidden="true">
                                <AppIcon
                                    :icon="(isMoneyIn ? selectedDestWallet : selectedSourceWallet)?.icon"
                                    :fallback="(isMoneyIn ? selectedDestWallet : selectedSourceWallet) ? 'wallet' : 'circle-plus'"
                                />
                            </span>
                            <span class="m3-txn__wallet-body">
                                <span class="m3-txn__wallet-label">{{ t('transaction.wallet') }}</span>
                                <span class="m3-txn__wallet-name">
                                    {{ (isMoneyIn ? selectedDestWallet : selectedSourceWallet)?.name || t('transaction.chooseWallet') }}
                                </span>
                            </span>
                            <svg class="m3-txn__wallet-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        <label v-if="['Debt', 'Receivable'].includes(mainTab) && debtBalance > 0" class="m3-txn__check">
                            <input v-model="lunasi" type="checkbox" class="m3-txn__check-box">
                            <span>{{ mainTab === 'Debt' ? t('transaction.settle') : t('transaction.collectAll') }}</span>
                            <span class="m3-txn__check-amount">Rp {{ formatBalance(debtBalance) }}</span>
                        </label>

                        <div class="m3-txn__meta">
                            <div class="m3-txn__meta-item">
                                <span class="m3-txn__meta-label">{{ t('transaction.created') }}</span>
                                <span class="m3-txn__meta-value">{{ formatTimestamp(transaction.created_at) }}</span>
                            </div>
                            <div class="m3-txn__meta-item">
                                <span class="m3-txn__meta-label">{{ t('transaction.updated') }}</span>
                                <span class="m3-txn__meta-value">{{ formatTimestamp(transaction.updated_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="m3-txn__dock">
                        <div class="m3-txn__amount-panel">
                            <p class="m3-txn__amount-label">{{ t('transaction.amount') }}</p>
                            <p class="m3-txn__amount" :class="form.errors.amount ? 'm3-txn__amount--error' : ''">
                                <span class="m3-txn__amount-currency">Rp</span>{{ formatAmount }}
                            </p>
                            <div class="m3-txn__actions">
                                <button
                                    type="button"
                                    class="m3-txn__date"
                                    :title="t('transaction.chooseDate')"
                                    @click="dateModalTarget = 'transaction'; showDateModal = true"
                                >
                                    <AppIcon icon="calendar-days" />
                                    <span class="m3-txn__date-label">{{ dateButtonLabel }}</span>
                                </button>
                                <M3IconButton
                                    :aria-label="t('transaction.keypadLabel')"
                                    :variant="showKeypad ? 'tonal' : 'standard'"
                                    :aria-pressed="showKeypad ? 'true' : 'false'"
                                    @click="showKeypad = !showKeypad"
                                >
                                    <AppIcon :icon="showKeypad ? 'keyboard-off' : 'keyboard'" />
                                </M3IconButton>
                                <M3Button variant="filled" size="lg" full-width type="submit" :disabled="form.processing">
                                    {{ t('common.save') }}
                                </M3Button>
                            </div>
                        </div>

                        <M3AmountKeypad v-show="showKeypad" @key="handleKeypad" />
                    </div>
                </form>
            </Transition>
        </div>

        <!-- ── Modal: tanggal ──────────────────────────────────────── -->
        <M3DatePickerModal
            :show="showDateModal"
            :model-value="dateModalTarget === 'due_date' ? form.due_date : form.date"
            @update:model-value="pickDate"
            @close="showDateModal = false"
        />

        <!-- ── Modal: dompet ───────────────────────────────────────── -->
        <M3WalletPickerModal
            :show="showWalletModal"
            :wallets="availableWallets"
            :selected-id="walletModalMode === 'source' ? form.source_wallet_id : form.destination_wallet_id"
            @select="(w) => { selectWallet(w); showWalletModal = false }"
            @close="showWalletModal = false"
        />

        <!-- ── Modal: konfirmasi hapus ────────────────────────────── -->
        <M3Modal :show="showDeleteConfirm" max-width="sm" @close="showDeleteConfirm = false">
            <div class="m3-txn__confirm">
                <span class="m3-txn__confirm-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </span>
                <h2 class="m3-txn__confirm-title">{{ t('transaction.deleteTitle') }}</h2>
                <p class="m3-txn__confirm-text">{{ t('transaction.deleteWarn') }}</p>
            </div>

            <template #footer>
                <M3Button variant="text" @click="showDeleteConfirm = false">
                    {{ t('btn.no') }}
                </M3Button>
                <M3Button class="m3-txn__confirm-delete" @click="confirmDelete">
                    {{ t('btn.yes') }}
                </M3Button>
            </template>
        </M3Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.m3-txn {
    display: flex;
    flex-direction: column;
    min-height: 100dvh;
    background: var(--m3-color-surface);
    color: var(--m3-color-on-surface);
}

.m3-txn--fullscreen {
    position: fixed;
    inset: 0;
    z-index: 30;
    height: 100dvh;
    overflow: hidden;
}

.m3-txn--desktop {
    position: relative;
    overflow: visible;
}

.m3-txn--desktop .m3-txn__body {
    overflow: visible;
}

/* ── Top bar ── */
.m3-txn__topbar {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    padding: var(--m3-space-3) var(--m3-space-4);
    padding-top: calc(var(--m3-space-3) + env(safe-area-inset-top, 0px));
}

.m3-txn__title {
    flex: 1;
    margin: 0;
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-title-large-weight);
    letter-spacing: var(--m3-type-title-large-tracking);
    color: var(--m3-color-on-surface);
}

.m3-txn__delete-btn {
    color: var(--m3-color-expense);
}

/* ── Langkah ── */
.m3-txn__steps {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1);
    padding: 0 var(--m3-space-4) var(--m3-space-2);
    overflow-x: auto;
    scroll-padding-inline: var(--m3-space-4);
    scrollbar-width: none;
}

.m3-txn__steps::-webkit-scrollbar {
    display: none;
}

.m3-txn__step {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
    min-height: 2rem;
    padding: 0 var(--m3-space-3);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    font-family: inherit;
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
    white-space: nowrap;
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-txn__step :deep(svg) {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
}

.m3-txn__step--active {
    background: var(--m3-color-secondary-container);
    border-color: transparent;
    color: var(--m3-color-on-secondary-container);
}

.m3-txn__step--done {
    background: var(--m3-color-surface-container-high);
    border-color: transparent;
    color: var(--m3-color-on-surface);
}

.m3-txn__step-sep {
    color: var(--m3-color-on-surface-variant);
    flex-shrink: 0;
}

/* ── Intro ── */
.m3-txn__intro {
    text-align: center;
}

.m3-txn__intro-eyebrow {
    margin: 0 0 var(--m3-space-1);
    color: var(--m3-color-primary);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    text-transform: uppercase;
}

.m3-txn__accent--income { color: var(--m3-color-income); }
.m3-txn__accent--expense { color: var(--m3-color-expense); }
.m3-txn__accent--transfer { color: var(--m3-color-transfer); }
.m3-txn__accent--debt { color: var(--m3-color-debt); }
.m3-txn__accent--receivable { color: var(--m3-color-receivable); }

.m3-txn__intro-title {
    margin: 0;
    font-size: var(--m3-type-headline-small-size);
    line-height: var(--m3-type-headline-small-line-height);
    font-weight: var(--m3-type-headline-small-weight);
    letter-spacing: var(--m3-type-headline-small-tracking);
}

.m3-txn__intro-desc {
    margin: var(--m3-space-1) 0 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

/* ── Error ── */
.m3-txn__error {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    margin: 0;
    padding: var(--m3-space-3);
    border-radius: var(--m3-radius-md);
    background: color-mix(in srgb, var(--m3-color-expense) 14%, transparent);
    color: var(--m3-color-expense);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    font-weight: var(--m3-type-body-medium-weight);
}

/* ── Kartu tipe ── */
.m3-txn__type-list {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
    width: 100%;
}

.m3-txn__type {
    display: flex;
    align-items: center;
    gap: var(--m3-space-4);
    padding: var(--m3-space-3) var(--m3-space-4);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-low);
    color: var(--m3-color-on-surface);
    font-family: inherit;
    text-align: left;
    cursor: pointer;
    transition: transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-txn__type:active {
    transform: scale(0.97);
}

.m3-txn__type:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-txn__type--current {
    background: var(--m3-color-secondary-container);
    border-color: transparent;
    color: var(--m3-color-on-secondary-container);
}

.m3-txn__type-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
}

.m3-txn__type-icon :deep(svg) {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-txn__type-icon--expense { background: var(--m3-color-expense-container); color: var(--m3-color-expense); }
.m3-txn__type-icon--income { background: var(--m3-color-income-container); color: var(--m3-color-income); }
.m3-txn__type-icon--transfer { background: var(--m3-color-transfer-container); color: var(--m3-color-transfer); }
.m3-txn__type-icon--debt { background: var(--m3-color-debt-container); color: var(--m3-color-debt); }
.m3-txn__type-icon--receivable { background: var(--m3-color-receivable-container); color: var(--m3-color-receivable); }

.m3-txn__type-body {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
}

.m3-txn__type-label {
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
}

.m3-txn__type-desc {
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

.m3-txn__type--current .m3-txn__type-desc {
    color: inherit;
}

.m3-txn__type-check {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
    flex-shrink: 0;
}

/* ── Layout ── */
.m3-txn__body {
    flex: 1;
    min-height: 0;
    padding: var(--m3-space-4);
    padding-bottom: calc(var(--m3-space-4) + env(safe-area-inset-bottom, 0px));
}

.m3-txn--fullscreen .m3-txn__body {
    overflow-y: auto;
}

.m3-txn__body--center {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-5);
    width: 100%;
    max-width: var(--m3-size-dialog-md);
    margin: 0 auto;
}

.m3-txn__body--column {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
    width: 100%;
    max-width: var(--m3-size-dialog-md);
    margin: 0 auto;
}

/* Dock layout: area field menggulir, panel nominal + keypad tetap di bawah */
.m3-txn__body--dock {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 0;
    min-height: 0;
    max-width: none;
    margin: 0;
    padding: 0;
}

.m3-txn--fullscreen .m3-txn__body--dock {
    overflow: hidden;
}

.m3-txn__scroll {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: var(--m3-space-4);
    min-height: 0;
    width: 100%;
    max-width: var(--m3-size-dialog-md);
    margin: 0 auto;
    padding: var(--m3-space-4);
    overflow-y: auto;
}

.m3-txn__dock {
    display: flex;
    flex-shrink: 0;
    flex-direction: column;
    gap: var(--m3-space-2);
    width: 100%;
    max-width: var(--m3-size-dialog-md);
    margin: 0 auto;
    padding: var(--m3-space-3) var(--m3-space-4)
        calc(var(--m3-space-4) + env(safe-area-inset-bottom, 0px));
    background: var(--m3-color-surface);
    border-top: 1px solid var(--m3-color-outline-variant);
}

.m3-txn__dock .m3-txn__amount-panel {
    border-top: none;
    padding-top: 0;
}

/* Desktop: dok mengalir tepat setelah field (tanpa pinning viewport). */
.m3-txn--desktop .m3-txn__body--dock {
    flex: 0 0 auto;
}

.m3-txn--desktop .m3-txn__body--dock,
.m3-txn--desktop .m3-txn__scroll {
    overflow: visible;
}

.m3-txn__subtabs,
.m3-txn__chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-2);
}

.m3-txn__subtabs {
    width: 100%;
}

.m3-txn__chip-balance {
    margin-left: var(--m3-space-1);
    opacity: 0.75;
}

/* ── Dompet ── */
.m3-txn__wallets {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
    padding: var(--m3-space-3);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-low);
}

.m3-txn__wallet-pair {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
}

.m3-txn__wallet-stack {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-txn__section-label {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
    text-align: center;
}

.m3-txn__wallet {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    width: 100%;
    min-height: var(--m3-size-touch-target-lg);
    padding: var(--m3-space-2) var(--m3-space-3);
    border: 1px dashed var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface);
    color: var(--m3-color-on-surface);
    font-family: inherit;
    text-align: left;
    cursor: pointer;
    transition: transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-txn__wallet:active {
    transform: scale(0.98);
}

.m3-txn__wallet:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-txn__wallet--filled {
    border-style: solid;
    border-color: var(--m3-color-primary);
}

.m3-txn__wallet--error {
    border-style: solid;
    border-color: var(--m3-color-expense);
}

.m3-txn__wallet-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-highest);
    color: var(--m3-color-on-surface-variant);
}

.m3-txn__wallet--filled .m3-txn__wallet-icon {
    color: var(--m3-color-primary);
}

.m3-txn__wallet-icon :deep(svg) {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-txn__wallet-body {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
}

.m3-txn__wallet-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-txn__wallet-label {
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    text-transform: uppercase;
}

.m3-txn__wallet-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
    font-weight: var(--m3-type-body-large-weight);
}

.m3-txn__wallet-balance {
    flex-shrink: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.m3-txn__wallet-chevron {
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
    flex-shrink: 0;
    color: var(--m3-color-on-surface-variant);
}

.m3-txn__swap {
    align-self: center;
    flex-shrink: 0;
}

.m3-txn__swap--on {
    transform: rotate(180deg);
}

/* ── Checkbox ── */
.m3-txn__check {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    min-height: var(--m3-size-touch-target);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    cursor: pointer;
}

.m3-txn__check-box {
    appearance: none;
    width: var(--m3-size-checkbox);
    height: var(--m3-size-checkbox);
    flex-shrink: 0;
    border: 2px solid var(--m3-color-on-surface-variant);
    border-radius: 2px;
    background: transparent;
    cursor: pointer;
    display: grid;
    place-content: center;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-txn__check-box::after {
    content: '';
    width: 0.625rem;
    height: 0.375rem;
    border-left: 2px solid var(--m3-color-on-primary);
    border-bottom: 2px solid var(--m3-color-on-primary);
    transform: rotate(-45deg) scale(0);
    transition: transform var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-txn__check-box:checked {
    background: var(--m3-color-primary);
    border-color: var(--m3-color-primary);
}

.m3-txn__check-box:checked::after {
    transform: rotate(-45deg) scale(1);
}

.m3-txn__check-box:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-txn__check-amount {
    margin-left: auto;
    color: var(--m3-color-debt);
    font-variant-numeric: tabular-nums;
}

/* ── Due date ── */
.m3-txn__due {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    padding: var(--m3-space-4);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-low);
}

/* ── Kategori ── */
.m3-txn__cats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: var(--m3-space-2);
}

.m3-txn__cat {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-1);
    aspect-ratio: 1;
    padding: var(--m3-space-2);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-low);
    color: var(--m3-color-on-surface);
    font-family: inherit;
    text-decoration: none;
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-txn__cat:active {
    transform: scale(0.96);
}

.m3-txn__cat:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-txn__cat--selected {
    background: var(--m3-color-secondary-container);
    border-color: var(--m3-color-primary);
    color: var(--m3-color-on-secondary-container);
}

.m3-txn__cat--add {
    border-style: dashed;
    color: var(--m3-color-primary);
}

.m3-txn__cat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-icon-lg);
    height: var(--m3-size-icon-lg);
}

.m3-txn__cat-icon :deep(svg) {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-txn__cat-name {
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-align: center;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    overflow: hidden;
}

.m3-txn__cat-check {
    position: absolute;
    top: var(--m3-space-2);
    right: var(--m3-space-2);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-primary);
    color: var(--m3-color-on-primary);
}

.m3-txn__cat-check :deep(svg) {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
}

@media (min-width: 30rem) {
    .m3-txn__cats {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

.m3-txn__empty {
    padding: var(--m3-space-6) var(--m3-space-4);
    border: 1px dashed var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-lg);
    text-align: center;
}

.m3-txn__empty p {
    margin: 0 0 var(--m3-space-2);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

.m3-txn__empty-link {
    color: var(--m3-color-primary);
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-emphasized-label-large-weight);
}

/* ── Metadata (khusus Edit) ── */
.m3-txn__meta {
    display: flex;
    gap: var(--m3-space-2);
}

.m3-txn__meta-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-0-5);
    padding: var(--m3-space-2) var(--m3-space-3);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-low);
}

.m3-txn__meta-label {
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    text-transform: uppercase;
}

.m3-txn__meta-value {
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

/* ── Nominal ── */
.m3-txn__amount-panel {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    padding: var(--m3-space-3) 0 var(--m3-space-2);
    border-top: 1px solid var(--m3-color-outline-variant);
    text-align: center;
}

.m3-txn__amount-label {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
}

.m3-txn__amount {
    margin: 0;
    font-size: var(--m3-type-display-small-size);
    line-height: var(--m3-type-display-small-line-height);
    font-weight: var(--m3-type-display-small-weight);
    letter-spacing: var(--m3-type-display-small-tracking);
    color: var(--m3-color-on-surface);
    font-variant-numeric: tabular-nums;
}

.m3-txn__amount--error {
    color: var(--m3-color-expense);
}

/* Layar sangat sempit: nominal panjang tetap satu baris dengan skala title besar. */
@media (max-width: 359px) {
    .m3-txn__amount {
        font-size: var(--m3-type-headline-medium-size);
        line-height: var(--m3-type-headline-medium-line-height);
        letter-spacing: var(--m3-type-headline-medium-tracking);
    }
}

.m3-txn__amount-currency {
    margin-right: var(--m3-space-2);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-title-medium-size);
}

.m3-txn__actions {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
}

.m3-txn__actions :deep(.m3-icon-btn) {
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
}

.m3-txn__actions :deep(.m3-icon-btn__content) {
    width: var(--m3-size-icon-lg);
    height: var(--m3-size-icon-lg);
}

.m3-txn__actions :deep(.m3-icon-btn__content svg) {
    width: var(--m3-size-icon-lg);
    height: var(--m3-size-icon-lg);
}

.m3-txn__actions :deep(.m3-btn--full) {
    flex: 1;
    width: auto;
    min-width: 0;
}

.m3-txn__date {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-2);
    flex: 0 1 auto;
    min-width: 0;
    min-height: var(--m3-size-touch-target-lg);
    padding: 0 var(--m3-space-3);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-low);
    color: var(--m3-color-on-surface);
    font-family: inherit;
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-txn__date:active {
    transform: scale(0.96);
}

.m3-txn__date:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-txn__date :deep(svg) {
    width: var(--m3-size-icon-lg);
    height: var(--m3-size-icon-lg);
    flex-shrink: 0;
}

.m3-txn__date-label {
    min-width: 0;
    overflow: hidden;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-label-large-weight);
    text-align: left;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-txn__dock :deep(.m3-keypad) {
    row-gap: var(--m3-space-1);
}

.m3-txn__dock :deep(.m3-keypad__del) {
    width: var(--m3-size-icon-lg);
    height: var(--m3-size-icon-lg);
}

@media (max-width: 359px) {
    .m3-txn__actions {
        gap: var(--m3-space-1);
    }

    .m3-txn__date {
        padding: 0 var(--m3-space-2);
    }
}

/* ── Konfirmasi hapus ── */
.m3-txn__confirm {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-3);
    text-align: center;
}

.m3-txn__confirm-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3.5rem;
    height: 3.5rem;
    border-radius: var(--m3-radius-full);
    background: color-mix(in srgb, var(--m3-color-expense) 18%, transparent);
    color: var(--m3-color-expense);
}

.m3-txn__confirm-icon :deep(svg) {
    width: var(--m3-size-icon-lg);
    height: var(--m3-size-icon-lg);
}

.m3-txn__confirm-title {
    margin: 0;
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-title-large-weight);
}

.m3-txn__confirm-text {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

.m3-txn__confirm-delete {
    background: var(--m3-color-expense) !important;
    color: var(--m3-color-on-error) !important;
}

/* ── Transisi langkah ── */
.m3-txn-step-enter-active {
    transition:
        opacity var(--m3-motion-default-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-txn-step-leave-active {
    transition:
        opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-txn-step-enter-from {
    opacity: 0;
    transform: translateY(var(--m3-space-4));
}

.m3-txn-step-leave-to {
    opacity: 0;
    transform: translateY(calc(-1 * var(--m3-space-2)));
}
</style>
