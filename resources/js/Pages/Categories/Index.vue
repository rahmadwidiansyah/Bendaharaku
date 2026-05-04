<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    groupedCategories: Object,
    totalCategories: Number,
});

const desiredOrder = ['Income', 'Expense', 'Transfer', 'Debt', 'Receivable'];

const sortedGroups = computed(() => {
    return Object.entries(props.groupedCategories).sort((a, b) => {
        const posA = desiredOrder.indexOf(a[0]);
        const posB = desiredOrder.indexOf(b[0]);
        return (posA === -1 ? 999 : posA) - (posB === -1 ? 999 : posB);
    });
});

const getTheme = (typeName) => {
    return {
        'Income': { text: 'text-green-400', bg: 'bg-green-500', glow: 'hover:shadow-[0_0_15px_rgba(74,222,128,0.2)]', border: 'hover:border-green-500/50' },
        'Expense': { text: 'text-gray-300', bg: 'bg-gray-400', glow: 'hover:shadow-[0_0_15px_rgba(156,163,175,0.2)]', border: 'hover:border-gray-500/50' },
        'Transfer': { text: 'text-blue-400', bg: 'bg-blue-500', glow: 'hover:shadow-[0_0_15px_rgba(59,130,246,0.2)]', border: 'hover:border-blue-500/50' },
        'Debt': { text: 'text-yellow-400', bg: 'bg-yellow-500', glow: 'hover:shadow-[0_0_15px_rgba(229,208,126,0.2)]', border: 'hover:border-[#E5D07E]/50' },
        'Receivable': { text: 'text-pink-400', bg: 'bg-pink-500', glow: 'hover:shadow-[0_0_15px_rgba(252,165,255,0.2)]', border: 'hover:border-purple-500/50' },
    }[typeName] || { text: 'text-white', bg: 'bg-white', glow: 'hover:shadow-[0_0_15px_rgba(255,255,255,0.2)]', border: 'hover:border-white/100' };
};

const getHeaderText = (typeName) => {
    return {
        'Income': 'Pemasukan',
        'Expense': 'Pengeluaran',
        'Transfer': 'Transfer',
        'Debt': 'Kategori Hutang',
        'Receivable': 'Kategori Piutang',
    }[typeName] || 'Lainnya';
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Vault Kategori" />

        <div class="p-5 pb-32 max-w-md mx-auto relative z-10 min-h-screen">
            
            <header class="mb-10 pt-4 animate-fade-in-up">
                <div class="flex justify-between items-end mb-8 px-1">
                    <div>
                        <p class="text-xs text-gray-300 font-bold uppercase tracking-[0.3em] mb-1 opacity-80">✨ Collection</p>
                        <h1 class="text-3xl font-bold text-white tracking-tighter leading-none">Vault <span class="text-gray-500">Kategori</span></h1>
                    </div>
                    
                    <div class="flex flex-col items-end">
                        <span class="text-xs text-gray-500 font-bold uppercase tracking-[0.2em] leading-none">Total</span>
                        <div class="flex items-center gap-1.5 my-1">
                            <div class="w-1.5 h-1.5 rounded-full bg-purple-500 shadow-[0_0_8px_purple] mr-0.5"></div>
                            <span class="text-lg font-bold text-white leading-none">{{ totalCategories }}</span>
                        </div>
                    </div>
                </div>

                <Link :href="route('categories.create')" 
                   class="relative w-full h-16 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl flex items-center justify-between px-6 active:scale-[0.97] transition-all group overflow-hidden shadow-2xl">
                    <div class="absolute inset-0 bg-gradient-to-r from-purple-900/0 via-purple-500/5 to-purple-900/0 -translate-x-full group-hover:translate-x-full transition-transform duration-1000 ease-in-out"></div>
                    
                    <div class="relative z-10 flex items-center gap-4">
                        <div class="w-8 h-8 rounded-lg bg-purple-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-white uppercase tracking-wide">Tambah Kategori</span>
                            <span class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-0.5">Organisir pengeluaran baru</span>
                        </div>
                    </div>

                    <div class="relative z-10 flex items-center">
                        <div class="w-8 h-8 rounded-full border border-white/10 flex items-center justify-center group-hover:border-purple-500/50 transition-colors">
                            <svg class="w-4 h-4 text-gray-500 group-hover:text-purple-500 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                <path d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </Link>
            </header>

            <div v-for="([typeName, categories], index) in sortedGroups" :key="typeName" class="mb-10 animate-fade-in-up" :style="{ animationDelay: (index * 100) + 'ms' }">
                <div class="flex items-center gap-3 mb-5 px-1">
                    <div class="w-1.5 h-1.5 rounded-full" :class="getTheme(typeName).bg"></div>
                    <h2 class="text-[11px] font-bold uppercase tracking-[0.2em]" :class="getTheme(typeName).text">
                        {{ getHeaderText(typeName) }}
                    </h2>
                    <div class="flex-1 h-px bg-gradient-to-r from-purple-500 to-transparent"></div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <Link v-for="category in categories" :key="category.id" :href="route('categories.show', category.id)"
                       class="relative group bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-4 flex flex-col items-center justify-center text-center active:scale-95 transition-all duration-300"
                       :class="[getTheme(typeName).glow, getTheme(typeName).border]">
                        
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center text-2xl border border-white/10 shrink-0 shadow-inner overflow-hidden p-0.5 mb-2.5 transition-transform duration-300 group-hover:scale-110 group-hover:-translate-y-1">
                            <img v-if="category.icon?.includes('.')" :src="'/storage/' + category.icon" class="w-full h-full object-contain p-1">
                            <span v-else class="drop-shadow-md">{{ category.icon || '📁' }}</span>
                        </div>
                        
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest line-clamp-2 leading-tight group-hover:text-gray-200 transition-colors">
                            {{ category.category_name }}
                        </p>
                    </Link>
                </div>
            </div>

            <div v-if="Object.keys(groupedCategories).length === 0" class="text-center py-10 bg-gradient-to-br from-gray-900 to-gray-800 backdrop-blur-sm rounded-xl border border-white/10 mt-8 animate-fade-in-up flex flex-col items-center relative overflow-hidden group">
                <p class="text-xs font-bold text-white uppercase tracking-widest relative z-10">Kategori Masih Kosong</p>
                <p class="text-xs font-medium text-gray-500 mt-2 max-w-[200px] leading-relaxed relative z-10">Buat kategori pertamamu sekarang untuk mulai mencatat keuangan.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in-up { 0% { opacity: 0; transform: translateY(15px); } 100% { opacity: 1; transform: translateY(0); } }
.animate-fade-in-up { animation: fade-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
</style>
