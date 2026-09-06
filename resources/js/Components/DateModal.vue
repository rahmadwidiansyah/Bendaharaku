<script setup>
/**
 * DateModal.vue
 *
 * Modal pemilih rentang tanggal dengan quick-date buttons.
 * Direfactor untuk menggunakan BaseModal — menghapus duplikasi struktur modal
 * (Teleport, overlay, close button, animasi) yang sebelumnya hardcode di sini.
 *
 * Props:
 *   startDate — Tanggal mulai saat ini (format YYYY-MM-DD)
 *   endDate   — Tanggal akhir saat ini (format YYYY-MM-DD)
 *   action    — Route name/URL tujuan submit filter
 */

import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import BaseModal from '@/Components/BaseModal.vue'
import { formatLocalYMD } from '@/utils/format.js'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
    startDate: String,
    endDate: String,
    action: String,
})

const showModal = ref(false)

const form = useForm({
    start_date: props.startDate,
    end_date: props.endDate,
})

const toggleModal = () => {
    showModal.value = !showModal.value
}

const setQuickDate = (type) => {
    const today = new Date()
    let start, end

    if (type === 'thisYear') {
        start = new Date(today.getFullYear(), 0, 1)
        end   = new Date(today.getFullYear(), 11, 31)
    } else if (type === 'thisMonth') {
        start = new Date(today.getFullYear(), today.getMonth(), 1)
        end   = new Date(today.getFullYear(), today.getMonth() + 1, 0)
    } else if (type === 'lastMonth') {
        start = new Date(today.getFullYear(), today.getMonth() - 1, 1)
        end   = new Date(today.getFullYear(), today.getMonth(), 0)
    }

    form.start_date = formatLocalYMD(start)
    form.end_date   = formatLocalYMD(end)
}

const submit = () => {
    if (isInvalidRange.value) return

    form.get(props.action, {
        preserveState: true,
        onSuccess: () => {
            showModal.value = false
        },
    })
}

const isInvalidRange = computed(() => form.start_date > form.end_date)

const activeQuickDate = computed(() => {
    const today = new Date()
    const checks = {
        thisYear: [
            formatLocalYMD(new Date(today.getFullYear(), 0, 1)),
            formatLocalYMD(new Date(today.getFullYear(), 11, 31)),
        ],
        thisMonth: [
            formatLocalYMD(new Date(today.getFullYear(), today.getMonth(), 1)),
            formatLocalYMD(new Date(today.getFullYear(), today.getMonth() + 1, 0)),
        ],
        lastMonth: [
            formatLocalYMD(new Date(today.getFullYear(), today.getMonth() - 1, 1)),
            formatLocalYMD(new Date(today.getFullYear(), today.getMonth(), 0)),
        ],
    }
    for (const [key, [s, e]] of Object.entries(checks)) {
        if (form.start_date === s && form.end_date === e) return key
    }
    return null
})

// Indikator: apakah filter sedang tidak default (bulan ini)?
const isFiltered = computed(() => {
    const today = new Date()
    const defaultStart = formatLocalYMD(new Date(today.getFullYear(), today.getMonth(), 1))
    return props.startDate !== defaultStart
})
</script>

