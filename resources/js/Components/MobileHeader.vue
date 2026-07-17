<script setup>
/**
 * MobileHeader.vue
 *
 * Top bar mobile — judul halaman aktif + avatar user dengan dropdown menu lengkap.
 * Tersembunyi di desktop (sidebar sudah menyediakan navigasi).
 *
 * Fitur:
 *   - Judul halaman dinamis dari route aktif
 *   - Avatar dengan foto profil / inisial fallback
 *   - Dropdown menu: Profil, Pengaturan, Wallet, Kategori, Tema, Bantuan, Logout
 *   - Animasi dropdown smooth (slide-down + fade)
 *   - Tutup dropdown saat klik di luar atau tekan Escape
 *
 * Props:
 *   title — Override judul halaman manual (opsional)
 */

import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { usePage, Link, router } from '@inertiajs/vue3'
import Avatar from '@/Components/Avatar.vue'

const props = defineProps({
    title: {
        type: String,
        default: null,
    },
})

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)

// ─── Avatar src ───────────────────────────────────────────────────
const avatarSrc = computed(() => {
    const avatar = user.value?.avatar
    if (!avatar) return null
    if (avatar.startsWith('http://') || avatar.startsWith('https://')) return avatar
    return `/storage/${avatar}`
})

// ─── Route → judul ───────────────────────────────────────────────
const routeTitleMap = {
    'dashboard':           'Dashboard',
    'wallets.index':       'Aset & Dompet',
    'wallets.show':        'Detail Dompet',
    'wallets.create':      'Dompet Baru',
    'wallets.edit':        'Edit Dompet',
    'transactions.index':  'Transaksi',
    'transactions.create': 'Catat Transaksi',
    'transactions.edit':   'Edit Transaksi',
    'analytics.index':     'Grafik & Analitik',
    'categories.index':    'Kategori',
    'categories.create':   'Kategori Baru',
    'categories.edit':     'Edit Kategori',
    'categories.show':     'Detail Kategori',
    'loans.index':         'Hutang & Piutang',
    'settings.index':      'Pengaturan',
    'settings.ai':         'Pengaturan AI',
    'profile.edit':        'Profil Saya',
}

const routeTitle = computed(() => {
    try {
        const current = route().current()
        if (current && routeTitleMap[current]) return routeTitleMap[current]
        const match = Object.keys(routeTitleMap).find(key =>
            current?.startsWith(key.replace('.*', ''))
        )
        return match ? routeTitleMap[match] : 'Bendaharaku'
    } catch {
        return 'Bendaharaku'
    }
})

const pageTitle = computed(() => props.title ?? routeTitle.value)

// ─── Dropdown ─────────────────────────────────────────────────────
const showMenu = ref(false)

const menuItems = [
    {
        label: 'Profil Saya',
        route: 'profile.edit',
        icon: 'user',
        color: 'text-purple-400',
    },
    {
        label: 'Pengaturan',
        route: 'settings.index',
        icon: 'settings',
        color: 'text-blue-400',
    },
    {
        label: 'Wallet',
        route: 'wallets.index',
        icon: 'wallet',
        color: 'text-green-400',
    },
    {
        label: 'Kategori',
        route: 'categories.index',
        icon: 'tag',
        color: 'text-yellow-400',
    },
    {
        label: 'Hutang & Piutang',
        route: 'loans.index',
        icon: 'loans',
        color: 'text-orange-400',
    },
]

const toggleMenu = () => { showMenu.value = !showMenu.value }
const closeMenu  = () => { showMenu.value = false }

const handleKeydown = (e) => {
    if (e.key === 'Escape') closeMenu()
}

onMounted(()      => document.addEventListener('keydown', handleKeydown))
onBeforeUnmount(() => document.removeEventListener('keydown', handleKeydown))

const handleLogout = () => {
    closeMenu()
    router.post(route('logout'))
}
</script>

