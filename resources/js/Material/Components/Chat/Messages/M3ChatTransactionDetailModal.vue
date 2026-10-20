<script setup>
/**
 * M3ChatTransactionDetailModal — detail transaksi chat versi M3.
 * Tab Detail: nominal, kategori, wallet, tanggal, catatan.
 * Tab AI Stats: sumber, intent, model, tokens, confidence, latency,
 *   raw prompt & JSON metadata (akordeon).
 * Dialog di desktop, bottom sheet di mobile — paritas kontrak aksi Default.
 */
import { ref, computed, watch } from 'vue'
import { useClipboard } from '@/Composables/useClipboard.js'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/Composables/useToast'
import AppIcon from '@/Components/AppIcon.vue'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3CircularProgress from '@/Material/Components/M3CircularProgress.vue'

const { t, locale } = useI18n()
const { showToast } = useToast()

const props = defineProps({
    modelValue:  { type: Boolean, default: false },
    transaction: { type: Object,  default: null },
    metadata:    { type: Object,  default: () => ({}) },
    initialTab:  { type: String, default: 'detail' },
})

const emit = defineEmits(['update:modelValue', 'deleted'])

function close() { emit('update:modelValue', false) }

const isDeleting = ref(false)
const showDeleteConfirm = ref(false)
const activeTab = ref(props.initialTab)
watch(() => props.modelValue, (open) => {
    if (open) activeTab.value = props.initialTab || 'detail'
})
watch(() => props.initialTab, (v) => {
    if (props.modelValue) activeTab.value = v
})

const openAccordion = ref(null)
function toggleAccordion(name) {
    openAccordion.value = openAccordion.value === name ? null : name
}

const { copied: copySuccess, copy: copyToClipboard } = useClipboard()
async function copyId() {
    const id = props.transaction?.reference_number ?? props.transaction?.id ?? ''
    await copyToClipboard(String(id))
}

const sourceInfo = computed(() => {
    const ref = props.transaction?.reference_number ?? ''
    const prefix = ref.split('-')[0] ?? ''
    const map = {
        'WEB': { label: t('chat.source.web'),      icon: 'message-circle' },
        'TEL': { label: t('chat.source.telegram'), icon: 'send' },
        'WA':  { label: t('chat.source.whatsapp'), icon: 'smartphone' },
        'DSC': { label: t('chat.source.discord'),  icon: 'gamepad-2' },
        'API': { label: t('chat.source.api'),      icon: 'zap' },
        'IMP': { label: t('chat.source.import'),   icon: 'folder-down' },
        'MNL': { label: t('chat.source.manual'),   icon: 'pencil' },
    }
    return map[prefix] ?? { label: t('chat.source.dashboard'), icon: 'globe' }
})

function formatDateTime(dt) {
    if (!dt) return '-'
    try {
        return new Date(dt).toLocaleString(locale.value === 'en' ? 'en-US' : 'id-ID', {
            year: 'numeric', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit',
            hour12: false,
        })
    } catch { return dt }
}

const confidenceLabel = computed(() => {
    const c = props.transaction?.confidence ?? props.metadata?.confidence
    if (c === null || c === undefined) return null
    const pct   = Math.round(c * 100)
    const level = pct >= 90
        ? t('chatTransaction.confidence.high')
        : pct >= 70
            ? t('chatTransaction.confidence.medium')
            : t('chatTransaction.confidence.low')
    const cls = pct >= 90 ? 'm3-cdm__conf--high' : pct >= 70 ? 'm3-cdm__conf--medium' : 'm3-cdm__conf--low'
    return { pct, level, cls }
})

const latencyLabel = computed(() => {
    const ms = props.metadata?.latency_ms
    if (ms === null || ms === undefined || ms === '') return null
    const num = Number(ms)
    if (Number.isNaN(num)) return null
    return num >= 1000 ? `${(num / 1000).toFixed(2)} ${t('chatTransaction.seconds')}` : `${num} ms`
})

const modelLabel = computed(() => {
    const m = props.metadata?.model ?? ''
    return m || props.metadata?.provider || null
})