<template>
    <!-- Trigger button — tampil di halaman sebagai pemicu -->
    <button
        type="button"
        class="text-gray-400 hover:text-[var(--color-text-primary)] bg-linear-to-br from-gray-900 to-gray-800 border border-[var(--color-border-default)] rounded-lg sm:rounded-xl px-3 py-1.5 sm:px-4 sm:py-2.5 active:scale-90 transition-all shrink-0 flex items-center justify-center"
        aria-label="Pilih rentang waktu"
        @click="toggleModal"
    >
        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <!-- Dot indikator filter aktif -->
        <span
            v-if="isFiltered"
            class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-purple-500 rounded-full ring-2 ring-[#121212]"
            aria-label="Filter aktif"
        />
    </button>

    <!-- Modal via BaseModal -->
    <BaseModal
        :show="showModal"
        :title="t('common.dateRange')"
        max-width="adaptive"
        @close="showModal = false"
    >
        <form @submit.prevent="submit" class="space-y-5">
            <!-- Date inputs -->
            <div class="grid grid-cols-2 gap-3 text-left">
                <div class="space-y-1">
                    <label
                        for="date-start"
                        class="text-2xs font-bold text-[var(--color-brand)] uppercase tracking-widest pl-1"
                    >
                        {{ t('common.from') }}
                    </label>
                    <input
                        id="date-start"
                        type="date"
                        v-model="form.start_date"
                        class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-[var(--color-border-default)] text-[var(--color-text-primary)] rounded-xl p-3 text-2xs focus:outline-none focus:ring-1 focus:border-purple-500 focus:ring-purple-500 transition-all [color-scheme:dark]"
                    />
                </div>
                <div class="space-y-1">
                    <label
                        for="date-end"
                        class="text-2xs font-bold text-[var(--color-brand)] uppercase tracking-widest pl-1"
                    >
                        {{ t('common.to') }}
                    </label>
                    <input
                        id="date-end"
                        type="date"
                        v-model="form.end_date"
                        :min="form.start_date"
                        :class="[
                            'w-full bg-gradient-to-br from-gray-900 to-gray-800 border text-[var(--color-text-primary)] rounded-xl p-3 text-2xs focus:outline-none focus:ring-1 transition-all [color-scheme:dark]',
                            isInvalidRange
                                ? 'border-red-500/60 focus:border-red-500 focus:ring-red-500'
                                : 'border-[var(--color-border-default)] focus:border-purple-500 focus:ring-purple-500',
                        ]"
                    />
                </div>
            </div>

            <!-- Validation error -->
            <p
                v-if="isInvalidRange"
                role="alert"
                class="text-2xs text-[var(--color-expense-text)] font-bold -mt-2"
            >
                ⚠ {{ t('common.dateInvalidRange') }}
            </p>

            <!-- Quick-date shortcuts -->
            <div class="grid grid-cols-3 gap-2 pt-1">
                <button
                    type="button"
                    :class="[
                        'text-2xs font-bold py-3 rounded-xl uppercase transition-all duration-150 active:scale-95',
                        activeQuickDate === 'thisYear'
                            ? 'bg-purple-600 text-[var(--color-text-primary)] shadow-lg shadow-purple-500/20'
                            : 'bg-gradient-to-br from-gray-900 to-gray-800 text-gray-400 border border-[var(--color-border-default)] hover:text-[var(--color-text-primary)] hover:border-white/20',
                    ]"
                    @click="setQuickDate('thisYear')"
                >
                    {{ t('common.thisYear') }}
                </button>
                <button
                    type="button"
                    :class="[
                        'text-2xs font-bold py-3 rounded-xl uppercase transition-all duration-150 active:scale-95',
                        activeQuickDate === 'thisMonth'
                            ? 'bg-purple-600 text-[var(--color-text-primary)] shadow-lg shadow-purple-500/20'
                            : 'bg-gradient-to-br from-gray-900 to-gray-800 text-gray-400 border border-[var(--color-border-default)] hover:text-[var(--color-text-primary)] hover:border-white/20',
                    ]"
                    @click="setQuickDate('thisMonth')"
                >
                    {{ t('common.thisMonth') }}
                </button>
                <button
                    type="button"
                    :class="[
                        'text-2xs font-bold py-3 rounded-xl uppercase transition-all duration-150 active:scale-95',
                        activeQuickDate === 'lastMonth'
                            ? 'bg-purple-600 text-[var(--color-text-primary)] shadow-lg shadow-purple-500/20'
                            : 'bg-gradient-to-br from-gray-900 to-gray-800 text-gray-400 border border-[var(--color-border-default)] hover:text-[var(--color-text-primary)] hover:border-white/20',
                    ]"
                    @click="setQuickDate('lastMonth')"
                >
                    {{ t('common.lastMonth') }}
                </button>
            </div>

            <!-- Submit -->
            <button
                type="submit"
                :disabled="form.processing || isInvalidRange"
                class="w-full bg-gradient-to-br from-brand-deep to-brand-soft text-[var(--color-text-primary)] font-black text-2xs uppercase py-3.5 rounded-xl active:scale-95 transition-all shadow-lg shadow-purple-500/20 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100 flex items-center justify-center gap-2"
            >
                <svg
                    v-if="form.processing"
                    class="animate-spin w-4 h-4 shrink-0"
                    fill="none"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                {{ form.processing ? t('common.applying') : t('common.applyFilter') }}
            </button>
        </form>
    </BaseModal>
</template>
