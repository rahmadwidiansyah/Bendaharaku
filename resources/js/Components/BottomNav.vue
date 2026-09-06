<script setup>
/**
 * BottomNav.vue
 *
 * Navigasi utama aplikasi:
 *   - Mobile: floating bottom navigation bar (5 item: Home, Transaksi, Aset, Grafik, Label)
 *   - Desktop: collapsible sidebar dengan logo/brand area
 *
 * Phase 4 changes:
 *   - Tambah "Transaksi" ke mobile nav (gantikan "Label" yang dipindah ke desktop)
 *   - Tambah brand/logo area di sidebar desktop saat terbuka
 *   - Perbaiki active indicator: pill background lebih jelas di mobile
 *   - Desktop: Loan + Settings + Label tetap ada di sidebar
 */

import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import { usePageLoading } from '@/Composables/usePageLoading'
import NavItem from '@/Components/NavItem.vue'

const { t } = useI18n()

const { isDesktopLayout } = useLayoutPreference()
const { pendingUrl, isLoading } = usePageLoading()

// Anticipated active: saat loading, cek apakah pendingUrl cocok dengan route item.
// Ini membuat navbar langsung highlight item tujuan tanpa menunggu navigasi selesai.
const isActive = (patterns) => {
    // Saat tidak loading, pakai route().current() seperti biasa
    if (!isLoading.value || !pendingUrl.value) {
        return patterns.some(p => route().current(p))
    }
    // Saat loading, cek pendingUrl
    const url = pendingUrl.value
    return patterns.some(p => {
        if (p === 'dashboard')       return /^\/$|\/dashboard/.test(url)
        if (p === 'budgeting.*')     return /\/budgeting/.test(url)
        if (p === 'transactions.*')  return /\/transactions/.test(url)
        if (p === 'analytics.*')     return /\/analytics/.test(url)
        if (p === 'categories.*')    return /\/categories/.test(url)
        if (p === 'loans.*')         return /\/loans/.test(url)
        if (p === 'settings.*')      return /\/settings/.test(url)
        return false
    })
}

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
            isDesktopLayout
                ? 'lg:bottom-auto lg:top-0 lg:left-0 lg:translate-x-0 lg:h-screen lg:border-t-0 lg:border-r lg:rounded-none lg:bg-[var(--color-surface-overlay)] lg:flex lg:flex-col lg:justify-start'
                : 'bottom-[calc(0.75rem+env(safe-area-inset-bottom,0px))] left-1/2 -translate-x-1/2 w-[calc(100%-2rem)] sm:max-w-lg md:max-w-2xl bg-[var(--color-surface-overlay)]/95 backdrop-blur-2xl border border-[var(--color-border-default)] rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.85)]',
            isDesktopLayout && isSidebarOpen ? 'lg:w-64' : (isDesktopLayout ? 'lg:w-20' : ''),
        ]"
        style="overflow: visible;"
        :aria-label="$t('nav.mainNav')"
        role="navigation"
    >

        <!-- ── Brand Area (Desktop Sidebar Only) ── -->
        <div :class="['items-center mb-6 pt-5 px-4', isDesktopLayout ? 'hidden lg:flex' : 'hidden']">
            <!-- Logo icon — selalu tampil -->
            <div class="w-9 h-9 shrink-0 bg-gradient-to-br from-brand-deep to-brand-soft rounded-xl flex items-center justify-center shadow-lg shadow-purple-500/30">
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
                :aria-label="isSidebarOpen ? $t('common.close') + ' sidebar' : $t('btn.back') + ' sidebar'"
                :class="['ml-auto shrink-0 text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] transition-colors p-1.5 rounded-lg hover:bg-[var(--color-surface-muted)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-brand)]', isSidebarOpen ? '' : 'mx-auto']"
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
        <div :class="['mx-4 mb-4 border-t border-[var(--color-border-subtle)]', isDesktopLayout ? 'hidden lg:block' : 'hidden']" />

        <!-- ── Quick Action Buttons (Desktop Only) ── -->
        <div :class="['w-full mb-4 flex-col gap-2 px-4', isDesktopLayout ? 'hidden lg:flex' : 'hidden']">
            <Link
                :href="route('transactions.create')"
                class="flex items-center justify-center lg:justify-start gap-2 w-full px-3 py-3 rounded-xl text-white bg-gradient-to-br from-brand-deep to-brand-soft shadow-lg shadow-purple-500/20 active:scale-95 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-300"
                :aria-label="$t('nav.record')"
            >
                <svg class="w-6 h-6 shrink-0 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span :class="['text-2xs font-bold tracking-wider uppercase', !isSidebarOpen ? 'lg:hidden' : '']">
                    {{ $t('nav.newRecord') }}
                </span>
            </Link>

            <Link
                :href="route('chat.index')"
                class="flex items-center justify-center lg:justify-start gap-2 w-full px-3 py-3 rounded-xl text-white bg-violet-600/90 hover:bg-violet-500 shadow-lg shadow-violet-500/20 active:scale-95 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-300"
                :aria-label="$t('nav.chat')"
            >
                <svg class="w-6 h-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z" />
                </svg>
                <span :class="['text-2xs font-bold tracking-wider uppercase', !isSidebarOpen ? 'lg:hidden' : '']">
                    {{ $t('nav.chat') }}
                </span>
            </Link>
        </div>

        <!-- ── Nav Items ── -->
        <div :class="[
            'flex pt-1.5 pb-1.5 px-2',
            isDesktopLayout ? 'lg:flex-col lg:justify-start lg:gap-0.5 lg:px-3 lg:pt-0 lg:items-stretch' : 'items-center justify-around gap-1',
        ]" style="overflow: visible;">

            <!-- Home -->
            <NavItem
                :href="route('dashboard')"
                :label="$t('nav.home')"
                :active="isActive(['dashboard'])"
                :is-desktop="isDesktopLayout"
                :sidebar-open="isSidebarOpen"
            >
                <template #icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                </template>
            </NavItem>

            <!-- Budgeting -->
            <NavItem
                :href="route('budgeting.index')"
                :label="$t('nav.budgeting')"
                :active="isActive(['budgeting.*'])"
                :is-desktop="isDesktopLayout"
                :sidebar-open="isSidebarOpen"
            >
                <template #icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </template>
            </NavItem>

            <!-- Catat — hidden on desktop (hanya mobile FAB yang tampil di tengah) -->
            <!--
            <NavItem
                v-if="isDesktopLayout"
                :href="route('transactions.create')"
                :label="$t('nav.record')"
                :active="isActive(['transactions.*'])"
                :is-desktop="true"
                :sidebar-open="isSidebarOpen"
            >
                <template #icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </NavItem>
            -->

            <!-- Catat mobile — Floating Action Button (FAB) menonjol dan bercahaya -->
            <div v-if="!isDesktopLayout" class="flex-1 flex flex-col items-center justify-center relative select-none" style="overflow: visible; z-index: 10;">
                <Link
                    :href="route('transactions.create')"
                    :aria-label="$t('nav.record')"
                    :aria-current="isActive(['transactions.*']) ? 'page' : undefined"
                    class="flex items-center justify-center rounded-full border-[4px] border-[var(--color-surface-base)] active:scale-95 hover:scale-105 active:translate-y-0.5 transition-all duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-brand)] cursor-pointer"
                    :class="[
                        'w-14 h-14 -mt-7 mb-1',
                        isActive(['transactions.*'])
                            ? 'bg-purple-500 shadow-lg shadow-purple-500/65'
                            : 'bg-purple-600 shadow-md shadow-purple-500/55',
                    ]"
                >
                    <svg class="w-7 h-7 text-white transition-transform duration-300 group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </Link>
                <span
                    class="text-[9px] font-black tracking-wider uppercase leading-none transition-all duration-300"
                    :class="isActive(['transactions.*']) ? 'text-purple-400' : 'text-gray-400'"
                >{{ $t('nav.record') }}</span>
            </div>

            <!-- Grafik (Analytics) -->
            <NavItem
                :href="route('analytics.index')"
                :label="$t('nav.analytics')"
                :active="isActive(['analytics.*'])"
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
                :label="$t('nav.label')"
                :active="isActive(['categories.*'])"
                :is-desktop="isDesktopLayout"
                :sidebar-open="isSidebarOpen"
            >
                <template #icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                </template>
            </NavItem>

            <!-- Tanggungan (Hutang & Piutang) — Desktop Only -->
            <NavItem
                v-if="isDesktopLayout"
                :href="route('loans.index', { type: 'debt' })"
                :label="$t('nav.loan')"
                :active="isActive(['loans.*'])"
                :is-desktop="true"
                :sidebar-open="isSidebarOpen"
            >
                <template #icon>
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75" />
                    </svg>
                </template>
            </NavItem>

            <!-- Pengaturan — Desktop Only -->
            <NavItem
                v-if="isDesktopLayout"
                :href="route('settings.index')"
                :label="$t('nav.settings')"
                :active="isActive(['settings.*'])"
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