const intentLabel = computed(() => {
    const intent = props.metadata?.intent ?? ''
    const map = {
        'single_transaction': t('chatTransaction.intent.single'),
        'multi_transaction':  t('chatTransaction.intent.multi'),
        'command':            t('chatTransaction.intent.command'),
        'error':              t('common.error'),
        'draft':              t('transaction.draft'),
    }
    return map[intent] ?? (intent || null)
})

const parseStatus = computed(() =>
    props.transaction?.is_cancelled
        ? { label: t('transaction.cancelled'), cls: 'm3-cdm__status--cancelled' }
        : props.transaction?.is_cleared
        ? { label: t('chatTransaction.aiParsed'), cls: 'm3-cdm__status--cleared' }
        : { label: t('transaction.draft'), cls: 'm3-cdm__status--draft' }
)

const tokens = computed(() => props.metadata?.total_tokens ?? props.metadata?.tokens ?? props.metadata?.usage?.total_tokens ?? null)
const isRegexFallback = computed(() => (props.metadata?.model === 'regex' || props.metadata?.provider === 'local-rules'))

const detailTypeConfig = computed(() => {
    const map = {
        income:     { label: t('types.income'),      icon: 'trending-up',     badge: 'm3-cdm__badge--income' },
        expense:    { label: t('types.expense'),     icon: 'trending-down',   badge: 'm3-cdm__badge--expense' },
        transfer:   { label: t('types.transfer'),    icon: 'arrow-left-right', badge: 'm3-cdm__badge--transfer' },
        debt:       { label: t('types.debt'),        icon: 'hand-coins',      badge: 'm3-cdm__badge--debt' },
        receivable: { label: t('types.receivable'),  icon: 'handshake',       badge: 'm3-cdm__badge--receivable' },
        other:      { label: t('transaction.title'), icon: 'file-text',       badge: 'm3-cdm__badge--neutral' },
    }
    return map[props.transaction?.type_key ?? 'other'] ?? map.other
})

const detailAmountClass = computed(() => {
    const key = props.transaction?.type_key ?? 'other'
    const map = {
        income:     'm3-cdm__amount--income',
        expense:    'm3-cdm__amount--expense',
        transfer:   'm3-cdm__amount--transfer',
        debt:       'm3-cdm__amount--debt',
        receivable: 'm3-cdm__amount--receivable',
    }
    return map[key] ?? ''
})

const jsonMeta = computed(() => JSON.stringify(props.metadata ?? {}, null, 2))

function deleteTransaction() {
    if (!props.transaction?.id || isDeleting.value) return
    showDeleteConfirm.value = true
}

async function confirmDeleteTransaction() {
    if (!props.transaction?.id || isDeleting.value) return

    isDeleting.value = true
    try {
        const { data } = await axios.delete(route('chat.transaction.cancel', { id: props.transaction.id }))
        if (data.success) {
            emit('deleted')
            showDeleteConfirm.value = false
            showToast(t('toast.deleted'), 'success')
            close()
        }
    } catch (e) {
        if (e.response?.status === 404) {
            emit('deleted')
            close()
        }
        showToast(t('toast.error'), 'error')
        console.error('deleteTransaction error', e)
    } finally {
        isDeleting.value = false
    }
}
</script>

