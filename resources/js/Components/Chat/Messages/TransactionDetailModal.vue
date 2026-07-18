<script setup>
/**
 * TransactionDetailModal.vue
 *
 * Bottom sheet / modal detail transaksi yang dibuat via AI.
 * Menampilkan: ID, sumber, intent, model AI, confidence, latency, raw prompt, JSON metadata.
 *
 * Pakai useClipboard untuk copy ID.
 */

import { ref, computed } from 'vue'
import { useClipboard }  from '@/Composables/useClipboard.js'

const props = defineProps({
    modelValue:  { type: Boolean, default: false },
    transaction: { type: Object,  default: null },
    metadata:    { type: Object,  default: () => ({}) },
})

const emit = defineEmits(['update:modelValue'])

function close() { emit('update:modelValue', false) }

// Accordion
const openAccordion = ref(null)
function toggleAccordion(name) {
    openAccordion.value = openAccordion.value === name ? null : name
}

// Copy ID via composable
const { copied: copySuccess, copy: copyToClipboard } = useClipboard()
async function copyId() {
    const id = props.transaction?.reference_number ?? props.transaction?.id ?? ''
    await copyToClipboard(String(id))
}

// Sumber berdasarkan prefix reference_number
const sourceInfo = computed(() => {
    const ref = props.transaction?.reference_number ?? ''
    const prefix = ref.split('-')[0] ?? ''
    const map = {
        'WEB': { label: 'Web Chat',     icon: '💬', color: 'text-purple-400' },
        'TEL': { label: 'Telegram Bot', icon: '📡', color: 'text-blue-400' },
        'WA':  { label: 'WhatsApp',     icon: '📱', color: 'text-emerald-400' },
        'DSC': { label: 'Discord',      icon: '🎮', color: 'text-indigo-400' },
        'API': { label: 'REST API',     icon: '⚡', color: 'text-yellow-400' },
        'IMP': { label: 'Import',       icon: '📂', color: 'text-orange-400' },
        'MNL': { label: 'Manual Entry', icon: '✏️', color: 'text-gray-400' },
    }
    return map[prefix] ?? { label: 'Web Dashboard', icon: '🌐', color: 'text-gray-400' }
})

