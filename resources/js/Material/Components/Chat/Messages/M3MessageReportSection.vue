<script setup>
/**
 * M3MessageReportSection — section laporan dari bot (paritas Default).
 * Semua varian dipertahankan: saldo header, saldo list, income/expense/
 * transactions (via M3TransactionSummaryList), wallet, asset, category, default.
 * Warna nominal mengikuti semantik finansial; ikon wallet/kategori via
 * Material/utils/m3IconColor.
 */
import { useI18n } from 'vue-i18n'
import { computed, reactive } from 'vue'
import M3TransactionSummaryList from './M3TransactionSummaryList.vue'
import M3MarkdownRenderer from './M3MarkdownRenderer.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { getM3WalletIconClass } from '@/Material/utils/m3IconColor.js'
import { toLucide } from '@/utils/chatIcons.js'

const { t } = useI18n()

const props = defineProps({
    component: { type: Object, required: true },
    content:   { type: Array, default: () => [] },
})

const variant = computed(() => {
    const key = props.component.translationKey || ''
    if (key.includes('balance') || key.includes('saldo') || key.includes('balance_title') || key.includes('balance_list')) return 'saldo'
    if (key.includes('wallet') || key.includes('wallet_title')) return 'wallet'
    if (key.includes('asset') || key.includes('asset_title')) return 'asset'
    if (key.includes('transaction_today') || key.includes('transaction_today_title')) return 'transactions'
    if (key.includes('category') || key.includes('category_title')) return 'category'
    if (key.includes('income') || key.includes('income_title')) return 'income'
    if (key.includes('expense') || key.includes('expense_title')) return 'expense'
    return 'default'
})

const stripLeadingEmoji = (text) =>
    String(text ?? '').replace(/^(\p{Extended_Pictographic}[\u{FE0F}\u{200D}\u{20E3}]*)[\s]*/u, '')

const stripMarkdownStars = (text) => String(text ?? '').replace(/\*\*/g, '').trim()

const isMeaningful = (v) => v !== null && v !== undefined && String(v).trim() !== '' && String(v).trim() !== '-'

const titleText = computed(() => {
    const raw = props.component.title || (props.component.translationKey ? t(props.component.translationKey) : '')
    const cleaned = stripMarkdownStars(raw)
    return props.component.emoji ? stripLeadingEmoji(cleaned) : cleaned
})

const hasStructuredItems = computed(() =>
    Array.isArray(props.component.items) && props.component.items.length > 0 && typeof props.component.items[0] === 'object'
)

const isSaldoHeader = computed(() =>
    variant.value === 'saldo' &&
    props.component.translationKey?.includes('balance_title') &&
    props.component.items?.some?.(item => item?.label?.includes?.('Total Saldo') || item?.label?.includes?.('Total Balance'))
)

const isSaldoList = computed(() =>
    variant.value === 'saldo' &&
    props.component.translationKey?.includes('balance_list')
)

const isSummarizable = computed(() =>
    (variant.value === 'income' || variant.value === 'expense' || variant.value === 'transactions') && hasStructuredItems.value
)

const hasStructuredCategorySections = computed(() =>
    variant.value === 'category' &&
    Array.isArray(props.component.items) &&
    props.component.items.length > 0 &&
    typeof props.component.items[0] === 'object' &&
    'categories' in props.component.items[0]
)

function parseItem(item) {
    if (!item || typeof item !== 'string') return null
    if (!item.includes(' — ')) return null
    const parts = item.split(' — ')
    if (parts.length < 4) return null
    const parsed = { date: parts[0], type: parts[1], category: parts[2], amount: parts[3], wallet: parts[4] || '' }
    if (!isMeaningful(parsed.date)) parsed.date = ''
    if (!isMeaningful(parsed.category)) parsed.category = ''
    if (!isMeaningful(parsed.wallet)) parsed.wallet = ''
    return parsed
}

function getWalletTypeLabel(groupType) {
    if (!groupType) return ''
    const labels = {
        'Asset': t('chat.command.wallet_type_asset'),
        'Liquid': t('chat.command.wallet_type_liquid'),
        'System': t('chat.command.wallet_type_system'),
    }
    return labels[groupType] || groupType
}

function isIconUrl(icon) {
    return icon && (icon.includes('.') || icon.startsWith('http') || icon.includes('/'))
}

function defaultIcon(value) {
    return value && value !== 'wallet' ? value : 'credit-card'
}

const failedIcons = reactive(new Set())