<template>
    <M3Modal
        :show="modelValue && !!transaction"
        :aria-label="$t('transaction.detail.title')"
        variant="dialog"
        mobile-variant="sheet"
        max-width="lg"
        @close="close"
    >
        <template #header>
            <div class="m3-cdm__head">
                <div class="m3-cdm__head-top">
                    <div class="m3-cdm__head-titles">
                        <h2 class="m3-cdm__title">{{ $t('transaction.detail.title') }}</h2>
                        <p class="m3-cdm__subtitle" :class="parseStatus.cls">{{ parseStatus.label }}</p>
                    </div>
                    <span class="m3-cdm__badge" :class="detailTypeConfig.badge">
                        <AppIcon :icon="detailTypeConfig.icon" iconClass="m3-cdm__badge-icon" />
                        {{ transaction?.type_label ?? detailTypeConfig.label }}
                    </span>
                </div>

                <div class="m3-cdm__tabs" role="tablist" :aria-label="$t('transaction.detail.title')">
                    <button
                        type="button"
                        role="tab"
                        id="m3-cdm-tab-detail"
                        :aria-selected="activeTab === 'detail'"
                        :class="['m3-cdm__tab', activeTab === 'detail' ? 'm3-cdm__tab--active' : '']"
                        @click="activeTab = 'detail'"
                    >
                        Detail
                    </button>
                    <button
                        type="button"
                        role="tab"
                        id="m3-cdm-tab-stats"
                        :aria-selected="activeTab === 'stats'"
                        :class="['m3-cdm__tab', activeTab === 'stats' ? 'm3-cdm__tab--active' : '']"
                        @click="activeTab = 'stats'"
                    >
                        <AppIcon icon="info" iconClass="m3-cdm__tab-icon" />
                        AI Stats
                    </button>
                </div>
            </div>
        </template>

        <div
            v-if="activeTab === 'detail'"
            role="tabpanel"
            aria-labelledby="m3-cdm-tab-detail"
            class="m3-cdm"
        >
            <div class="m3-cdm__amount-card">
                <p class="m3-cdm__amount-label">{{ $t('transaction.amount') }}</p>
                <p class="m3-cdm__amount" :class="detailAmountClass">
                    {{ transaction?.amount_formatted ?? transaction?.amount ?? '-' }}
                </p>
                <p v-if="transaction?.notes" class="m3-cdm__amount-note">{{ transaction.notes }}</p>
            </div>

            <div class="m3-cdm__id">
                <p class="m3-cdm__section-label">{{ $t('transaction.detail.transactionId') }}</p>
                <div class="m3-cdm__id-row">
                    <code class="m3-cdm__code">{{ transaction?.reference_number ?? transaction?.id ?? '-' }}</code>
                    <button
                        type="button"
                        class="m3-cdm__copy"
                        :class="copySuccess ? 'm3-cdm__copy--done' : ''"
                        @click="copyId"
                    >
                        <AppIcon :icon="copySuccess ? 'check' : 'copy'" iconClass="m3-cdm__copy-icon" />
                        {{ copySuccess ? $t('chatTransaction.copied') : $t('chatTransaction.copy') }}
                    </button>
                </div>
            </div>

            <div class="m3-cdm__rows">
                <div v-if="transaction?.category" class="m3-cdm__row">
                    <span class="m3-cdm__row-label">{{ $t('transaction.detail.category') }}</span>
                    <span class="m3-cdm__row-value">{{ transaction.category }}</span>
                </div>
                <div v-if="transaction?.source_wallet" class="m3-cdm__row">
                    <span class="m3-cdm__row-label">{{ $t('transaction.detail.wallet') }}</span>
                    <span class="m3-cdm__row-value">{{ transaction.source_wallet }}</span>
                </div>
                <div v-if="transaction?.dest_wallet" class="m3-cdm__row">
                    <span class="m3-cdm__row-label">{{ $t('transaction.detail.to') }} {{ $t('transaction.detail.wallet') }}</span>
                    <span class="m3-cdm__row-value">{{ transaction.dest_wallet }}</span>
                </div>
                <div v-if="transaction?.subject" class="m3-cdm__row">
                    <span class="m3-cdm__row-label">{{ $t('transaction.detail.party') }}</span>
                    <span class="m3-cdm__row-value">{{ transaction.subject }}</span>
                </div>
                <div v-if="transaction?.date || transaction?.created_at" class="m3-cdm__row">
                    <span class="m3-cdm__row-label">{{ $t('transaction.detail.date') }}</span>
                    <span class="m3-cdm__row-value">{{ formatDateTime(transaction.created_at ?? transaction.date) }}</span>
                </div>
                <div class="m3-cdm__row">
                    <span class="m3-cdm__row-label">{{ $t('transaction.detail.note') }}</span>
                    <span class="m3-cdm__row-value m3-cdm__row-value--note">{{ transaction?.notes || $t('transaction.detail.noNote') }}</span>
                </div>
                <div class="m3-cdm__row">
                    <span class="m3-cdm__row-label">{{ $t('common.status') }}</span>
                    <span class="m3-cdm__row-value" :class="parseStatus.cls">{{ parseStatus.label }}</span>
                </div>
            </div>
        </div>

        <div
            v-else
            role="tabpanel"
            aria-labelledby="m3-cdm-tab-stats"
            class="m3-cdm"
        >
            <div class="m3-cdm__id">
                <p class="m3-cdm__section-label">{{ $t('transaction.detail.transactionId') }}</p>
                <div class="m3-cdm__id-row">
                    <code class="m3-cdm__code">{{ transaction?.reference_number ?? transaction?.id ?? '-' }}</code>
                    <button
                        type="button"
                        class="m3-cdm__copy"
                        :class="copySuccess ? 'm3-cdm__copy--done' : ''"
                        @click="copyId"
                    >
                        <AppIcon :icon="copySuccess ? 'check' : 'copy'" iconClass="m3-cdm__copy-icon" />
                        {{ copySuccess ? $t('chatTransaction.copied') : $t('chatTransaction.copy') }}
                    </button>
                </div>
            </div>

            <div class="m3-cdm__rows">
                <div class="m3-cdm__row">
                    <span class="m3-cdm__row-label">{{ $t('chatTransaction.recordedFrom') }}</span>
                    <span class="m3-cdm__row-value m3-cdm__row-value--icon">
                        <AppIcon :icon="sourceInfo.icon" iconClass="m3-cdm__row-icon" />
                        {{ sourceInfo.label }}
                    </span>
                </div>
                <div v-if="intentLabel" class="m3-cdm__row">
                    <span class="m3-cdm__row-label">{{ $t('chatTransaction.intent.label') }}</span>
                    <span class="m3-cdm__row-value">{{ intentLabel }}</span>
                </div>
                <div v-if="modelLabel" class="m3-cdm__row">
                    <span class="m3-cdm__row-label">{{ $t('chatTransaction.processedBy') }}</span>
                    <span class="m3-cdm__row-value">
                        {{ modelLabel }}
                        <span v-if="isRegexFallback" class="m3-cdm__fallback">(regex)</span>
                    </span>
                </div>
                <div v-if="tokens !== null" class="m3-cdm__row">
                    <span class="m3-cdm__row-label">Tokens</span>
                    <span class="m3-cdm__row-value">
                        {{ tokens }}
                        <span v-if="isRegexFallback" class="m3-cdm__fallback">0 (fallback)</span>
                    </span>
                </div>
                <div v-if="latencyLabel" class="m3-cdm__row">
                    <span class="m3-cdm__row-label">{{ $t('chatTransaction.processingDuration') }}</span>
                    <span class="m3-cdm__row-value">{{ latencyLabel }}</span>
                </div>
                <div v-if="confidenceLabel" class="m3-cdm__row">
                    <span class="m3-cdm__row-label">{{ $t('chatTransaction.aiConfidence') }}</span>
                    <span class="m3-cdm__row-value" :class="confidenceLabel.cls">
                        {{ confidenceLabel.pct }}% &mdash; {{ confidenceLabel.level }}
                    </span>
                </div>
                <div class="m3-cdm__row">
                    <span class="m3-cdm__row-label">{{ $t('common.status') }}</span>
                    <span class="m3-cdm__row-value" :class="parseStatus.cls">{{ parseStatus.label }}</span>
                </div>
                <div v-if="transaction?.date || transaction?.created_at" class="m3-cdm__row">
                    <span class="m3-cdm__row-label">{{ $t('chatTransaction.transactionTime') }}</span>
                    <span class="m3-cdm__row-value">{{ formatDateTime(transaction.created_at ?? transaction.date) }}</span>
                </div>
            </div>

            <div class="m3-cdm__accordions">
                <div v-if="metadata?.raw_prompt" class="m3-cdm__accordion">
                    <button
                        type="button"
                        class="m3-cdm__accordion-btn"
                        :aria-expanded="openAccordion === 'prompt'"
                        aria-controls="m3-cdm-prompt"
                        @click="toggleAccordion('prompt')"
                    >
                        <AppIcon icon="message-square" iconClass="m3-cdm__accordion-icon" />
                        <span class="m3-cdm__accordion-label">{{ $t('chatTransaction.rawPrompt') }}</span>
                        <AppIcon
                            icon="chevron-down"
                            iconClass="m3-cdm__accordion-chevron"
                            :class="{ 'm3-cdm__accordion-chevron--open': openAccordion === 'prompt' }"
                        />
                    </button>
                    <div v-show="openAccordion === 'prompt'" id="m3-cdm-prompt" class="m3-cdm__accordion-panel">
                        <div class="m3-cdm__pre-wrap">{{ metadata.raw_prompt }}</div>
                    </div>
                </div>

                <div class="m3-cdm__accordion">
                    <button
                        type="button"
                        class="m3-cdm__accordion-btn"
                        :aria-expanded="openAccordion === 'json'"
                        aria-controls="m3-cdm-json"
                        @click="toggleAccordion('json')"
                    >
                        <AppIcon icon="code-2" iconClass="m3-cdm__accordion-icon" />
                        <span class="m3-cdm__accordion-label">JSON Metadata</span>
                        <AppIcon
                            icon="chevron-down"
                            iconClass="m3-cdm__accordion-chevron"
                            :class="{ 'm3-cdm__accordion-chevron--open': openAccordion === 'json' }"
                        />
                    </button>
                    <div v-show="openAccordion === 'json'" id="m3-cdm-json" class="m3-cdm__accordion-panel">
                        <pre class="m3-cdm__pre">{{ jsonMeta }}</pre>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="m3-cdm__actions">
                <Link
                    v-if="!transaction?.is_cancelled"
                    :href="route('transactions.edit', { transaction: transaction.id })"
                    class="m3-cdm__icon-btn"
                    :title="$t('common.edit')"
                    :aria-label="$t('common.edit')"
                >
                    <AppIcon icon="pencil" iconClass="m3-cdm__action-icon" />
                </Link>
                <button
                    v-if="!transaction?.is_cancelled"
                    type="button"
                    :disabled="isDeleting"
                    class="m3-cdm__icon-btn m3-cdm__icon-btn--danger"
                    :title="$t('common.delete')"
                    :aria-label="$t('common.delete')"
                    @click="deleteTransaction"
                >
                    <M3CircularProgress v-if="isDeleting" size="xs" />
                    <AppIcon v-else icon="trash-2" iconClass="m3-cdm__action-icon" />
                </button>
                <M3Button variant="outlined" full-width @click="close">
                    {{ $t('common.close') }}
                </M3Button>
            </div>
        </template>
    </M3Modal>

    <M3Modal
        :show="showDeleteConfirm"
        :closeable="!isDeleting"
        max-width="sm"
        variant="dialog"
        mobile-variant="sheet"
        :aria-label="$t('chatTransaction.confirmDelete')"
        @close="showDeleteConfirm = false"
    >
        <div class="m3-cdm__dialog">
            <span class="m3-cdm__dialog-icon">
                <AppIcon icon="trash-2" iconClass="m3-cdm__dialog-icon-glyph" />
            </span>
            <h3 class="m3-cdm__dialog-title">{{ $t('common.delete') }}</h3>
            <p class="m3-cdm__dialog-text">{{ t('chatTransaction.confirmDelete') }}</p>
        </div>

        <template #footer>
            <M3Button variant="text" :disabled="isDeleting" @click="showDeleteConfirm = false">
                {{ $t('common.cancel') }}
            </M3Button>
            <M3Button
                variant="filled"
                class="m3-cdm__danger-filled"
                :disabled="isDeleting"
                @click="confirmDeleteTransaction"
            >
                {{ isDeleting ? $t('common.deleting') : $t('btn.delete') }}
            </M3Button>
        </template>
    </M3Modal>
