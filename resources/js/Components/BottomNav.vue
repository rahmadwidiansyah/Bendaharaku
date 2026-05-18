<script setup>
import { Link } from '@inertiajs/vue3';
import { useLayoutPreference } from '@/Composables/useLayoutPreference';

const { isDesktopLayout } = useLayoutPreference();

defineProps({
    isSidebarOpen: {
        type: Boolean,
        default: true
    }
});

defineEmits(['toggle']);
</script>

<template>
    <nav
        :class="[
            'fixed z-50 transition-all duration-300',
            // Mobile (Bottom Nav)
            'bottom-0 rounded-t-2xl left-1/2 -translate-x-1/2 w-full max-w-md bg-gray-900/90 backdrop-blur-xl border-t border-white/10 pb-safe',
            // Desktop (Sidebar)
            isDesktopLayout ? 'lg:bottom-auto lg:top-0 lg:left-0 lg:translate-x-0 lg:h-screen lg:border-t-0 lg:border-r lg:rounded-none lg:bg-gray-900 lg:flex lg:flex-col lg:justify-start lg:pt-8' : '',
            isDesktopLayout && isSidebarOpen ? 'lg:w-64' : (isDesktopLayout ? 'lg:w-20' : '')
        ]">
        
        <!-- Toggle Button (Desktop Only) -->
        <div :class="['px-4 mb-8 justify-end', isDesktopLayout ? 'hidden lg:flex' : 'hidden']">
            <button @click="$emit('toggle', !isSidebarOpen)" class="text-gray-400 hover:text-white transition-colors bg-gray-800 p-2 rounded-xl border border-white/10 hover:border-purple-500/50">
                <svg v-if="isSidebarOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <div :class="['flex justify-around items-center pt-4 pb-1.5 px-3', isDesktopLayout ? 'lg:flex-col lg:justify-start lg:gap-4 lg:px-4 lg:pt-0' : '']">

            <!-- Desktop Create Buttons -->
            <div :class="['w-full mb-4', isDesktopLayout ? 'hidden lg:flex lg:flex-col lg:gap-2' : 'hidden']">
                <Link :href="route('transactions.create')" 
                    class="flex flex-row items-center justify-center lg:justify-start gap-2 w-full px-3 py-3 rounded-xl text-white bg-gradient-to-br from-purple-800 to-purple-500 shadow-lg shadow-purple-500/20 active:scale-95 transition-all group">
                    <svg class="w-6 h-6 shrink-0 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span :class="['text-xs font-bold tracking-wider uppercase', !isSidebarOpen ? 'lg:hidden' : '']">Catat Baru</span>
                </Link>

                <a href="https://t.me/catatwidi_bot" target="_blank" 
                    class="flex flex-row items-center justify-center lg:justify-start gap-2 w-full px-3 py-3 rounded-xl text-white bg-blue-600/90 hover:bg-blue-500 shadow-lg shadow-blue-500/20 active:scale-95 transition-all group">
                    <svg class="w-6 h-6 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.31-.346-.11l-6.4 4.02-2.76-.89c-.6-.188-.612-.6.126-.89l10.814-4.17c.5-.188.948.116.822.885z"/>
                    </svg>
                    <span :class="['text-xs font-bold tracking-wider uppercase', !isSidebarOpen ? 'lg:hidden' : '']">Telegram</span>
                </a>
            </div>

            <Link :href="route('dashboard')" 
                :class="['flex flex-col items-center gap-1 transition-all duration-200 group', isDesktopLayout ? 'lg:flex-row lg:w-full lg:px-3 lg:py-3 lg:rounded-xl' : '', route().current('dashboard') ? (isDesktopLayout ? 'text-purple-500 lg:bg-purple-500/10 scale-105 lg:scale-100 lg:border lg:border-purple-500/30' : 'text-purple-500 scale-105') : (isDesktopLayout ? 'text-gray-500 hover:text-gray-300 lg:hover:bg-gray-800 lg:border lg:border-transparent' : 'text-gray-500 hover:text-gray-300')]">
                <svg class="w-6 h-6 shrink-0 transition-transform lg:group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <span :class="['text-xs font-bold tracking-wider uppercase', !isSidebarOpen ? 'lg:hidden' : '']">Home</span>
            </Link>

            <Link :href="route('wallets.index')" 
                :class="['flex flex-col items-center gap-1 transition-all duration-200 group', isDesktopLayout ? 'lg:flex-row lg:w-full lg:px-3 lg:py-3 lg:rounded-xl' : '', route().current('wallets.*') ? (isDesktopLayout ? 'text-purple-500 lg:bg-purple-500/10 scale-105 lg:scale-100 lg:border lg:border-purple-500/30' : 'text-purple-500 scale-105') : (isDesktopLayout ? 'text-gray-500 hover:text-gray-300 lg:hover:bg-gray-800 lg:border lg:border-transparent' : 'text-gray-500 hover:text-gray-300')]">
                <svg class="w-6 h-6 shrink-0 transition-transform lg:group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
                </svg>
                <span :class="['text-xs font-bold tracking-wider uppercase', !isSidebarOpen ? 'lg:hidden' : '']">Aset</span>
            </Link>

            <Link :href="route('analytics.index')" 
                :class="['flex flex-col items-center gap-1 transition-all duration-200 group', isDesktopLayout ? 'lg:flex-row lg:w-full lg:px-3 lg:py-3 lg:rounded-xl' : '', route().current('analytics.*') ? (isDesktopLayout ? 'text-purple-500 lg:bg-purple-500/10 scale-105 lg:scale-100 lg:border lg:border-purple-500/30' : 'text-purple-500 scale-105') : (isDesktopLayout ? 'text-gray-500 hover:text-gray-300 lg:hover:bg-gray-800 lg:border lg:border-transparent' : 'text-gray-500 hover:text-gray-300')]">
                <svg class="w-6 h-6 shrink-0 transition-transform lg:group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11 3.055A9.003 9.003 0 003.055 11H11V3.055zM20.945 13H13v7.945a9.003 9.003 0 007.945-7.945z">
                    </path>
                </svg>
                <span :class="['text-xs font-bold tracking-wider uppercase', !isSidebarOpen ? 'lg:hidden' : '']">Grafik</span>
            </Link>

            <Link :href="route('categories.index')" 
                :class="['flex flex-col items-center gap-1 transition-all duration-200 group', isDesktopLayout ? 'lg:flex-row lg:w-full lg:px-3 lg:py-3 lg:rounded-xl' : '', route().current('categories.*') ? (isDesktopLayout ? 'text-purple-500 lg:bg-purple-500/10 scale-105 lg:scale-100 lg:border lg:border-purple-500/30' : 'text-purple-500 scale-105') : (isDesktopLayout ? 'text-gray-500 hover:text-gray-300 lg:hover:bg-gray-800 lg:border lg:border-transparent' : 'text-gray-500 hover:text-gray-300')]">
                <svg class="w-6 h-6 shrink-0 transition-transform lg:group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                <span :class="['text-xs font-bold tracking-wider uppercase', !isSidebarOpen ? 'lg:hidden' : '']">Label</span>
            </Link>



        </div>
    </nav>
</template>
