<script setup>
/**
 * InsightBanner.vue
 *
 * Banner insight keuangan yang dismissable.
 * Diekstrak dari Dashboard.vue — sebelumnya inline di template dengan
 * logic insight computed + sessionStorage dismiss hardcode di sana.
 *
 * Props:
 *   thisMonthIncome  — Total pemasukan bulan ini
 *   thisMonthExpense — Total pengeluaran bulan ini
 *
 * Emits:
 *   (tidak ada — dismiss state dikelola internal via sessionStorage)
 */

import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
    thisMonthIncome: {
        type: Number,
        default: 0,
    },
    thisMonthExpense: {
        type: Number,
        default: 0,
    },
})

// Insight logic — sebelumnya di computed Dashboard.vue
const insight = computed(() => {
    let type = 'info'
    let msg  = t('insight.neutral')
    let icon = 'info'

    if (props.thisMonthExpense > 0 && props.thisMonthIncome > 0) {
        const ratio = (props.thisMonthExpense / props.thisMonthIncome) * 100
        if (ratio >= 80) {
            type = 'danger'
            msg  = t('insight.bad')
            icon = 'danger'
        } else if (ratio <= 40) {
            type = 'success'
            msg  = t('insight.good')
            icon = 'success'
        } else {
            type = 'info'
            msg  = t('insight.warning')
            icon = 'warning'
        }
    } else if (props.thisMonthExpense > 0 && props.thisMonthIncome === 0) {
        type = 'warning'
        msg  = t('insight.bad')
        icon = 'danger'
    }

    return { type, msg, icon }
})

// Dismiss — persisten di sessionStorage (reset tiap buka tab baru)
const isVisible = ref(!sessionStorage.getItem('insightDismissed'))

const dismiss = () => {
    sessionStorage.setItem('insightDismissed', 'true')
    isVisible.value = false
}

// Color map berdasarkan insight type
const colorClasses = computed(() => ({
    danger:  'bg-red-950/40    border-red-900/50    text-red-400',
    success: 'bg-green-950/40  border-green-900/50  text-green-400',
    warning: 'bg-yellow-950/40 border-yellow-900/50 text-yellow-400',
    info:    'bg-gray-900/80   border-gray-900/50   text-gray-400',
}[insight.value.type]))
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
    >
        <div
            v-if="isVisible"
            :class="[
                'mb-6 p-3 rounded-xl border flex items-center justify-between gap-3',
                'animate-fade-in-up delay-100',
                'text-2xs uppercase font-bold tracking-widest',
                colorClasses,
            ]"
            role="status"
            :aria-label="insight.msg"
        >
            <div class="flex items-center gap-3">
                <svg v-if="insight.icon === 'danger'" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <svg v-else-if="insight.icon === 'success'" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else-if="insight.icon === 'warning'" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="leading-relaxed">{{ insight.msg }}</p>
            </div>

            <button
                type="button"
                @click="dismiss"
                class="text-current opacity-60 hover:opacity-100 transition-opacity p-1 shrink-0 focus:outline-none focus-visible:ring-1 focus-visible:ring-current rounded"
                aria-label="Tutup notifikasi"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </Transition>
</template>