</template>

<style scoped>
.m3-cdm__head {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    width: 100%;
}

.m3-cdm__head-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--m3-space-3);
}

.m3-cdm__head-titles {
    min-width: 0;
}

.m3-cdm__title {
    margin: 0;
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    color: var(--m3-color-on-surface);
}

.m3-cdm__subtitle {
    margin: var(--m3-space-0-5) 0 0;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-cdm__badge {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
    flex-shrink: 0;
    padding: var(--m3-space-1) var(--m3-space-2-5);
    border-radius: var(--m3-radius-full);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    white-space: nowrap;
}

.m3-cdm__badge-icon {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    flex-shrink: 0;
}

.m3-cdm__badge--income { background: var(--m3-color-income-container); color: var(--m3-color-on-income-container); }
.m3-cdm__badge--expense { background: var(--m3-color-expense-container); color: var(--m3-color-on-expense-container); }
.m3-cdm__badge--transfer { background: var(--m3-color-transfer-container); color: var(--m3-color-on-transfer-container); }
.m3-cdm__badge--debt { background: var(--m3-color-debt-container); color: var(--m3-color-on-debt-container); }
.m3-cdm__badge--receivable { background: var(--m3-color-receivable-container); color: var(--m3-color-on-receivable-container); }
.m3-cdm__badge--neutral { background: var(--m3-color-surface-container); color: var(--m3-color-on-surface-variant); }

/* Tabs */
.m3-cdm__tabs {
    display: flex;
    gap: var(--m3-space-1);
    padding: var(--m3-space-1);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container);
    width: fit-content;
}

