<script setup>
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility'
import { formatDate, formatNumber, formatLocalYMD, parseDateOnly } from '@/utils/format.js'

const { t } = useI18n()
const { isBalanceVisible, toggleVisibility } = useBalanceVisibility()

const props = defineProps({
    loanDetails: { type: Array, default: () => [] },
    title: { type: String, default: '' },
    isDebt: { type: Boolean, default: true },
    total: { type: Number, default: 0 },
})

const maskedAmount = '••••••'

const typeLabel = computed(() => (props.isDebt ? t('loan.titleDebt') : t('loan.titleReceivable')))
const totalLabel = computed(() => (props.isDebt ? t('loan.totalDebt') : t('loan.totalReceivable')))
const countLabel = computed(() =>
    t(props.isDebt ? 'loan.activeDebtors' : 'loan.activeCreditors', { n: props.loanDetails.length }),
)
const typeIcon = computed(() => (props.isDebt ? 'circle-dollar-sign' : 'hand-coins'))

const displayAmount = (amount) => (isBalanceVisible.value ? formatNumber(amount) : maskedAmount)

const dueDays = (loan) => {
    if (!loan?.due_date) return null
    const due = parseDateOnly(loan.due_date)
    const today = parseDateOnly(formatLocalYMD())
    if (!due || !today) return null
    return Math.round((due.getTime() - today.getTime()) / 86_400_000)
}

const sortedLoans = computed(() =>
    [...props.loanDetails].sort((a, b) => {
        const aDue = dueDays(a)
        const bDue = dueDays(b)
        if (aDue !== null && bDue !== null) return aDue - bDue || (b.age ?? 0) - (a.age ?? 0)
        if (aDue !== null) return -1
        if (bDue !== null) return 1
        return (b.age ?? 0) - (a.age ?? 0)
    }),
)

const nearestDueLoan = computed(() => sortedLoans.value.find((loan) => loan.due_date) ?? null)
const overdueCount = computed(() => sortedLoans.value.filter((loan) => (dueDays(loan) ?? 0) < 0).length)

const dueTone = (loan) => {
    const days = dueDays(loan)
    if (days === null) return 'normal'
    if (days < 0) return 'overdue'
    if (days === 0) return 'today'
    if (days <= 7) return 'soon'
    return 'normal'
}

const dueLabel = (loan) => {
    const days = dueDays(loan)
    if (days === null) return ''
    if (days < 0) return t('upcomingDebts.overdue')
    if (days === 0) return t('transaction.today')
    if (days <= 7) return `${days} ${t('upcomingDebts.days')}`
    return formatDate(loan.due_date, { day: '2-digit', month: 'short' })
}

const dueAriaLabel = (loan) => `${t('transaction.dueDate')}: ${formatDate(loan.due_date)}`
const sinceLabel = (loan) => formatDate(loan.since || loan.latest_date)
const initial = (subject) => (subject || '?').charAt(0).toUpperCase()
</script>

