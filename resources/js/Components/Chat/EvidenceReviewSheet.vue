<script setup>
/**
 * EvidenceReviewSheet.vue
 *
 * Bottom sheet untuk review dan edit draft transaksi dari evidence OCR.
 */
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'
import BaseModal from '@/Components/BaseModal.vue'

const { t } = useI18n()
const { showToast } = useToast()

const props = defineProps({
    modelValue:   { type: Boolean, required: true },
    evidenceUuid: { type: String,  default: null },
})

const emit = defineEmits(['update:modelValue', 'confirmed', 'cancelled', 'commitSuccess', 'commitError'])

const draft = ref(null)
const evidence = ref(null)
const isLoading = ref(false)
const isSaving = ref(false)
const isCommitting = ref(false)
const error = ref(null)
const isDirty = ref(false)

const editForm = ref({
    transaction_type: '',
    wallet_id: null,
    category_id: null,
    amount: 0,
    description: '',
    transaction_date: '',
    destination_name: '',
    destination_account: '',
})

const wallets = ref([])

const confidenceColor = computed(() => {
    if (!draft.value) return 'gray'
    const c = draft.value.confidence
    if (c >= 0.8) return 'green'
    if (c >= 0.5) return 'amber'
    return 'red'
})

const confidenceLabel = computed(() => {
    if (!draft.value) return ''
    const c = draft.value.confidence
    if (c >= 0.8) return t('chatTransaction.confidence.high')
    if (c >= 0.5) return t('chatTransaction.confidence.medium')
    return t('chatTransaction.confidence.low')
})

function close() {
    emit('update:modelValue', false)
}

function getBadge(conf) {
    if (conf >= 0.8) return { bg: 'bg-income-bg', text: 'text-income-text' }
    if (conf >= 0.5) return { bg: 'bg-debt-bg', text: 'text-debt-text' }
    return { bg: 'bg-expense-bg', text: 'text-expense-text' }
}

function formatAmount(val) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0)
}

async function fetchDraft() {
    if (!props.evidenceUuid) return
    isLoading.value = true
    error.value = null
    try {
        const res = await axios.get(route('chat.evidence.draft.show', { uuid: props.evidenceUuid }))
        if (res.data.success) {
            draft.value = res.data.draft
            evidence.value = res.data.evidence
            editForm.value = {
                transaction_type: res.data.draft.transaction_type,
                wallet_id: res.data.draft.wallet_id,
                category_id: res.data.draft.category_id,
                amount: res.data.draft.amount,
                description: res.data.draft.description || '',
                transaction_date: res.data.draft.transaction_date || '',
                destination_name: res.data.draft.destination_name || '',
                destination_account: res.data.draft.destination_account || '',
            }
            isDirty.value = false
        }
    } catch (err) {
        error.value = err.response?.data?.message || t('chat.error.loadDraftFailed')
    } finally {
        isLoading.value = false
    }
}

async function saveDraft() {
    if (!props.evidenceUuid || !isDirty.value) return
    isSaving.value = true
    error.value = null
    try {
        const res = await axios.patch(route('chat.evidence.draft.update', { uuid: props.evidenceUuid }), editForm.value)
        if (res.data.success) {
            draft.value = res.data.draft
            editForm.value = {
                transaction_type: res.data.draft.transaction_type,
                wallet_id: res.data.draft.wallet_id,
                category_id: res.data.draft.category_id,
                amount: res.data.draft.amount,
                description: res.data.draft.description || '',
                transaction_date: res.data.draft.transaction_date || '',
                destination_name: res.data.draft.destination_name || '',
                destination_account: res.data.draft.destination_account || '',
            }
            isDirty.value = false
            showToast(t('toast.saved'), 'success')
        }
    } catch (err) {
        error.value = err.response?.data?.message || t('chat.error.saveFailed')
        showToast(err.response?.data?.message || t('toast.error'), 'error')
    } finally {
        isSaving.value = false
    }
}

async function commitTransaction() {
    if (!props.evidenceUuid) return

    // Save draft first if dirty
    if (isDirty.value) {
        await saveDraft()
    }

    isCommitting.value = true
    error.value = null

    try {
        const res = await axios.post(route('chat.evidence.commit', { uuid: props.evidenceUuid }), editForm.value)
        if (res.data.success) {
            emit('commitSuccess', {
                uuid: props.evidenceUuid,
                transaction_id: res.data.transaction_id,
                transaction: res.data.transaction,
                warnings: res.data.warnings,
            })
            showToast(t('toast.saved'), 'success')
            close()
        } else {
            error.value = res.data.message || t('chat.error.commitFailed')
            emit('commitError', { uuid: props.evidenceUuid, error: error.value })
            showToast(error.value, 'error')
        }
    } catch (err) {
        error.value = err.response?.data?.message || t('chat.error.commitFailed')
        emit('commitError', { uuid: props.evidenceUuid, error: error.value })
        showToast(error.value, 'error')
    } finally {
        isCommitting.value = false
    }
}