.m3-cdm__tab {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
    min-height: 2rem;
    padding: 0 var(--m3-space-3);
    border: none;
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-small-size);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-cdm__tab:hover {
    color: var(--m3-color-on-surface);
}

.m3-cdm__tab:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-cdm__tab--active {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-cdm__tab-icon {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
}

/* Body */
.m3-cdm {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
}

.m3-cdm__amount-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-1);
    padding: var(--m3-space-4);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container);
}

.m3-cdm__amount-label {
    margin: 0;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
}

.m3-cdm__amount {
    margin: 0;
    font-size: var(--m3-type-headline-small-size);
    line-height: var(--m3-type-headline-small-line-height);
    font-weight: var(--m3-type-emphasized-headline-small-weight);
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.01em;
    color: var(--m3-color-on-surface);
}

.m3-cdm__amount--income { color: var(--m3-color-income); }
.m3-cdm__amount--expense { color: var(--m3-color-expense); }
.m3-cdm__amount--transfer { color: var(--m3-color-transfer); }
.m3-cdm__amount--debt { color: var(--m3-color-debt); }
.m3-cdm__amount--receivable { color: var(--m3-color-receivable); }

.m3-cdm__amount-note {
    margin: 0;
    max-width: 100%;
    font-size: var(--m3-type-label-small-size);
    color: var(--m3-color-on-surface-variant);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-cdm__section-label {
    margin: 0 0 var(--m3-space-1-5);
    font-size: var(--m3-type-label-small-size);
    color: var(--m3-color-on-surface-variant);
}

.m3-cdm__id-row {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
}

.m3-cdm__code {
    flex: 1;
    min-width: 0;
    padding: var(--m3-space-1-5) var(--m3-space-2-5);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-sm);
    background: var(--m3-color-surface-container);
    font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-on-surface);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-cdm__copy {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
    flex-shrink: 0;
    min-height: var(--m3-size-touch-target);
    padding: 0 var(--m3-space-2-5);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-small-size);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-cdm__copy:hover {
    background: color-mix(in srgb, var(--m3-color-on-surface) 6%, transparent);
}