<template>
    <M3Layout :title="t('loan.title')">
        <Head :title="t('loan.title')" />

        <div class="m3-loans" :class="isDebt ? 'm3-loans--debt' : 'm3-loans--receivable'">
            <M3PageTitle :title="t('loan.title')" :subtitle="typeLabel" />

            <nav class="m3-loans__tabs" :aria-label="t('loan.title')">
                <Link
                    :href="route('loans.index', { type: 'debt' })"
                    class="m3-loans__tab"
                    :class="{ 'm3-loans__tab--active': isDebt }"
                    :aria-current="isDebt ? 'page' : undefined"
                >
                    <AppIcon icon="circle-dollar-sign" class="m3-loans__tab-icon" />
                    <span>{{ t('types.debt') }}</span>
                </Link>
                <Link
                    :href="route('loans.index', { type: 'piutang' })"
                    class="m3-loans__tab"
                    :class="{ 'm3-loans__tab--active': !isDebt }"
                    :aria-current="!isDebt ? 'page' : undefined"
                >
                    <AppIcon icon="hand-coins" class="m3-loans__tab-icon" />
                    <span>{{ t('types.receivable') }}</span>
                </Link>
            </nav>

            <div class="m3-loans__layout">
                <aside class="m3-loans__aside">
                    <M3Card variant="elevated" padding="lg" class="m3-loans__hero">
                        <div class="m3-loans__hero-head">
                            <span class="m3-loans__hero-icon" aria-hidden="true">
                                <AppIcon :icon="typeIcon" class="m3-loans__hero-icon-glyph" />
                            </span>
                            <M3IconButton
                                :aria-label="t('header.toggleBalance')"
                                @click="toggleVisibility"
                            >
                                <AppIcon :icon="isBalanceVisible ? 'eye' : 'eye-off'" />
                            </M3IconButton>
                        </div>

                        <p class="m3-loans__hero-label">{{ totalLabel }}</p>
                        <p class="m3-loans__total" aria-live="polite">
                            <span class="m3-loans__currency">{{ t('common.currency') }}</span>
                            <span class="m3-loans__total-value">{{ displayAmount(total) }}</span>
                        </p>
                        <p class="m3-loans__count">{{ countLabel }}</p>

                        <div v-if="nearestDueLoan" class="m3-loans__hero-due">
                            <span class="m3-loans__due-icon" aria-hidden="true">
                                <AppIcon icon="calendar-days" class="m3-loans__due-glyph" />
                            </span>
                            <div class="m3-loans__due-copy">
                                <p class="m3-loans__due-title">{{ t('upcomingDebts.title') }}</p>
                                <p class="m3-loans__due-value">{{ formatDate(nearestDueLoan.due_date) }}</p>
                            </div>
                            <span v-if="overdueCount > 0" class="m3-loans__overdue">
                                {{ t('upcomingDebts.overdue') }} · {{ overdueCount }}
                            </span>
                        </div>
                    </M3Card>
                </aside>

                <section class="m3-loans__list-section" :aria-labelledby="`m3-loans-list-${isDebt ? 'debt' : 'receivable'}`">
                    <header class="m3-loans__list-head">
                        <span class="m3-loans__list-dot" aria-hidden="true" />
                        <h2 :id="`m3-loans-list-${isDebt ? 'debt' : 'receivable'}`" class="m3-loans__list-title">
                            {{ isDebt ? t('loan.fromWhom') : t('loan.toWhom') }}
                        </h2>
                        <span class="m3-loans__list-count">{{ sortedLoans.length }}</span>
                    </header>

                    <M3Card v-if="sortedLoans.length > 0" variant="outlined" padding="none" class="m3-loans__list">
                        <M3ListItem
                            v-for="(loan, index) in sortedLoans"
                            :key="`${loan.subject}-${loan.type ?? (isDebt ? 'debt' : 'receivable')}`"
                            :divider="index < sortedLoans.length - 1"
                        >
                            <template #icon>
                                <span class="m3-loans__avatar" aria-hidden="true">{{ initial(loan.subject) }}</span>
                            </template>

                            <span class="m3-loans__subject">{{ loan.subject }}</span>

                            <template #supporting>
                                <span class="m3-loans__support">
                                    <span>{{ t('loan.since') }} {{ sinceLabel(loan) }}</span>
                                    <span class="m3-loans__support-sep" aria-hidden="true">·</span>
                                    <span>{{ loan.age ?? 0 }} {{ t('loan.days') }}</span>
                                </span>
                            </template>

                            <template #trailing>
                                <span class="m3-loans__trailing">
                                    <span class="m3-loans__amount">
                                        <span class="m3-loans__amount-currency">{{ t('common.currency') }}</span>
                                        {{ displayAmount(loan.balance) }}
                                    </span>
                                    <span
                                        v-if="loan.due_date"
                                        class="m3-loans__due-chip"
                                        :class="`m3-loans__due-chip--${dueTone(loan)}`"
                                    >
                                        <AppIcon icon="calendar-days" class="m3-loans__due-chip-icon" />
                                        <span class="m3-loans__sr-only">{{ dueAriaLabel(loan) }}</span>
                                        <span aria-hidden="true">{{ dueLabel(loan) }}</span>
                                    </span>
                                </span>
                            </template>
                        </M3ListItem>
                    </M3Card>

                    <M3Card v-else variant="outlined" padding="lg" class="m3-loans__empty">
                        <span class="m3-loans__empty-icon" aria-hidden="true">
                            <AppIcon icon="check-circle-2" class="m3-loans__empty-glyph" />
                        </span>
                        <p class="m3-loans__empty-title">{{ t('loan.clean') }}</p>
                        <p class="m3-loans__empty-text">
                            {{ isDebt ? t('loan.cleanMsg') : t('loan.cleanMsgRcv') }}
                        </p>
                        <M3Button :href="route('transactions.create')" variant="tonal">
                            <template #leading>
                                <AppIcon icon="plus" />
                            </template>
                            {{ t('nav.newRecord') }}
                        </M3Button>
                    </M3Card>
                </section>
            </div>
        </div>
    </M3Layout>
</template>

<style scoped>
.m3-loans {
    --m3-loans-empty-max: 22rem;

    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
    width: 100%;
    max-width: var(--m3-size-content-max);
    margin: 0 auto;
    padding: var(--m3-space-4);
}

.m3-loans--debt {
    --m3-loans-accent: var(--m3-color-debt);
    --m3-loans-container: var(--m3-color-debt-container);
    --m3-loans-on-container: var(--m3-color-on-debt-container);
}

.m3-loans--receivable {
    --m3-loans-accent: var(--m3-color-receivable);
    --m3-loans-container: var(--m3-color-receivable-container);
    --m3-loans-on-container: var(--m3-color-on-receivable-container);
}

/* ── Segmented control ─────────────────────────────────────────── */
.m3-loans__tabs {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--m3-space-1);
    padding: var(--m3-space-1);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container);
}

.m3-loans__tab {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-2);
    min-height: var(--m3-size-touch-target-lg);
    padding: 0 var(--m3-space-3);
    border-radius: var(--m3-radius-full);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-label-large-weight);
    letter-spacing: var(--m3-type-label-large-tracking);
    text-decoration: none;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-loans__tab:hover {
    color: var(--m3-color-on-surface);
}

