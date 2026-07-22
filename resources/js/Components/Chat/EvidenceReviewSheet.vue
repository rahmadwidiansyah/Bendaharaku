<script setup>
/**
 * EvidenceReviewSheet.vue
 *
 * Bottom sheet untuk review dan edit draft transaksi dari evidence OCR.
 */
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()

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
    if (c >= 0.8) return 'Tinggi'
    if (c >= 0.5) return 'Sedang'
    return 'Rendah'
})

function close() {
    emit('update:modelValue', false)
}

function getBadge(conf) {
    if (conf >= 0.8) return { bg: 'bg-green-500/20', text: 'text-green-400' }
    if (conf >= 0.5) return { bg: 'bg-amber-500/20', text: 'text-amber-400' }
    return { bg: 'bg-red-500/20', text: 'text-red-400' }
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
        error.value = err.response?.data?.message || 'Gagal memuat data draft'
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
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Gagal menyimpan'
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
            close()
        } else {
            error.value = res.data.message || 'Gagal membuat transaksi'
            emit('commitError', { uuid: props.evidenceUuid, error: error.value })
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Gagal membuat transaksi'
        emit('commitError', { uuid: props.evidenceUuid, error: error.value })
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
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="modelValue" class="fixed inset-0 z-50 flex items-end">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="close" />

                <Transition
                    enter-active-class="transition-transform duration-300 ease-out"
                    enter-from-class="translate-y-full"
                    enter-to-class="translate-y-0"
                    leave-active-class="transition-transform duration-200 ease-in"
                    leave-from-class="translate-y-0"
                    leave-to-class="translate-y-full"
                    appear
                >
                    <div
                        v-if="modelValue"
                        role="dialog"
                        aria-modal="true"
                        class="relative z-10 w-full max-w-lg mx-auto bg-gray-900 border-t border-x border-white/10 rounded-t-3xl shadow-2xl max-h-[85vh] flex flex-col"
                    >
                        <!-- Handle -->
                        <div class="flex justify-center pt-3 pb-1 shrink-0">
                            <div class="w-10 h-1 rounded-full bg-white/20" />
                        </div>

                        <!-- Header -->
                        <div class="flex items-center justify-between px-5 py-3 border-b border-white/5 shrink-0">
                            <div>
                                <h2 class="text-sm font-bold text-white">Review Transaksi</h2>
                                <p class="text-2xs text-gray-500 mt-0.5">Periksa dan edit data sebelum disimpan</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="isDirty"
                                    @click="saveDraft"
                                    :disabled="isSaving"
                                    class="text-2xs font-semibold text-purple-400 hover:text-purple-300 disabled:opacity-50"
                                >
                                    {{ isSaving ? 'Menyimpan...' : 'Simpan' }}
                                </button>
                                <button
                                    @click="close"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-500 hover:text-white hover:bg-white/8 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4"
                            style="padding-bottom: max(1rem, env(safe-area-inset-bottom, 1rem));">

                            <!-- Loading -->
                            <div v-if="isLoading" class="flex items-center justify-center py-8">
                                <svg class="animate-spin w-6 h-6 text-purple-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </div>

                            <!-- Error -->
                            <div v-else-if="error" class="text-center py-8">
                                <p class="text-red-400 text-sm">{{ error }}</p>
                                <button @click="fetchDraft" class="mt-3 text-purple-400 text-sm hover:underline">Coba Lagi</button>
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
                                    <p v-for="(w, i) in draft.warnings" :key="i" class="text-xs text-amber-400">{{ w }}</p>
                                </div>

                                <!-- Amount -->
                                <div>
                                    <label class="flex items-center justify-between mb-1.5">
                                        <span class="text-xs font-semibold text-gray-400">Nominal</span>
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
                                        <span class="text-xs font-semibold text-gray-400">Tipe Transaksi</span>
                                    </label>
                                    <select
                                        v-model="editForm.transaction_type"
                                        @change="onFieldChange"
                                        class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/15"
                                    >
                                        <option value="EXPENSE">Pengeluaran</option>
                                        <option value="INCOME">Pemasukan</option>
                                        <option value="TRANSFER">Transfer</option>
                                    </select>
                                </div>

                                <!-- Wallet -->
                                <div>
                                    <label class="flex items-center justify-between mb-1.5">
                                        <span class="text-xs font-semibold text-gray-400">Dompet Sumber</span>
                                        <span :class="['text-2xs px-1.5 py-0.5 rounded', getBadge(draft.wallet_confidence).bg, getBadge(draft.wallet_confidence).text]">
                                            {{ Math.round(draft.wallet_confidence * 100) }}%
                                        </span>
                                    </label>
                                    <select
                                        v-model="editForm.wallet_id"
                                        @change="onFieldChange"
                                        class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/15"
                                    >
                                        <option :value="null">Pilih dompet...</option>
                                        <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                                    </select>
                                </div>

                                <!-- Date -->
                                <div>
                                    <label class="flex items-center justify-between mb-1.5">
                                        <span class="text-xs font-semibold text-gray-400">Tanggal</span>
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
                                        <span class="text-xs font-semibold text-gray-400">Deskripsi</span>
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
                                            <span class="text-xs font-semibold text-gray-400">Nama Tujuan</span>
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
                                            <span class="text-xs font-semibold text-gray-400">Rekening Tujuan</span>
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
                                        <span class="text-xs font-semibold text-gray-400">No. Referensi</span>
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
                        <div v-if="draft" class="shrink-0 px-5 py-3 border-t border-white/5 flex gap-3"
                            style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom, 0.75rem));">
                            <button
                                @click="cancelDraft"
                                :disabled="isCommitting"
                                class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-gray-400 bg-gray-800 border border-white/8 hover:bg-gray-700 transition-colors disabled:opacity-50"
                            >
                                Batal
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
                                {{ isCommitting ? 'Menyimpan...' : 'Simpan Transaksi' }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
