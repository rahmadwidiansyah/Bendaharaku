<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility';
import { formatNumber, formatDate } from '@/utils/format.js';

const { isBalanceVisible } = useBalanceVisibility();

const props = defineProps({
    loanDetails: Array,
    title: String,
    isDebt: Boolean,
    total: Number,
});

const maskedAmount = '••••••';
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head :title="title" />

        <div class="p-5 pb-32 w-full lg:max-w-4xl mx-auto lg:px-8 relative animate-slide-up"
            style="animation-delay: 50ms;">
            <header class="flex justify-between items-center mb-8 pt-4">
                <div>
                    <h1 class="text-2xl font-bold text-white tracking-tight">{{ title }}</h1>
                </div>
                <Link :href="route('dashboard')"
                    class="w-10 h-10 rounded-full bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-gray-400 active:scale-90 transition-all shadow-md hover:text-white hover:border-gray-500"
                    aria-label="Kembali ke Dashboard">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </Link>
            </header>

            <!-- Total Card -->
            <div class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-6 text-center mb-8">
                <p :class="['text-2xs font-bold uppercase tracking-widest mb-1', isDebt ? 'text-yellow-500' : 'text-pink-500']">
                    Total Aktif
                </p>
                <h2 class="text-3xl font-bold text-white tracking-tight">
                    <span v-if="isBalanceVisible">Rp {{ formatNumber(total) }}</span>
                    <span v-else class="tracking-widest text-gray-400">{{ maskedAmount }}</span>
                </h2>
            </div>

            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4">
                Daftar {{ isDebt ? 'Pemberi Hutang' : 'Yang Ngutang' }}
            </h2>

            <div class="space-y-3">
                <template v-if="loanDetails.length > 0">
                    <div v-for="loan in loanDetails" :key="loan.subject"
                        class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-4 rounded-xl relative overflow-hidden group hover:border-white/20 transition-colors">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="text-lg font-bold text-white leading-none">{{ loan.subject }}</p>
                                <p class="text-xs text-gray-400 font-medium mt-1">
                                    Sisa:
                                    <span v-if="isBalanceVisible"
                                        :class="['font-bold', isDebt ? 'text-red-400' : 'text-green-400']">
                                        Rp {{ formatNumber(loan.balance) }}
                                    </span>
                                    <span v-else class="font-bold text-gray-500">{{ maskedAmount }}</span>
                                </p>
                            </div>

                            <div class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 px-3 py-1.5 rounded-xl text-center flex flex-col justify-center">
                                <p class="text-xs font-bold text-white leading-tight">{{ loan.age }}</p>
                                <p class="text-2xs font-bold text-gray-500 uppercase tracking-widest">Hari</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1 mt-3 pt-3 border-t border-white/10">
                            <svg class="w-3 h-3 text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-2xs text-gray-400">
                                Transaksi terakhir:
                                <span class="font-bold">{{ formatDate(loan.latest_date) }}</span>
                            </p>
                        </div>
                    </div>
                </template>

                <div v-else class="text-center py-12 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl">
                    <p class="text-2xl mb-2">🎉</p>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Bersih dari beban!</p>
                    <p class="text-2xs text-gray-600 mt-1">Tidak ada {{ isDebt ? 'hutang' : 'piutang' }} aktif saat ini.</p>
                </div>
            </div>
        </div>

        <!-- BottomNav TIDAK di-render di sini — sudah ditangani oleh AuthenticatedLayout -->
    </AuthenticatedLayout>
</template>

<style scoped>
/* Animasi dipindahkan ke app.css, ini hanya fallback lokal jika belum ada di sana */
@keyframes slide-up {
    from { transform: translateY(15px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}
.animate-slide-up {
    animation: slide-up 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}
</style>