.m3-loans__tab:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-loans__tab--active {
    background: var(--m3-loans-container);
    color: var(--m3-loans-on-container);
    font-weight: var(--m3-type-emphasized-label-large-weight);
}

.m3-loans__tab-icon {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
    flex-shrink: 0;
}

/* ── Layout ────────────────────────────────────────────────────── */
.m3-loans__layout {
    display: grid;
    gap: var(--m3-space-5);
    min-width: 0;
}

.m3-loans__aside {
    min-width: 0;
}

.m3-loans__hero {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-loans__hero-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
    margin-bottom: var(--m3-space-3);
}

.m3-loans__hero-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
    border-radius: var(--m3-radius-full);
    background: var(--m3-loans-container);
    color: var(--m3-loans-accent);
}

.m3-loans__hero-icon-glyph {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-loans__hero-label {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
}

.m3-loans__total {
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

.m3-loans__currency {
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-title-medium-weight);
}

.m3-loans__count {
    margin: var(--m3-space-1) 0 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

.m3-loans__hero-due {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--m3-space-2);
    margin-top: var(--m3-space-3);
    padding-top: var(--m3-space-3);
    border-top: 1px solid var(--m3-color-outline-variant);
    min-width: 0;
}

.m3-loans__due-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-loans-accent);
}

.m3-loans__due-glyph {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-loans__due-copy {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-0-5);
    min-width: 0;
    flex: 1;
}

.m3-loans__due-title {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    text-transform: uppercase;
}

.m3-loans__due-value {
    margin: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    letter-spacing: var(--m3-type-title-small-tracking);
}

.m3-loans__overdue {
    flex-shrink: 0;
    padding: var(--m3-space-1) var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-loans-container);
    color: var(--m3-loans-on-container);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    white-space: nowrap;
}

/* ── List ──────────────────────────────────────────────────────── */
.m3-loans__list-section {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-loans__list-head {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    padding: 0 var(--m3-space-1);
    min-width: 0;
}

.m3-loans__list-dot {
    flex-shrink: 0;
    width: var(--m3-space-2);
    height: var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-loans-accent);
}

.m3-loans__list-title {
    margin: 0;
    min-width: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    letter-spacing: var(--m3-type-title-small-tracking);
}

.m3-loans__list-count {
    margin-left: auto;
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
}

.m3-loans__list {
    overflow: hidden;
}

.m3-loans__avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-loans-container);
    color: var(--m3-loans-on-container);
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
}

.m3-loans__subject {
    font-weight: var(--m3-type-emphasized-body-large-weight);
}

.m3-loans__support {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1);
    min-width: 0;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.m3-loans__support-sep {
    color: var(--m3-color-outline);
}

.m3-loans__trailing {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: var(--m3-space-1);
    min-width: 0;
}

.m3-loans__amount {
    display: inline-flex;
    align-items: baseline;
    gap: var(--m3-space-1);
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.m3-loans__amount-currency {
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
}

.m3-loans__due-chip {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    white-space: nowrap;
}

.m3-loans__due-chip--today,
.m3-loans__due-chip--soon {
    background: var(--m3-loans-container);
    color: var(--m3-loans-on-container);
    font-weight: var(--m3-type-emphasized-label-small-weight);
}

.m3-loans__due-chip--overdue {
    border: 1px solid var(--m3-loans-accent);
    background: var(--m3-loans-container);
    color: var(--m3-loans-on-container);
    font-weight: var(--m3-type-emphasized-label-small-weight);
}

.m3-loans__due-chip-icon {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
}

.m3-loans__sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* ── Empty ─────────────────────────────────────────────────────── */
.m3-loans__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
    text-align: center;
}

.m3-loans__empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-space-12);
    height: var(--m3-space-12);
    border-radius: var(--m3-radius-full);
    background: var(--m3-loans-container);
    color: var(--m3-loans-accent);
}

.m3-loans__empty-glyph {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-loans__empty-title {
    margin: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
}

.m3-loans__empty-text {
    margin: 0;
    max-width: var(--m3-loans-empty-max);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

@media (min-width: 64rem) {
    .m3-loans__layout {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.6fr);
        gap: var(--m3-space-6);
        align-items: start;
    }

    .m3-loans__aside {
        position: sticky;
        top: calc(var(--m3-size-appbar) + var(--m3-space-4));
    }

    .m3-loans__total {
        font-size: var(--m3-type-display-small-size);
        line-height: var(--m3-type-display-small-line-height);
        font-weight: var(--m3-type-emphasized-display-small-weight);
        letter-spacing: var(--m3-type-display-small-tracking);
    }
}

@media (max-width: 22.4375rem) {
    .m3-loans__tabs {
        grid-template-columns: minmax(0, 1fr);
    }

    .m3-loans__list-head {
        flex-wrap: wrap;
    }

    .m3-loans__list-count {
        margin-left: 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .m3-loans__tab {
        transition: none;
    }
}
</style>
