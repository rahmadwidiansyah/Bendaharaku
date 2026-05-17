<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <nav
        class="fixed bottom-0 rounded-t-2xl left-1/2 -translate-x-1/2 w-full max-w-md bg-gray-900/90 backdrop-blur-xl border-t border-white/10 pb-safe z-50">
        <div class="flex justify-around items-center pt-4 pb-1.5 px-3">

            <Link :href="route('dashboard')" class="flex flex-col items-center gap-1 transition-all duration-200"
                :class="route().current('dashboard') ? 'text-purple-500 scale-105' : 'text-gray-500 hover:text-gray-300'">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <span class="text-xs font-bold tracking-wider uppercase">Home</span>
            </Link>

            <Link :href="route('wallets.index')" class="flex flex-col items-center gap-1 transition-all duration-200"
                :class="route().current('wallets.*') ? 'text-purple-500 scale-105' : 'text-gray-500 hover:text-gray-300'">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
                </svg>
                <span class="text-xs font-bold tracking-wider uppercase">Aset</span>
            </Link>

            <Link :href="route('analytics.index')" class="flex flex-col items-center gap-1 transition-all duration-200"
                :class="route().current('analytics.*') ? 'text-purple-500 scale-105' : 'text-gray-500 hover:text-gray-300'">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11 3.055A9.003 9.003 0 003.055 11H11V3.055zM20.945 13H13v7.945a9.003 9.003 0 007.945-7.945z">
                    </path>
                </svg>
                <span class="text-xs font-bold tracking-wider uppercase">Grafik</span>
            </Link>


            <Link :href="route('categories.index')" class="flex flex-col items-center gap-1 transition-all duration-200"
                :class="route().current('categories.*') ? 'text-purple-500 scale-105' : 'text-gray-500 hover:text-gray-300'">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                <span class="text-xs font-bold tracking-wider uppercase">Label</span>
            </Link>

        </div>
    </nav>
</template>