const walletIconClass = computed(() => getM3WalletIconClass())

const typeAmountClass = (type) => {
    const key = String(type ?? '').toLowerCase()
    if (key === 'income') return 'm3-report__amount--income'
    if (key === 'expense') return 'm3-report__amount--expense'
    return 'm3-report__amount--transfer'
}

const typeDotClass = (type) => {
    const key = String(type ?? '').toLowerCase()
    if (key === 'income') return 'm3-report__dot--income'
    if (key === 'expense') return 'm3-report__dot--expense'
    return 'm3-report__dot--transfer'
}
</script>

<template>
    <!-- Saldo header (total + jumlah dompet) -->
    <div v-if="isSaldoHeader" class="m3-report m3-report--plain">
        <div class="m3-report__head">
            <div class="m3-report__title-row">
                <AppIcon v-if="component.emoji" :icon="toLucide(component.emoji)" iconClass="m3-report__title-icon" fallback="wallet" />
                <h3 class="m3-report__title">
                    <M3MarkdownRenderer :content="titleText" inline />
                </h3>
            </div>
            <div class="m3-report__meta-row">
                <template v-for="(item, idx) in component.items" :key="idx">
                    <div class="m3-report__meta-pair">
                        <span class="m3-report__meta-label">{{ item.label }}</span>
                        <span v-if="item.value" class="m3-report__meta-value">{{ item.value }}</span>
                    </div>
                    <span v-if="idx < component.items.length - 1" class="m3-report__meta-sep">·</span>
                </template>
            </div>
        </div>
    </div>

    <!-- Saldo list (daftar dompet) -->
    <div v-else-if="isSaldoList" class="m3-report m3-report--plain">
        <div class="m3-report__list">
            <div v-for="(item, idx) in component.items" :key="idx" class="m3-report__row">
                <div class="m3-report__row-left">
                    <img
                        v-if="isIconUrl(item.icon) && !failedIcons.has(idx)"
                        :src="item.icon"
                        class="m3-report__wallet-photo"
                        alt=""
                        @error="failedIcons.add(idx)"
                    />
                    <AppIcon v-else :icon="toLucide(defaultIcon(item.icon))" :class="['m3-report__row-icon', walletIconClass]" fallback="credit-card" />
                    <div class="m3-report__row-text">
                        <span class="m3-report__row-name">{{ item.name }}</span>
                        <span v-if="item.group_type" class="m3-report__row-sub">{{ getWalletTypeLabel(item.group_type) }}</span>
                    </div>
                </div>
                <span class="m3-report__row-amount">{{ item.amount }}</span>
            </div>
        </div>
    </div>

    <!-- income / expense / transactions terstruktur -->
    <M3TransactionSummaryList
        v-else-if="isSummarizable"
        :title="titleText"
        :emoji="component.emoji"
        :items="component.items"
        :total="component.total"
        :count="component.count"
        :count-label="variant === 'saldo' ? 'dompet' : 'transaksi'"
        :accent="variant === 'expense' ? 'rose' : 'emerald'"
    />

    <!-- Varian lain / fallback item string -->
    <div
        v-else
        class="m3-report"
        :class="variant === 'income' ? 'm3-report--income' : variant === 'expense' ? 'm3-report--expense' : ''"
    >
        <div class="m3-report__head">
            <div class="m3-report__title-row">
                <AppIcon v-if="component.emoji" :icon="toLucide(component.emoji)" iconClass="m3-report__title-icon" fallback="bar-chart-3" />
                <h3 class="m3-report__title">
                    <M3MarkdownRenderer :content="titleText" inline />
                </h3>
            </div>
            <div v-if="variant === 'asset'" class="m3-report__meta-row">
                <span v-if="component.count">{{ component.count }} {{ t('chat.command.asset_count') }}</span>
                <span v-if="component.total" class="m3-report__asset-total">Total {{ component.total }}</span>
            </div>
        </div>

        <div class="m3-report__list">
            <!-- Wallet: satu baris per dompet -->
            <template v-if="variant === 'wallet'">
                <div v-for="(item, idx) in component.items" :key="idx" class="m3-report__row">
                    <span class="m3-report__row-name">{{ item.split(':')[0] ?? item }}</span>
                    <span class="m3-report__row-amount">{{ item.split(':').slice(1).join(':') ?? '' }}</span>
                </div>
            </template>

            <!-- Asset: nama kiri, nominal kanan -->
            <template v-else-if="variant === 'asset'">
                <div v-for="(item, idx) in component.items" :key="idx" class="m3-report__row">
                    <span class="m3-report__row-sub">{{ item.split(':')[0] ?? item }}</span>
                    <span class="m3-report__row-amount">{{ item.split(':').slice(1).join(':') ?? '' }}</span>
                </div>
            </template>

            <!-- Category: section per tipe -->
            <template v-else-if="variant === 'category'">
                <div v-if="hasStructuredCategorySections" class="m3-report__category">
                    <div v-for="(section, sIdx) in component.items" :key="sIdx" class="m3-report__category-section">
                        <div class="m3-report__category-head">
                            <AppIcon :icon="toLucide(section.type_icon)" iconClass="m3-report__category-icon" fallback="folder" />
                            <h4 class="m3-report__category-name">
                                {{ section.label_key ? t(section.label_key) : section.type_name }}
                            </h4>
                            <span class="m3-report__category-count">({{ section.categories.length }})</span>
                        </div>
                        <div class="m3-report__category-items">
                            <div v-for="(cat, cIdx) in section.categories" :key="cIdx" class="m3-report__category-item">
                                <span class="m3-report__category-dot" aria-hidden="true" />
                                <span>{{ cat }}</span>
                            </div>
                        </div>
                        <div v-if="sIdx < component.items.length - 1" class="m3-report__category-sep" />
                    </div>
                </div>
                <div v-else class="m3-report__category-flat">
                    <div v-for="(item, idx) in component.items" :key="idx" class="m3-report__category-item">
                        <span class="m3-report__category-dot m3-report__category-dot--alt" aria-hidden="true" />
                        <span>{{ item }}</span>
                    </div>
                </div>
            </template>

            <!-- Default/transactions fallback item string -->
            <template v-else>
                <div v-for="(item, idx) in component.items" :key="idx">
                    <div v-if="parseItem(item)" class="m3-report__txn-row">
                        <div class="m3-report__txn-left">
                            <span v-if="parseItem(item).date" class="m3-report__txn-date">{{ parseItem(item).date }}</span>
                            <span class="m3-report__dot" :class="typeDotClass(parseItem(item).type)" aria-hidden="true" />
                            <span v-if="parseItem(item).category" class="m3-report__txn-category">{{ parseItem(item).category }}</span>
                            <span v-if="parseItem(item).wallet" class="m3-report__txn-wallet">{{ parseItem(item).wallet }}</span>
                        </div>
                        <span class="m3-report__txn-amount" :class="typeAmountClass(parseItem(item).type)">{{ parseItem(item).amount }}</span>
                    </div>
                    <div v-else class="m3-report__string-row">
                        <AppIcon icon="sparkles" iconClass="m3-report__sparkle" />
                        <span>{{ item }}</span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<style scoped>
