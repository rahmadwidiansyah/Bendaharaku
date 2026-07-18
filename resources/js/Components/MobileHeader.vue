<script setup>
/**
 * MobileHeader.vue
 *
 * Top bar mobile — judul halaman aktif + avatar (link ke settings).
 * Tersembunyi di desktop.
 */

import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
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

        <!-- Avatar button — langsung ke Settings -->
        <div class="relative shrink-0">
            <Link
                :href="route('settings.index')"
                aria-label="Buka Pengaturan"
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
            </Link>
        </div>
    </header>
</template>
