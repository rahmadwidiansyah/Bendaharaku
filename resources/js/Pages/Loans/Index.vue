<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BottomNav from '@/Components/BottomNav.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    loanDetails: Array,
    title: String,
    isDebt: Boolean,
    total: Number,
});

const formatAmount = (amount) => {
    return new Intl.NumberFormat('id-ID').format(amount);
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="title" />
        
        <div class="p-5 pb-32 max-w-md mx-auto relative animate-slide-up" style="animation-delay: 50ms;">
            <header class="flex justify-between items-center mb-8 pt-4">
                <div>
                    <h1 class="text-2xl font-bold text-white tracking-tight">{{ title }}</h1>
                </div>
                <Link :href="route('dashboard')" class="w-10 h-10 rounded-full bg-[#1A1A1A] border border-[#333] flex items-center justify-center text-gray-400 active:scale-90 transition-all shadow-md hover:text-white hover:border-gray-500">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </Link>
            </header>

            <div :class="['border rounded-xl p-6 text-center mb-8', isDebt ? 'bg-[#E5D07E]/10 border-[#E5D07E]/30' : 'bg-[#FCA5FF]/10 border-[#FCA5FF]/30']">
                <p :class="['text-xs font-bold uppercase tracking-widest mb-1', isDebt ? 'text-[#E5D07E]' : 'text-[#FCA5FF]']">Total Aktif</p>
                <h2 class="text-3xl font-bold text-white tracking-tight">Rp {{ formatAmount(total) }}</h2>
            </div>

            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4">Daftar {{ isDebt ? 'Pemberi Hutang' : 'Yang Ngutang' }}</h2>

            <div class="space-y-3">
                <template v-if="loanDetails.length > 0">
                    <div v-for="loan in loanDetails" :key="loan.subject" class="bg-[#1A1A1A] p-4 rounded-xl border border-[#262626] relative overflow-hidden group hover:border-[#333] transition-colors">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="text-lg font-bold text-white leading-none">{{ loan.subject }}</p>
                                <p class="text-[9px] text-gray-500 font-medium mt-1">Sisa: 
                                    <span :class="['font-bold', isDebt ? 'text-[#E5D07E]' : 'text-[#FCA5FF]']">
                                        Rp {{ formatAmount(loan.balance) }}
                                    </span>
                                </p>
                            </div>
                            
                            <div class="bg-[#262626] px-3 py-1.5 rounded-xl border border-[#333] text-center flex flex-col justify-center">
                                <p class="text-[14px] font-bold text-white leading-tight">{{ loan.age }}</p>
                                <p class="text-[8px] font-bold text-gray-500 uppercase tracking-widest">Hari</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-1 mt-3 pt-3 border-t border-[#262626]">
                            <svg class="w-3 h-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <p class="text-xs text-gray-400">Transaksi terakhir: <span class="font-bold">{{ formatDate(loan.latest_date) }}</span></p>
                        </div>
                    </div>
                </template>
                <div v-else class="text-center py-10">
                    <span class="text-4xl block mb-2">🎉</span>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Bersih dari beban!</p>
                </div>
            </div>
        </div>

        <BottomNav />
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes slide-up { from { transform: translateY(15px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.animate-slide-up { animation: slide-up 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
</style>
