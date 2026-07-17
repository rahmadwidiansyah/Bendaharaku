<script setup>
/**
 * UpcomingDebts.vue
 *
 * Daftar notifikasi hutang/piutang yang mendekati atau melewati jatuh tempo.
 * Setiap item bisa di-dismiss secara individual (persisten di sessionStorage).
 *
 * Diekstrak dari Dashboard.vue — sebelumnya ~80 baris inline di template
 * plus logic dismissedDebts, onMounted, dismissDebt, activeUpcomingDebts.
 *
 * Props:
 *   upcomingDebts — Array dari server: [{ subject, type, days_until, remaining, next_due_date }]
 *   isVisible     — Apakah saldo ditampilkan atau disembunyikan (dari useBalanceVisibility)
 *
 * Tidak emit apapun — dismiss state dikelola internal via sessionStorage.
 */

import { ref, computed, onMounted } from 'vue'
import { formatNumber } from '@/utils/format.js'

const props = defineProps({
    upcomingDebts: {
        type: Array,
        default: () => [],
    },
    isVisible: {
        type: Boolean,
        default: true,
    },
})

// IDs item yang sudah di-dismiss (format: subject+type)
const dismissed = ref([])

onMounted(() => {
    try {
        const stored = sessionStorage.getItem('dismissed_debts')
        if (stored) dismissed.value = JSON.parse(stored)
    } catch {
        // sessionStorage tidak tersedia (private mode, dsb.) — abaikan
    }
})

const dismiss = (key) => {
    dismissed.value.push(key)
    try {
        sessionStorage.setItem('dismissed_debts', JSON.stringify(dismissed.value))
    } catch { /* silent */ }
}

// Saring item yang belum di-dismiss
const activeDebts = computed(() =>
    props.upcomingDebts.filter((d) => !dismissed.value.includes(d.subject + d.type))
)

// Helper untuk badge warna & label hari
const urgencyKey = (daysUntil) => {
    if (daysUntil < 0)  return 'overdue'
    if (daysUntil === 0) return 'today'
    if (daysUntil <= 3) return 'soon'
    return 'normal'
}

const badgeClasses = {
    overdue: 'bg-red-500/20    text-red-400',
    today:   'bg-red-500/20    text-red-400',
    soon:    'bg-red-500/20    text-red-400',
    normal:  'bg-yellow-500/20 text-yellow-400',
}

const cardClasses = {
    overdue: 'bg-gradient-to-br from-red-900/30    to-gray-800 border-red-500/50',
    today:   'bg-gradient-to-br from-red-900/30    to-gray-800 border-red-500/50',
    soon:    'bg-gradient-to-br from-red-900/30    to-gray-800 border-red-500/50',
    normal:  'bg-gradient-to-br from-yellow-900/30 to-gray-800 border-yellow-500/30',
}

const amountClasses = {
    overdue: 'text-red-400',
    today:   'text-red-400',
    soon:    'text-red-400',
    normal:  'text-yellow-400',
}

const badgeLabel = (daysUntil) => {
    if (daysUntil < 0)   return 'Terlewat'
    if (daysUntil === 0) return 'Hari Ini!'
    return `${daysUntil} Hari Lagi`
}
</script>

<template>
    <div
        v-if="activeDebts.length > 0"
        class="mb-8 animate-fade-in-up delay-300"
    >
        <!-- Section header -->
        <div class="flex items-center mb-3 px-1 gap-3">
            <h2 class="text-2xs font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2 shrink-0">
                <svg class="w-3 h-3 text-red-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Jatuh Tempo
            </h2>
            <div class="flex-1 h-px bg-gradient-to-r from-red-500/20 to-transparent" aria-hidden="true" />
        </div>

        <!-- Debt items -->
        <div class="flex flex-col gap-3" role="list" aria-label="Daftar jatuh tempo">
            <div
                v-for="debt in activeDebts"
                :key="debt.subject + debt.type"
                :class="['p-3.5 rounded-xl border relative overflow-hidden', cardClasses[urgencyKey(debt.days_until)]]"
                role="listitem"
            >
                <!-- Row atas: nama + badge + dismiss -->
                <div class="flex justify-between items-start mb-1">
                    <h3 class="text-2xs font-bold text-white tracking-widest truncate mr-2">
                        {{ debt.type }} — {{ debt.subject }}
                    </h3>
                    <div class="flex items-center gap-2 shrink-0">
                        <span
                            :class="['text-2xs px-2 py-0.5 rounded-full font-bold', badgeClasses[urgencyKey(debt.days_until)]]"
                            aria-label="`Jatuh tempo: ${badgeLabel(debt.days_until)}`"
                        >
                            {{ badgeLabel(debt.days_until) }}
                        </span>
                        <button
                            type="button"
                            @click.stop.prevent="dismiss(debt.subject + debt.type)"
                            class="text-gray-500 hover:text-white shrink-0 p-1 bg-white/5 rounded-full transition-colors focus:outline-none focus-visible:ring-1 focus-visible:ring-white"
                            aria-label="Sembunyikan notifikasi ini"
                        >
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Row bawah: nominal + tanggal -->
                <div class="flex justify-between items-center mt-2">
                    <p :class="['text-sm font-bold tracking-tight', amountClasses[urgencyKey(debt.days_until)]]" aria-live="polite">
                        <span class="text-2xs mr-1 opacity-70">Rp</span>
                        {{ isVisible ? formatNumber(debt.remaining) : '••••' }}
                    </p>
                    <p class="text-2xs text-gray-400 font-medium">
                        {{ debt.next_due_date }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