.m3-report {
    margin: var(--m3-space-1-5) var(--m3-space-2);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-lowest);
    overflow: hidden;
}

.m3-report--plain {
    background: color-mix(in srgb, var(--m3-color-surface-container-lowest) 60%, transparent);
}

.m3-report--income {
    border-color: color-mix(in srgb, var(--m3-color-income) 30%, var(--m3-color-outline-variant));
}

.m3-report--expense {
    border-color: color-mix(in srgb, var(--m3-color-expense) 30%, var(--m3-color-outline-variant));
}

.m3-report__head {
    padding: var(--m3-space-3) var(--m3-space-3) var(--m3-space-2);
}

.m3-report__title-row {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-report__title-icon {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
    color: var(--m3-color-primary);
}

.m3-report__title {
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-on-surface);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-report__meta-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--m3-space-2);
    margin-top: var(--m3-space-1);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-report__meta-pair {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1);
}

.m3-report__meta-label {
    color: var(--m3-color-on-surface-variant);
}

.m3-report__meta-value {
    font-weight: var(--m3-type-emphasized-label-small-weight);
    font-variant-numeric: tabular-nums;
    color: var(--m3-color-on-surface);
}

.m3-report__meta-sep {
    color: var(--m3-color-outline);
}

.m3-report__asset-total {
    font-weight: var(--m3-type-emphasized-label-small-weight);
    font-variant-numeric: tabular-nums;
    color: var(--m3-color-income);
}

.m3-report__list > * + * {
    border-top: 1px solid color-mix(in srgb, var(--m3-color-outline-variant) 55%, transparent);
}

.m3-report__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
    padding: var(--m3-space-2-5) var(--m3-space-3);
    transition: background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-report__row:hover {
    background: color-mix(in srgb, var(--m3-color-on-surface) 4%, transparent);
}

