<script setup>
/**
 * BottomNav.vue
 *
 * Navigasi utama aplikasi:
 *   - Mobile: bottom navigation bar (5 item: Home, Transaksi, Aset, Grafik, Label)
 *   - Desktop: collapsible sidebar dengan logo/brand area
 *
 * Phase 4 changes:
 *   - Tambah "Transaksi" ke mobile nav (gantikan "Label" yang dipindah ke desktop)
 *   - Tambah brand/logo area di sidebar desktop saat terbuka
 *   - Perbaiki active indicator: pill background lebih jelas di mobile
 *   - Desktop: Loan + Settings + Label tetap ada di sidebar
 */

import { Link } from '@inertiajs/vue3'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import NavItem from '@/Components/NavItem.vue'

const { isDesktopLayout } = useLayoutPreference()

defineProps({
    isSidebarOpen: {
        type: Boolean,
        default: true,
    },
})

defineEmits(['toggle'])
</script>

<template>
    <nav
        :class="[
            'fixed z-50 transition-all duration-300',
            'bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md',
            'bg-gray-900/90 backdrop-blur-xl border-t border-white/10 rounded-t-2xl',
            '[padding-bottom:env(safe-area-inset-bottom,0px)]',
            isDesktopLayout ? 'lg:bottom-auto lg:top-0 lg:left-0 lg:translate-x-0 lg:h-screen lg:border-t-0 lg:border-r lg:rounded-none lg:bg-gray-900 lg:flex lg:flex-col lg:justify-start' : '',
            isDesktopLayout && isSidebarOpen ? 'lg:w-64' : (isDesktopLayout ? 'lg:w-20' : ''),
        ]"
        aria-label="Navigasi utama"
        role="navigation"
    >

        <!-- ── Brand Area (Desktop Sidebar Only) ── -->
        <div :class="['items-center mb-6 pt-5 px-4', isDesktopLayout ? 'hidden lg:flex' : 'hidden']">
            <!-- Logo icon — selalu tampil -->
            <div class="w-9 h-9 shrink-0 bg-gradient-to-br from-purple-800 to-purple-500 rounded-xl flex items-center justify-center shadow-lg shadow-purple-500/30">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <!-- Nama app — hanya saat sidebar terbuka -->
            <div :class="['ml-3 overflow-hidden transition-all duration-300', isSidebarOpen ? 'opacity-100 max-w-xs' : 'opacity-0 max-w-0']">
                <p class="text-sm font-black text-white tracking-tight leading-none whitespace-nowrap">Bendaharaku</p>
                <p class="text-2xs text-purple-400 font-bold uppercase tracking-widest mt-0.5 whitespace-nowrap">Finance Manager</p>
            </div>
            <!-- Toggle collapse button -->
            <button
                @click="$emit('toggle', !isSidebarOpen)"
                :aria-expanded="isSidebarOpen"
                :aria-label="isSidebarOpen ? 'Tutup sidebar' : 'Buka sidebar'"
                :class="['ml-auto shrink-0 text-gray-500 hover:text-white transition-colors p-1.5 rounded-lg hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400', isSidebarOpen ? '' : 'mx-auto']"
            >
                <svg v-if="isSidebarOpen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Divider bawah brand area -->
        <div :class="['mx-4 mb-4 border-t border-white/5', isDesktopLayout ? 'hidden lg:block' : 'hidden']" />

        <!-- ── Quick Action Buttons (Desktop Only) ── -->
        <div :class="['w-full mb-4 flex-col gap-2 px-4', isDesktopLayout ? 'hidden lg:flex' : 'hidden']">
            <Link
                :href="route('transactions.create')"
                class="flex items-center justify-center lg:justify-start gap-2 w-full px-3 py-3 rounded-xl text-white bg-gradient-to-br from-purple-800 to-purple-500 shadow-lg shadow-purple-500/20 active:scale-95 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-300"
                aria-label="Catat transaksi baru"
            >
                <svg class="w-6 h-6 shrink-0 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span :class="['text-2xs font-bold tracking-wider uppercase', !isSidebarOpen ? 'lg:hidden' : '']">
                    Catat Baru
                </span>
            </Link>

            <a
                href="https://t.me/catatwidi_bot"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center justify-center lg:justify-start gap-2 w-full px-3 py-3 rounded-xl text-white bg-blue-600/90 hover:bg-blue-500 shadow-lg shadow-blue-500/20 active:scale-95 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300"
                aria-label="Buka Telegram Bot"
            >
                <svg class="w-6 h-6 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.31-.346-.11l-6.4 4.02-2.76-.89c-.6-.188-.612-.6.126-.89l10.814-4.17c.5-.188.948.116.822.885z" />
                </svg>
                <span :class="['text-2xs font-bold tracking-wider uppercase', !isSidebarOpen ? 'lg:hidden' : '']">
                    Telegram
                </span>
            </a>
        </div>

        <!-- ── Nav Items ── -->
        <div :class="[
            'flex justify-around items-center pt-3 pb-1.5 px-2',
            isDesktopLayout ? 'lg:flex-col lg:justify-start lg:gap-0.5 lg:px-3 lg:pt-0' : '',
        ]">

            <!-- Home -->
            <NavItem
                :href="route('dashboard')"
                label="Home"
                :active="route().current('dashboard')"
                :is-desktop="isDesktopLayout"
                :sidebar-open="isSidebarOpen"
            >
                <template #icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                </template>
            </NavItem>

            <!-- Aset (Wallets) -->
            <NavItem
                :href="route('wallets.index')"
                label="Aset"
                :active="route().current('wallets.*')"
                :is-desktop="isDesktopLayout"
                :sidebar-open="isSidebarOpen"
            >
                <template #icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
                    </svg>
                </template>
            </NavItem>

            <!-- Catat (Transaksi) — center position, CTA utama -->
            <NavItem
                :href="route('transactions.create')"
                label="Catat"
                :active="route().current('transactions.*')"
                :is-desktop="isDesktopLayout"
                :sidebar-open="isSidebarOpen"
            >
                <template #icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </NavItem>

            <!-- Grafik (Analytics) -->
            <NavItem
                :href="route('analytics.index')"
                label="Grafik"
                :active="route().current('analytics.*')"
                :is-desktop="isDesktopLayout"
                :sidebar-open="isSidebarOpen"
            >
                <template #icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.003 9.003 0 003.055 11H11V3.055zM20.945 13H13v7.945a9.003 9.003 0 007.945-7.945z" />
                    </svg>
                </template>
            </NavItem>

            <!-- Label (Categories) — mobile + desktop -->
            <NavItem
                :href="route('categories.index')"
                label="Label"
                :active="route().current('categories.*')"
                :is-desktop="isDesktopLayout"
                :sidebar-open="isSidebarOpen"
            >
                <template #icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                </template>
            </NavItem>

            <!-- Hutang/Piutang — Desktop Only -->
            <NavItem
                v-if="isDesktopLayout"
                :href="route('loans.index', { type: 'debt' })"
                label="Hutang"
                :active="route().current('loans.*')"
                :is-desktop="true"
                :sidebar-open="isSidebarOpen"
            >
                <template #icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </NavItem>

            <!-- Pengaturan — Desktop Only -->
            <NavItem
                v-if="isDesktopLayout"
                :href="route('settings.index')"
                label="Pengaturan"
                :active="route().current('settings.*')"
                :is-desktop="true"
                :sidebar-open="isSidebarOpen"
            >
                <template #icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </template>
            </NavItem>

        </div>
    </nav>
</template>