function cancelDraft() {
    emit('cancelled', { uuid: props.evidenceUuid })
    close()
}

function onFieldChange() {
    isDirty.value = true
}

async function fetchWallets() {
    try {
        const res = await axios.get(route('chat.wallets'))
        wallets.value = res.data.wallets || []
    } catch (_) { /* ignore */ }
}

watch(() => props.modelValue, (v) => {
    if (v && props.evidenceUuid) {
        fetchDraft()
        fetchWallets()
    }
})
</script>

<template>
    <BaseModal
        :show="modelValue"
        max-width="2xl"
        align="bottom-sheet"
        mobile-only
        @close="close"
    >
        <!-- Header -->
        <template #header>
            <div>
                <h2 class="text-sm font-bold text-white">{{ t('chat.evidenceReview.title') }}</h2>
                <p class="text-2xs text-gray-500 mt-0.5">{{ t('chat.evidenceReview.subtitle') }}</p>
            </div>
        </template>

        <!-- Content (scrollable) -->
        <div
            class="overflow-y-auto w-full max-h-[calc(100dvh-240px)] border-t border-white/10 pt-3 space-y-4"
            style="padding-bottom: max(1rem, env(safe-area-inset-bottom, 1rem));"
        >
            <!-- Loading -->
            <div v-if="isLoading" class="flex items-center justify-center py-8">
                <svg class="animate-spin w-6 h-6 text-purple-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="text-center py-8">
                <p class="text-expense-text text-sm">{{ error }}</p>
                <button @click="fetchDraft" class="mt-3 text-purple-400 text-sm hover:underline">{{ t('chat.evidenceReview.retry') }}</button>
            </div>

            <!-- Draft form -->
            <template v-else-if="draft">
                <!-- Confidence -->
                <div class="flex items-center gap-2">
                    <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold', getBadge(draft.confidence).bg, getBadge(draft.confidence).text]">
                        <span class="w-1.5 h-1.5 rounded-full" :class="getBadge(draft.confidence).text.replace('text-', 'bg-')" />
                        {{ confidenceLabel }} {{ Math.round(draft.confidence * 100) }}%
                    </span>
                    <span v-if="evidence?.document_type" class="text-xs text-gray-500">{{ evidence.document_type }}</span>
                </div>

                <!-- Warnings -->
                <div v-if="draft.warnings?.length" class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-3">
                    <p v-for="(w, i) in draft.warnings" :key="i" class="text-xs text-debt-text">{{ w }}</p>
                </div>

                <!-- Amount -->
                <div>
                    <label class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-semibold text-gray-400">{{ t('chat.evidenceReview.amount') }}</span>
                        <span :class="['text-2xs px-1.5 py-0.5 rounded', getBadge(draft.amount_confidence).bg, getBadge(draft.amount_confidence).text]">
                            {{ Math.round(draft.amount_confidence * 100) }}%
                        </span>
                    </label>
                    <input
                        v-model.number="editForm.amount"
                        type="number"
                        @input="onFieldChange"
                        class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/15"
                    />
                </div>

                <!-- Transaction Type -->
                <div>
                    <label class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-semibold text-gray-400">{{ t('chat.evidenceReview.transactionType') }}</span>
                    </label>
                    <select
                        v-model="editForm.transaction_type"
                        @change="onFieldChange"
                        class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/15"
                    >
                        <option value="EXPENSE">{{ t('chat.evidenceReview.typeExpense') }}</option>
                        <option value="INCOME">{{ t('chat.evidenceReview.typeIncome') }}</option>
                        <option value="TRANSFER">{{ t('chat.evidenceReview.typeTransfer') }}</option>
                    </select>
                </div>

                <!-- Wallet -->
                <div>
                    <label class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-semibold text-gray-400">{{ t('chat.evidenceReview.sourceWallet') }}</span>
                        <span :class="['text-2xs px-1.5 py-0.5 rounded', getBadge(draft.wallet_confidence).bg, getBadge(draft.wallet_confidence).text]">
                            {{ Math.round(draft.wallet_confidence * 100) }}%
                        </span>
                    </label>
                    <select
                        v-model="editForm.wallet_id"
                        @change="onFieldChange"
                        class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/15"
                    >
                        <option :value="null">{{ t('chat.evidenceReview.selectWallet') }}</option>
                        <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                </div>

                <!-- Date -->
                <div>
                    <label class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-semibold text-gray-400">{{ t('chat.evidenceReview.date') }}</span>
                        <span :class="['text-2xs px-1.5 py-0.5 rounded', getBadge(draft.date_confidence).bg, getBadge(draft.date_confidence).text]">
                            {{ Math.round(draft.date_confidence * 100) }}%
                        </span>
                    </label>
                    <input
                        v-model="editForm.transaction_date"
                        type="text"
                        @input="onFieldChange"
                        placeholder="YYYY-MM-DD HH:mm"
                        class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-gray-600 outline-none focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/15"
                    />
                </div>

                <!-- Description -->
                <div>
                    <label class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-semibold text-gray-400">{{ t('chat.evidenceReview.description') }}</span>
                    </label>
                    <input
                        v-model="editForm.description"
                        type="text"
                        @input="onFieldChange"
                        class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-gray-600 outline-none focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/15"
                    />
                </div>

                <!-- Destination (for transfers) -->
                <template v-if="editForm.transaction_type === 'TRANSFER' || editForm.transaction_type === 'INTERNAL_TRANSFER'">
                    <div>
                        <label class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-gray-400">{{ t('chat.evidenceReview.destinationName') }}</span>
                            <span :class="['text-2xs px-1.5 py-0.5 rounded', getBadge(draft.destination_name_confidence).bg, getBadge(draft.destination_name_confidence).text]">
                                {{ Math.round(draft.destination_name_confidence * 100) }}%
                            </span>
                        </label>
                        <input
                            v-model="editForm.destination_name"
                            type="text"
                            @input="onFieldChange"
                            class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-gray-600 outline-none focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/15"
                        />
                    </div>
                    <div>
                        <label class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-gray-400">{{ t('chat.evidenceReview.destinationAccount') }}</span>
                            <span :class="['text-2xs px-1.5 py-0.5 rounded', getBadge(draft.destination_account_confidence).bg, getBadge(draft.destination_account_confidence).text]">
                                {{ Math.round(draft.destination_account_confidence * 100) }}%
                            </span>
                        </label>
                        <input
                            v-model="editForm.destination_account"
                            type="text"
                            @input="onFieldChange"
                            class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-gray-600 outline-none focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/15"
                        />
                    </div>
                </template>

                <!-- Reference Number (read-only) -->
                <div v-if="draft.reference_number">
                    <label class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-semibold text-gray-400">{{ t('chat.evidenceReview.referenceNumber') }}</span>
                        <span :class="['text-2xs px-1.5 py-0.5 rounded', getBadge(draft.reference_confidence).bg, getBadge(draft.reference_confidence).text]">
                            {{ Math.round(draft.reference_confidence * 100) }}%
                        </span>
                    </label>
                    <input
                        :value="draft.reference_number"
                        type="text"
                        readonly
                        class="w-full bg-gray-800/50 border border-white/5 rounded-xl px-3 py-2.5 text-sm text-gray-500 cursor-not-allowed"
                    />
                </div>
            </template>
        </div>

        <!-- Footer -->
        <template #footer>
            <template v-if="draft">
                <button
                    @click="cancelDraft"
                    :disabled="isCommitting"
                    class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-gray-400 bg-gray-800 border border-white/10 hover:bg-gray-700 transition-colors disabled:opacity-50"
                >
                    {{ t('chat.evidenceReview.cancel') }}
                </button>
                <button
                    v-if="isDirty"
                    @click="saveDraft"
                    :disabled="isSaving"
                    class="flex-1 py-2.5 rounded-xl text-sm font-bold text-purple-400 bg-purple-500/10 border border-purple-500/30 hover:bg-purple-500/20 transition-colors disabled:opacity-50"
                >
                    {{ isSaving ? t('chat.evidenceReview.saving') : t('chat.evidenceReview.save') }}
                </button>
                <button
                    @click="commitTransaction"
                    :disabled="isCommitting"
                    class="flex-1 py-2.5 rounded-xl text-sm font-bold text-white bg-purple-600 hover:bg-purple-500 shadow-lg shadow-purple-600/25 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                    <svg v-if="isCommitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ isCommitting ? t('chat.evidenceReview.committing') : t('chat.evidenceReview.commit') }}
                </button>
            </template>
        </template>
    </BaseModal>
</template>