<template>
    <!--
        Hanya tampil di mobile.
        Desktop menggunakan sidebar yang sudah menyediakan navigasi & context.
        
        Perbaikan UI/UX:
        - Judul halaman dengan typography yang lebih baik
        - Avatar dengan foto profil user atau fallback inisial
        - Dropdown menu lengkap dengan semua fitur
        - Smooth animation & transition
    -->
    <header
        class="lg:hidden sticky top-0 z-30 flex items-center justify-between px-4 h-16 bg-gradient-to-b from-gray-900 via-gray-900 to-gray-900/95 backdrop-blur-xl border-b border-white/5 shadow-md"
        aria-label="Header halaman"
    >
        <!-- Judul halaman aktif — typography hierarchy diperbaiki -->
        <div class="flex-1 min-w-0 mr-3">
            <h1 class="text-base font-black text-white tracking-tight truncate leading-tight">
                {{ pageTitle }}
            </h1>
            <p class="text-2xs text-gray-500 font-semibold uppercase tracking-widest mt-0.5 truncate">
                Bendaharaku
            </p>
        </div>

        <!-- Avatar button — buka dropdown -->
        <div class="relative shrink-0">
            <button
                type="button"
                @click="toggleMenu"
                :aria-expanded="showMenu"
                aria-haspopup="menu"
                aria-label="Buka menu akun"
                class="flex items-center justify-center rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900 active:scale-90 transition-all hover:shadow-lg hover:shadow-purple-500/20"
            >
                <Avatar
                    v-if="user"
                    :src="avatarSrc"
                    :name="user.name ?? 'U'"
                    size="sm"
                    :ring="true"
                />
                <div
                    v-else
                    class="w-8 h-8 rounded-full bg-gradient-to-br from-gray-800 to-gray-900 border-2 border-purple-500 animate-pulse"
                    aria-hidden="true"
                />
            </button>

            <!-- Dropdown menu — Teleport ke body agar tidak terjebak stacking context -->
            <Teleport to="body">
                <!-- Overlay backdrop -->
                <Transition
                    enter-active-class="transition-opacity duration-200 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-150 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="showMenu"
                        class="fixed inset-0 z-[9998]"
                        @click="closeMenu"
                        aria-hidden="true"
                    />
                </Transition>

                <!-- Menu panel — improved styling & animations -->
                <Transition
                    enter-active-class="transition-all duration-200 ease-out origin-top-right"
                    enter-from-class="opacity-0 scale-95 -translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition-all duration-150 ease-in origin-top-right"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 -translate-y-2"
                >
                    <div
                        v-if="showMenu"
                        role="menu"
                        class="fixed top-[4rem] right-3 z-[9999] w-64 rounded-2xl border border-white/10 bg-gradient-to-br from-gray-900 via-gray-900 to-gray-900/80 shadow-2xl shadow-black/80 overflow-hidden backdrop-blur-sm"
                    >
                        <!-- User info header — improved styling -->
                        <div class="px-4 py-3.5 border-b border-white/8 flex items-center gap-3">
                            <Avatar
                                v-if="user"
                                :src="avatarSrc"
                                :name="user.name ?? 'U'"
                                size="md"
                                :ring="false"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-white truncate leading-tight">{{ user?.name }}</p>
                                <p class="text-2xs text-gray-500 truncate mt-0.5">{{ user?.email }}</p>
                            </div>
                        </div>

                        <!-- Nav items — refined styling -->
                        <nav class="p-2" aria-label="Menu akun">
                            <Link
                                v-for="item in menuItems"
                                :key="item.route"
                                :href="route(item.route)"
                                role="menuitem"
                                @click="closeMenu"
                                class="flex items-center gap-3 w-full rounded-xl px-3.5 py-3 text-sm font-semibold text-gray-300 transition-all hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-1 focus-visible:ring-purple-400 active:scale-95 group"
                            >
                                <!-- Icon container with gradient -->
                                <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-gradient-to-br from-white/10 to-white/5 shrink-0 transition-all group-hover:from-white/15 group-hover:to-white/10" :class="item.color">
                                    <!-- Profil -->
                                    <svg v-if="item.icon === 'user'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <!-- Pengaturan -->
                                    <svg v-else-if="item.icon === 'settings'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <!-- Wallet -->
                                    <svg v-else-if="item.icon === 'wallet'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                    </svg>
                                    <!-- Kategori -->
                                    <svg v-else-if="item.icon === 'tag'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    <!-- Loans -->
                                    <svg v-else-if="item.icon === 'loans'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                                <span class="flex-1">{{ item.label }}</span>
                                <svg class="w-4 h-4 text-gray-600 group-hover:text-gray-500 opacity-0 group-hover:opacity-100 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </nav>

                        <!-- Divider -->
                        <div class="mx-3 h-px bg-white/8" aria-hidden="true" />

                        <!-- Logout — dengan styling khusus danger -->
                        <div class="p-2">
                            <button
                                type="button"
                                role="menuitem"
                                @click="handleLogout"
                                class="flex items-center gap-3 w-full rounded-xl px-3.5 py-3 text-sm font-semibold text-red-400 transition-all hover:bg-red-500/15 hover:text-red-300 focus:outline-none focus-visible:ring-1 focus-visible:ring-red-400 active:scale-95 group"
                            >
                                <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-red-500/10 shrink-0 transition-all group-hover:bg-red-500/20">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                </span>
                                <span class="flex-1">Keluar</span>
                                <svg class="w-4 h-4 text-red-600 group-hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </Transition>
            </Teleport>
        </div>
    </header>
</template>