.m3-cdm__copy:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-cdm__copy--done {
    border-color: var(--m3-color-income);
    color: var(--m3-color-income);
}

.m3-cdm__copy-icon {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
}

/* Rows */
.m3-cdm__rows {
    display: flex;
    flex-direction: column;
}

.m3-cdm__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-3);
    padding: var(--m3-space-3) 0;
    border-bottom: 1px solid color-mix(in srgb, var(--m3-color-outline-variant) 60%, transparent);
}

.m3-cdm__row:last-child {
    border-bottom: none;
}

.m3-cdm__row-label {
    flex-shrink: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-cdm__row-value {
    min-width: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    font-weight: var(--m3-type-emphasized-body-small-weight);
    color: var(--m3-color-on-surface);
    text-align: end;
    overflow-wrap: anywhere;
}

.m3-cdm__row-value--icon {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1-5);
}

.m3-cdm__row-icon {
    width: var(--m3-size-icon-2xs);
    height: var(--m3-size-icon-2xs);
    flex-shrink: 0;
    color: var(--m3-color-primary);
}

.m3-cdm__row-value--note {
    font-weight: var(--m3-type-body-small-weight);
    font-style: italic;
    color: var(--m3-color-on-surface-variant);
}

.m3-cdm__fallback {
    margin-inline-start: var(--m3-space-2);
    font-size: var(--m3-type-label-small-size);
    font-weight: var(--m3-type-body-small-weight);
    color: var(--m3-color-on-surface-variant);
}