function formatDateTime(dt) {
    if (!dt) return '-'
    try {
        return new Date(dt).toLocaleString('id-ID', {
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
    const level = pct >= 90 ? 'Tinggi' : pct >= 70 ? 'Sedang' : 'Rendah'
    const color = pct >= 90 ? 'text-emerald-400' : pct >= 70 ? 'text-yellow-400' : 'text-red-400'
    return { pct, level, color }
})

const latencyLabel = computed(() => {
    const ms = props.metadata?.latency_ms
    if (!ms) return null
    return ms >= 1000 ? (ms / 1000).toFixed(2) + ' detik' : ms + ' ms'
})

const modelLabel = computed(() => {
    const m = props.metadata?.model ?? ''
    return m || props.metadata?.provider || null
})

const intentLabel = computed(() => {
    const intent = props.metadata?.intent ?? ''
    const map = {
        'single_transaction': 'Transaksi Tunggal',
        'multi_transaction':  'Multi Transaksi',
        'command':            'Perintah',
        'error':              'Error',
        'draft':              'Draft',
    }
    return map[intent] ?? intent || null
})

const parseStatus = computed(() =>
    props.transaction?.is_cleared
        ? { label: 'AI Parsed', color: 'text-emerald-400' }
        : { label: 'Draft',     color: 'text-amber-400' }
)

const jsonMeta = computed(() => JSON.stringify(props.metadata ?? {}, null, 2))
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="modelValue && transaction"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center"
                @click.self="close"
            >
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="close"></div>

                <!-- Sheet / Modal -->
                <div class="relative z-10 w-full sm:max-w-lg bg-gray-900 border border-white/10 rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden max-h-[92dvh] flex flex-col">

                    <!-- Handle (mobile) -->
                    <div class="flex justify-center pt-3 pb-1 sm:hidden">
                        <div class="w-10 h-1 rounded-full bg-white/20"></div>
                    </div>

                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-3 border-b border-white/8">
                        <div>
                            <h2 class="text-sm font-bold text-white">Detail Transaksi</h2>
                            <p class="text-2xs text-gray-500 mt-0.5">{{ parseStatus.label }}</p>
                        </div>
                        <button @click="close" class="w-8 h-8 flex items-center justify-center rounded-xl text-gray-400 hover:text-white hover:bg-white/8 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Scrollable content -->
                    <div class="overflow-y-auto flex-1">

                        <!-- ID Transaksi -->
                        <div class="px-5 py-3 border-b border-white/5">
                            <p class="text-2xs text-gray-500 mb-1">ID Transaksi</p>
                            <div class="flex items-center gap-2">
                                <code class="text-xs font-mono text-white bg-gray-800 px-2.5 py-1 rounded-lg border border-white/8 flex-1 truncate">
                                    {{ transaction.reference_number ?? transaction.id ?? '-' }}
                                </code>
                                <button
                                    @click="copyId"
                                    class="shrink-0 flex items-center gap-1 px-2.5 py-1 rounded-lg text-2xs border transition-all"
                                    :class="copySuccess
                                        ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-400'
                                        : 'bg-gray-800 border-white/10 text-gray-400 hover:text-white hover:border-white/20'"
                                >
                                    <svg v-if="!copySuccess" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <svg v-else class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ copySuccess ? 'Tersalin' : 'Salin' }}
                                </button>
                            </div>
                        </div>

                        <!-- Info rows -->
                        <div class="divide-y divide-white/5">

                            <div class="flex items-center gap-3 px-5 py-2.5">
                                <span class="text-base w-5 text-center">{{ sourceInfo.icon }}</span>
                                <div class="flex-1">
                                    <p class="text-2xs text-gray-500">Dicatat dari</p>
                                    <p :class="['text-sm font-medium', sourceInfo.color]">{{ sourceInfo.label }}</p>
                                </div>
                            </div>

                            <div v-if="intentLabel" class="flex items-center gap-3 px-5 py-2.5">
                                <span class="text-base w-5 text-center">🎯</span>
                                <div class="flex-1">
                                    <p class="text-2xs text-gray-500">Intent</p>
                                    <p class="text-sm font-medium text-white">{{ intentLabel }}</p>
                                </div>
                            </div>

                            <div v-if="modelLabel" class="flex items-center gap-3 px-5 py-2.5">
                                <span class="text-base w-5 text-center">🤖</span>
                                <div class="flex-1">
                                    <p class="text-2xs text-gray-500">Diproses oleh</p>
                                    <p class="text-sm font-medium text-white">{{ modelLabel }}</p>
                                </div>
                            </div>

                            <div v-if="latencyLabel" class="flex items-center gap-3 px-5 py-2.5">
                                <span class="text-base w-5 text-center">⏱</span>
                                <div class="flex-1">
                                    <p class="text-2xs text-gray-500">Durasi proses</p>
                                    <p class="text-sm font-medium text-white">{{ latencyLabel }}</p>
                                </div>
                            </div>

                            <div v-if="confidenceLabel" class="flex items-center gap-3 px-5 py-2.5">
                                <span class="text-base w-5 text-center">📊</span>
                                <div class="flex-1">
                                    <p class="text-2xs text-gray-500">Confidence AI</p>
                                    <p :class="['text-sm font-medium', confidenceLabel.color]">
                                        {{ confidenceLabel.pct }}% &mdash; {{ confidenceLabel.level }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 px-5 py-2.5">
                                <span class="text-base w-5 text-center">✅</span>
                                <div class="flex-1">
                                    <p class="text-2xs text-gray-500">Status</p>
                                    <p :class="['text-sm font-medium', parseStatus.color]">{{ parseStatus.label }}</p>
                                </div>
                            </div>

                            <div v-if="transaction.date || transaction.created_at" class="flex items-center gap-3 px-5 py-2.5">
                                <span class="text-base w-5 text-center">📅</span>
                                <div class="flex-1">
                                    <p class="text-2xs text-gray-500">Waktu transaksi</p>
                                    <p class="text-sm font-medium text-white">
                                        {{ formatDateTime(transaction.created_at ?? transaction.date) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Accordions -->
                        <div class="border-t border-white/8 mt-1">

                            <!-- Raw Prompt -->
                            <div v-if="metadata?.raw_prompt" class="border-b border-white/5">
                                <button
                                    @click="toggleAccordion('prompt')"
                                    class="w-full flex items-center justify-between px-5 py-3 text-left hover:bg-white/3 transition-colors"
                                >
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">💬</span>
                                        <span class="text-sm text-gray-400 font-medium">Prompt Asli</span>
                                    </div>
                                    <svg :class="['w-4 h-4 text-gray-600 transition-transform', openAccordion === 'prompt' ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div v-if="openAccordion === 'prompt'" class="px-5 pb-4">
                                    <div class="text-xs text-gray-300 bg-gray-950 rounded-xl p-3 border border-white/8 whitespace-pre-wrap break-words leading-relaxed">{{ metadata.raw_prompt }}</div>
                                </div>
                            </div>

                            <!-- JSON Metadata -->
                            <div class="border-b border-white/5">
                                <button
                                    @click="toggleAccordion('json')"
                                    class="w-full flex items-center justify-between px-5 py-3 text-left hover:bg-white/3 transition-colors"
                                >
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">🔧</span>
                                        <span class="text-sm text-gray-400 font-medium">JSON Metadata</span>
                                    </div>
                                    <svg :class="['w-4 h-4 text-gray-600 transition-transform', openAccordion === 'json' ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div v-if="openAccordion === 'json'" class="px-5 pb-4">
                                    <pre class="text-2xs font-mono text-gray-400 bg-gray-950 rounded-xl p-3 overflow-x-auto border border-white/8 max-h-48 overflow-y-auto">{{ jsonMeta }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-5 py-3 border-t border-white/8 bg-gray-950/50">
                        <button
                            @click="close"
                            class="w-full py-2.5 rounded-xl bg-gray-800 border border-white/10 text-sm text-gray-300 hover:text-white hover:bg-gray-700 transition-all font-medium"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
