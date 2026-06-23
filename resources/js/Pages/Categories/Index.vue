<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useLayoutPreference } from '@/Composables/useLayoutPreference';

const { isDesktopLayout } = useLayoutPreference();

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
        'Income': { text: 'text-green-400', bg: 'bg-green-500', border: 'hover:border-green-500/50' },
        'Expense': { text: 'text-gray-300', bg: 'bg-gray-400', border: 'hover:border-gray-500/50' },
        'Transfer': { text: 'text-blue-400', bg: 'bg-blue-500', border: 'hover:border-blue-500/50' },
        'Debt': { text: 'text-yellow-400', bg: 'bg-yellow-500', border: 'hover:border-yellow-500/50' },
        'Receivable': { text: 'text-pink-400', bg: 'bg-pink-500', border: 'hover:border-purple-500/50' },
    }[typeName] || { text: 'text-white', bg: 'bg-white', border: 'hover:border-white/100' };
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
    <AuthenticatedLayout :fullWidth="true">

        <Head title="Vault Kategori" />

        <div class="p-5 pb-32 w-full lg:max-w-4xl mx-auto lg:px-8 relative z-10 min-h-screen">

            <header class="mb-10 pt-4 animate-fade-in-up">
                <div class="flex justify-between items-end mb-8 px-1">
                    <div>
                        <p class="text-2xs text-gray-300 font-bold uppercase tracking-[0.3em] mb-1 opacity-80">✨
                            Collection</p>
                        <h1 class="text-3xl font-bold text-white tracking-tighter leading-none">Vault <span
                                class="text-gray-500">Kategori</span></h1>
                    </div>

                    <div class="flex flex-col items-end">
                        <span
                            class="text-2xs text-gray-500 font-bold uppercase tracking-[0.2em] leading-none">Total</span>
                        <div class="flex items-center gap-1.5 my-1">
                            <div class="w-1.5 h-1.5 rounded-full bg-purple-500 shadow-[0_0_8px_purple] mr-0.5"></div>
                            <span class="text-lg font-bold text-white leading-none">{{ totalCategories }}</span>
                        </div>
                    </div>
                </div>

                <div :class="['grid grid-cols-1 gap-4', isDesktopLayout ? 'sm:grid-cols-1' : '']">
                    <Link :href="route('categories.create')"
                        class="relative w-full min-h-16 py-3 bg-linear-to-br from-purple-900/50 to-gray-800 border border-purple-500/30 rounded-xl flex items-center justify-between px-4 sm:px-6 active:scale-[0.97] transition-all group overflow-hidden shadow-2xl hover:border-purple-400">
                        <div
                            class="absolute inset-0 bg-linear-to-r from-blue-900/0 via-blue-500/5 to-blue-900/0 -translate-x-full group-hover:translate-x-full transition-transform duration-1000 ease-in-out">
                        </div>

                        <div class="relative z-10 flex items-center gap-3 sm:gap-4 w-full pr-8">
                            <div
                                class="w-9 h-9 shrink-0 rounded-xl bg-purple-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                    </path>
                                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span class="text-xl font-black text-white">+</span>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span
                                    class="text-sm font-bold text-white uppercase tracking-wide truncate">Tambah Kategori</span>
                                <span
                                    class="text-2xs sm:text-2xs text-purple-300/70 font-bold uppercase tracking-widest mt-0.5 leading-tight">Buat kategori baru</span>
                            </div>
                        </div>

                        <div class="absolute right-4 sm:right-6 z-10 flex items-center top-1/2 -translate-y-1/2">
                            <div
                                class="w-8 h-8 rounded-full border border-white/10 flex items-center justify-center group-hover:border-purple-500/50 transition-colors">
                                <svg class="w-4 h-4 text-gray-500 group-hover:text-purple-400 group-hover:translate-x-0.5 transition-all"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                    <path d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </Link>
                </div>
            </header>

            <div v-for="([typeName, categories], index) in sortedGroups" :key="typeName"
                class="mb-10 animate-fade-in-up" :style="{ animationDelay: (index * 100) + 'ms' }">
                <div class="flex items-center gap-3 mb-5 px-1">
                    <div class="w-1.5 h-1.5 rounded-full" :class="getTheme(typeName).bg"></div>
                    <h2 class="text-[11px] font-bold uppercase tracking-[0.2em]" :class="getTheme(typeName).text">
                        {{ getHeaderText(typeName) }}
                    </h2>
                    <div class="flex-1 h-px bg-linear-to-r from-purple-500 to-transparent"></div>
                </div>

                <div :class="['grid grid-cols-3 gap-3', isDesktopLayout ? 'lg:grid-cols-6 lg:gap-5' : '']">
                    <Link v-for="category in categories" :key="category.id"
                        :href="route('categories.show', category.id)"
                        class="relative group bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-4 flex flex-col items-center justify-center text-center active:scale-95 transition-all duration-300"
                        :class="[getTheme(typeName).glow, getTheme(typeName).border]">

                        <div
                            class="w-12 h-12 rounded-xl bg-linear-to-br from-gray-900 to-gray-800 flex items-center justify-center text-2xl border border-white/10 shrink-0 shadow-inner overflow-hidden p-0.5 mb-2.5 transition-transform duration-300 group-hover:scale-110 group-hover:-translate-y-1">
                            <img v-if="category.icon?.includes('.')" :src="'/storage/' + category.icon"
                                class="w-full h-full object-contain p-1">
                            <span v-else class="drop-shadow-md">{{ category.icon || '📁' }}</span>
                        </div>

                        <p
                            class="text-[9px] font-bold text-gray-400 uppercase tracking-widest line-clamp-2 leading-tight group-hover:text-gray-200 transition-colors">
                            {{ category.category_name }}
                        </p>
                    </Link>
                </div>
            </div>

            <div v-if="Object.keys(groupedCategories).length === 0"
                class="text-center py-10 bg-linear-to-br from-gray-900 to-gray-800 backdrop-blur-sm rounded-xl border border-white/10 mt-8 animate-fade-in-up flex flex-col items-center relative overflow-hidden group">
                <p class="text-2xs font-bold text-white uppercase tracking-widest relative z-10">Kategori Masih Kosong
                </p>
                <p class="text-2xs font-medium text-gray-500 mt-2 max-w-[200px] leading-relaxed relative z-10">Buat
                    kategori pertamamu sekarang untuk mulai mencatat keuangan.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in-up {
    0% {
        opacity: 0;
        transform: translateY(15px);
    }

    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
}
</style>