.m3-report__row-left {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2-5);
    min-width: 0;
    flex: 1;
}

.m3-report__row-icon {
    width: 1.25rem;
    height: 1.25rem;
    flex-shrink: 0;
}

.m3-report__wallet-photo {
    width: 1.25rem;
    height: 1.25rem;
    border-radius: var(--m3-radius-full);
    object-fit: cover;
    flex-shrink: 0;
}

.m3-report__row-text {
    min-width: 0;
}

.m3-report__row-name {
    display: block;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    font-weight: var(--m3-type-emphasized-body-small-weight);
    color: var(--m3-color-on-surface);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-report__row-sub {
    display: block;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-report__row-amount {
    flex-shrink: 0;
    margin-inline-start: var(--m3-space-2);
    font-size: var(--m3-type-body-small-size);
    font-weight: var(--m3-type-emphasized-body-small-weight);
    font-variant-numeric: tabular-nums;
    color: var(--m3-color-on-surface);
}

/* Kategori */
.m3-report__category {
    padding: var(--m3-space-3);
}

.m3-report__category-section {
    margin-bottom: var(--m3-space-4);
}

.m3-report__category-section:last-child {
    margin-bottom: 0;
}

.m3-report__category-head {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    margin-bottom: var(--m3-space-2);
}

.m3-report__category-icon {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
    color: var(--m3-color-primary);
}

.m3-report__category-name {
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-on-surface);
}

.m3-report__category-count {
    font-size: var(--m3-type-label-small-size);
    color: var(--m3-color-on-surface-variant);
    font-variant-numeric: tabular-nums;
}

.m3-report__category-items {
    padding-inline-start: var(--m3-space-5);
}

.m3-report__category-item {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-0-5) 0;
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-on-surface);
}

.m3-report__category-dot {
    width: 0.25rem;
    height: 0.25rem;
    border-radius: var(--m3-radius-full);
    flex-shrink: 0;
    background: var(--m3-color-primary);
}

.m3-report__category-dot--alt {
    background: var(--m3-color-outline);
}

.m3-report__category-sep {
    height: 1px;
    margin: var(--m3-space-4) 0;
    background: color-mix(in srgb, var(--m3-color-outline-variant) 55%, transparent);
}

.m3-report__category-flat {
    padding: var(--m3-space-2-5) var(--m3-space-3);
}

/* Baris transaksi string */
.m3-report__txn-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
    padding: var(--m3-space-2) var(--m3-space-3);
    transition: background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-report__txn-row:hover {
    background: color-mix(in srgb, var(--m3-color-on-surface) 4%, transparent);
}

.m3-report__txn-left {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-width: 0;
    flex: 1;
}

.m3-report__txn-date {
    flex-shrink: 0;
    width: 2.25rem;
    font-size: var(--m3-type-label-small-size);
    color: var(--m3-color-on-surface-variant);
    font-variant-numeric: tabular-nums;
}

.m3-report__dot {
    width: 0.375rem;
    height: 0.375rem;
    border-radius: var(--m3-radius-full);
    flex-shrink: 0;
}

.m3-report__dot--income { background: var(--m3-color-income); }
.m3-report__dot--expense { background: var(--m3-color-expense); }
.m3-report__dot--transfer { background: var(--m3-color-transfer); }

.m3-report__txn-category {
    font-size: var(--m3-type-body-small-size);
    font-weight: var(--m3-type-emphasized-body-small-weight);
    color: var(--m3-color-on-surface);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-report__txn-wallet {
    font-size: var(--m3-type-label-small-size);
    color: var(--m3-color-on-surface-variant);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 3.75rem;
}

.m3-report__txn-amount {
    flex-shrink: 0;
    font-size: var(--m3-type-body-small-size);
    font-weight: var(--m3-type-emphasized-body-small-weight);
    font-variant-numeric: tabular-nums;
}

.m3-report__amount--income { color: var(--m3-color-income); }
.m3-report__amount--expense { color: var(--m3-color-expense); }
.m3-report__amount--transfer { color: var(--m3-color-transfer); }

.m3-report__string-row {
    display: flex;
    align-items: flex-start;
    gap: var(--m3-space-2);
    padding: var(--m3-space-2) var(--m3-space-3);
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-on-surface-variant);
}

.m3-report__sparkle {
    width: 0.875rem;
    height: 0.875rem;
    flex-shrink: 0;
    margin-top: 0.125rem;
    color: var(--m3-color-primary);
}
</style>