.m3-cdm__status--cleared { color: var(--m3-color-income); }
.m3-cdm__status--draft { color: var(--m3-color-debt); }
.m3-cdm__status--cancelled { color: var(--m3-color-on-surface-variant); }

.m3-cdm__conf--high { color: var(--m3-color-primary); }
.m3-cdm__conf--medium { color: var(--m3-color-on-surface-variant); }
.m3-cdm__conf--low { color: var(--m3-color-error); }

/* Accordions */
.m3-cdm__accordions {
    display: flex;
    flex-direction: column;
    border-top: 1px solid var(--m3-color-outline-variant);
}

.m3-cdm__accordion + .m3-cdm__accordion {
    border-top: 1px solid color-mix(in srgb, var(--m3-color-outline-variant) 60%, transparent);
}

.m3-cdm__accordion-btn {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    width: 100%;
    min-height: var(--m3-size-touch-target);
    padding: var(--m3-space-2) 0;
    border: none;
    background: transparent;
    color: var(--m3-color-on-surface);
    cursor: pointer;
    text-align: start;
    transition: background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-cdm__accordion-btn:hover {
    background: color-mix(in srgb, var(--m3-color-on-surface) 4%, transparent);
}

.m3-cdm__accordion-btn:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: -2px;
}

.m3-cdm__accordion-icon {
    width: var(--m3-size-icon-2xs);
    height: var(--m3-size-icon-2xs);
    flex-shrink: 0;
    color: var(--m3-color-on-surface-variant);
}

.m3-cdm__accordion-label {
    flex: 1;
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    color: var(--m3-color-on-surface-variant);
}

.m3-cdm__accordion-chevron {
    width: var(--m3-size-icon-2xs);
    height: var(--m3-size-icon-2xs);
    flex-shrink: 0;
    color: var(--m3-color-on-surface-variant);
    transition: transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-cdm__accordion-chevron--open {
    transform: rotate(180deg);
}

.m3-cdm__accordion-panel {
    padding-bottom: var(--m3-space-3);
}

.m3-cdm__pre-wrap {
    padding: var(--m3-space-3);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container);
    font-size: var(--m3-type-body-small-size);
    line-height: 1.6;
    color: var(--m3-color-on-surface-variant);
    white-space: pre-wrap;
    overflow-wrap: break-word;
}

.m3-cdm__pre {
    margin: 0;
    padding: var(--m3-space-3);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container);
    font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
    font-size: var(--m3-type-label-small-size);
    color: var(--m3-color-on-surface-variant);
    max-height: 12rem;
    overflow: auto;
}

/* Footer actions */
.m3-cdm__actions {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    width: 100%;
}

.m3-cdm__icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    flex-shrink: 0;
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-cdm__icon-btn:hover {
    background: color-mix(in srgb, var(--m3-color-on-surface) 6%, transparent);
    color: var(--m3-color-on-surface);
}

.m3-cdm__icon-btn:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-cdm__icon-btn:disabled {
    opacity: 0.38;
    cursor: not-allowed;
}

.m3-cdm__icon-btn--danger:hover {
    border-color: var(--m3-color-error);
    color: var(--m3-color-error);
    background: color-mix(in srgb, var(--m3-color-error) 8%, transparent);
}

.m3-cdm__action-icon {
    width: var(--m3-size-icon-2xs);
    height: var(--m3-size-icon-2xs);
}

/* Dialog konfirmasi hapus */
.m3-cdm__dialog {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
    text-align: center;
    padding-top: var(--m3-space-2);
}

.m3-cdm__dialog-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
    margin-bottom: var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-expense-container);
    color: var(--m3-color-on-expense-container);
}

.m3-cdm__dialog-icon-glyph {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-cdm__dialog-title {
    margin: 0;
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    color: var(--m3-color-on-surface);
}

.m3-cdm__dialog-text {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-cdm__danger-filled {
    background: var(--m3-color-expense-container);
    color: var(--m3-color-expense);
    box-shadow: none;
}
</style>
