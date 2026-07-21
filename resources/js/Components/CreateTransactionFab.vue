<script setup>
/**
 * CreateTransactionFab.vue
 *
 * Tombol aksi utama bergaya tombol Pay di e-wallet.
 * Tampil di mobile, tersembunyi di desktop.
 */

import { Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'

const isOpen = ref(false)
const { isDesktopLayout } = useLayoutPreference()

const toggle = () => { isOpen.value = !isOpen.value }
const close  = () => { isOpen.value = false }
</script>

<template>
    <!--
        Posisi: tepat di atas BottomNav (bottom-[4.5rem]).
        pointer-events-none di wrapper agar area kosong tidak menghalangi scroll,
        pointer-events-auto dikembalikan di elemen interaktif.
    -->
    <div
        :class="[
            'fixed bottom-[4.5rem] left-1/2 -translate-x-1/2 w-full max-w-md px-4 z-40 pointer-events-none',
            isDesktopLayout ? 'lg:hidden' : '',
        ]"
    >
        <!-- Overlay backdrop -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm pointer-events-auto"
                style="z-index: -1"
                @click="close"
                aria-hidden="true"
            />
        </Transition>

        <!-- Sub-buttons: muncul ke atas saat isOpen -->
        <div class="flex flex-col gap-3 mb-3">

            <!-- Telegram Bot -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-3"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-3"
            >
                <a
                    v-if="isOpen"
                    href="https://t.me/catatwidi_bot"
                    target="_blank"
                    rel="noopener noreferrer"
                    @click="close"
                    aria-label="Catat via Telegram AI Bot"
                    class="pointer-events-auto w-full flex items-center gap-4 px-5 py-4 rounded-2xl active:scale-95 transition-transform"
                    style="background-color: #2AABEE; box-shadow: 0 8px 24px rgba(42,171,238,0.35);"
                    >
                >
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(255,255,255,0.2);">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.31-.346-.11l-6.4 4.02-2.76-.89c-.6-.188-.612-.6.126-.89l10.814-4.17c.5-.188.948.116.822.885z" />
                        </svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-black text-white leading-tight">Catat via AI Bot</p>
                        <p class="text-xs text-white/70 font-medium mt-0.5">Ketik santai di Telegram</p>
                    </div>
                    <svg class="w-4 h-4 shrink-0" style="color: rgba(255,255,255,0.6)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </Transition>

            <!-- Input Manual -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-3"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-3"
            >
                <Link
                    v-if="isOpen"
                    :href="route('transactions.create')"
                    @click="close"
                    aria-label="Catat transaksi manual"
                    class="pointer-events-auto w-full flex items-center gap-4 px-5 py-4 rounded-2xl active:scale-95 transition-transform bg-gradient-to-br from-purple-700 to-purple-500 shadow-lg shadow-purple-500/30"
                >
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(255,255,255,0.2);">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                        </svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-black text-white leading-tight">Catat Manual</p>
                        <p class="text-xs text-white/70 font-medium mt-0.5">Input form lengkap</p>
                    </div>
                    <svg class="w-4 h-4 shrink-0" style="color: rgba(255,255,255,0.6)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </Transition>
        </div>

        <!-- Tombol utama — pay button style -->
        <button
            type="button"
            @click="toggle"
            :aria-expanded="isOpen"
            aria-haspopup="true"
            :aria-label="isOpen ? 'Tutup menu catat transaksi' : 'Catat transaksi'"
            class="pointer-events-auto w-full flex items-center justify-center gap-3 py-4 px-6 rounded-2xl relative overflow-hidden active:scale-95 transition-transform focus:outline-none"
            :class="isOpen
                ? 'bg-gray-800 border border-white/10 shadow-lg shadow-black/40'
                : 'bg-gradient-to-br from-purple-700 via-purple-600 to-purple-500 shadow-lg shadow-purple-500/40 border border-purple-300/20'"
        >
            <!-- Shimmer sweep (hanya saat closed) -->
            <span
                v-if="!isOpen"
                class="absolute inset-0 pointer-events-none"
                style="background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent); animation: fab-shimmer 2.5s ease-in-out infinite;"
                aria-hidden="true"
            />

            <!-- Icon wrapper -->
            <span
                class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-transform duration-300"
                :class="isOpen ? 'rotate-45' : ''"
                :style="isOpen ? 'background: rgba(255,255,255,0.08)' : 'background: rgba(255,255,255,0.2)'"
            >
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </span>

            <!-- Label -->
            <span
                class="text-sm font-black uppercase tracking-widest"
                :class="isOpen ? 'text-gray-400' : 'text-white'"
            >
                {{ isOpen ? 'Tutup' : 'Catat Transaksi' }}
            </span>
        </button>
    </div>
</template>

<style scoped>
@keyframes fab-shimmer {
    0%   { transform: translateX(-100%); }
    60%  { transform: translateX(200%); }
    100% { transform: translateX(200%); }
}
</style>
