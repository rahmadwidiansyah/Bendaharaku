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
        class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-gray-400 hover:text-white rounded-xl px-4 flex items-center justify-center active:scale-95 transition-all relative h-[48px] z-30"
        aria-label="Pilih rentang waktu"
        @click="toggleModal"
    >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <!-- Dot indikator filter aktif -->
        <span
            v-if="isFiltered"
            class="absolute top-2 right-2 w-2 h-2 bg-purple-500 rounded-full"
            aria-label="Filter aktif"
        />
    </button>

    <!-- Modal via BaseModal -->
    <BaseModal
        :show="showModal"
        title="Rentang Waktu"
        max-width="sm"
        @close="showModal = false"
    >
        <form @submit.prevent="submit" class="space-y-5">
            <!-- Date inputs -->
            <div class="grid grid-cols-2 gap-3 text-left">
                <div class="space-y-1">
                    <label
                        for="date-start"
                        class="text-2xs font-bold text-purple-500 uppercase tracking-widest pl-1"
                    >
                        Dari
                    </label>
                    <input
                        id="date-start"
                        type="date"
                        v-model="form.start_date"
                        class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-3 text-2xs focus:outline-none focus:ring-1 focus:border-purple-500 focus:ring-purple-500 transition-all [color-scheme:dark]"
                    />
                </div>
                <div class="space-y-1">
                    <label
                        for="date-end"
                        class="text-2xs font-bold text-purple-500 uppercase tracking-widest pl-1"
                    >
                        Sampai
                    </label>
                    <input
                        id="date-end"
                        type="date"
                        v-model="form.end_date"
                        :min="form.start_date"
                        :class="[
                            'w-full bg-gradient-to-br from-gray-900 to-gray-800 border text-white rounded-xl p-3 text-2xs focus:outline-none focus:ring-1 transition-all [color-scheme:dark]',
                            isInvalidRange
                                ? 'border-red-500/60 focus:border-red-500 focus:ring-red-500'
                                : 'border-white/10 focus:border-purple-500 focus:ring-purple-500',
                        ]"
                    />
                </div>
            </div>

            <!-- Validation error -->
            <p
                v-if="isInvalidRange"
                role="alert"
                class="text-2xs text-red-400 font-bold -mt-2"
            >
                ⚠ Tanggal akhir harus sama atau setelah tanggal mulai.
            </p>

            <!-- Quick-date shortcuts -->
            <div class="grid grid-cols-3 gap-2 pt-1">
                <button
                    type="button"
                    class="bg-gradient-to-br from-gray-900 to-gray-800 text-2xs font-bold text-gray-400 py-3 rounded-xl border border-white/10 uppercase hover:text-white hover:border-white/20 transition-colors"
                    @click="setQuickDate('thisYear')"
                >
                    Tahun Ini
                </button>
                <button
                    type="button"
                    class="bg-gradient-to-br from-gray-900 to-gray-800 text-2xs font-bold text-gray-400 py-3 rounded-xl border border-white/10 uppercase hover:text-white hover:border-white/20 transition-colors"
                    @click="setQuickDate('thisMonth')"
                >
                    Bulan Ini
                </button>
                <button
                    type="button"
                    class="bg-gradient-to-br from-gray-900 to-gray-800 text-2xs font-bold text-gray-400 py-3 rounded-xl border border-white/10 uppercase hover:text-white hover:border-white/20 transition-colors"
                    @click="setQuickDate('lastMonth')"
                >
                    Bulan Lalu
                </button>
            </div>

            <!-- Submit -->
            <button
                type="submit"
                :disabled="form.processing || isInvalidRange"
                class="w-full bg-gradient-to-br from-purple-800 to-purple-500 text-white font-black text-2xs uppercase py-3.5 rounded-xl active:scale-95 transition-all shadow-lg shadow-purple-500/20 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100 flex items-center justify-center gap-2"
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
                {{ form.processing ? 'Menerapkan...' : 'Terapkan Filter' }}
            </button>
        </form>
    </BaseModal>
</template>
