<script setup>
/**
 * MobileHeader.vue
 *
 * Top bar mobile — menampilkan judul halaman aktif dan avatar user.
 * Tersembunyi di desktop (sidebar sudah menyediakan context yang cukup).
 *
 * Masalah yang diselesaikan:
 *   - Mobile tidak punya top bar sehingga user kehilangan konteks di halaman mana
 *   - User profile/avatar tidak accessible dari mobile tanpa masuk ke Settings
 *
 * Fitur:
 *   - Judul halaman dinamis berdasarkan route aktif
 *   - Avatar user dengan fallback inisial (reuse Avatar.vue)
 *   - Link ke halaman profile saat avatar diklik
 *   - Sticky top untuk tetap terlihat saat scroll
 *   - Tersembunyi di desktop (lg:hidden)
 *
 * Props:
 *   title    — Override judul halaman manual (opsional, default auto dari routeTitle)
 *
 * Usage di AuthenticatedLayout:
 *   <MobileHeader />
 *
 * Usage dengan override judul:
 *   <MobileHeader title="Detail Transaksi" />
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

// User dari shared props Inertia
const user = computed(() => page.props.auth?.user ?? null)

// Map route name → judul yang ditampilkan
const routeTitleMap = {
    'dashboard':          'Dashboard',
    'wallets.index':      'Aset & Dompet',
    'wallets.show':       'Detail Dompet',
    'wallets.create':     'Dompet Baru',
    'wallets.edit':       'Edit Dompet',
    'transactions.index': 'Transaksi',
    'transactions.create':'Catat Transaksi',
    'transactions.edit':  'Edit Transaksi',
    'analytics.index':    'Grafik & Analitik',
    'categories.index':   'Kategori',
    'categories.create':  'Kategori Baru',
    'categories.edit':    'Edit Kategori',
    'categories.show':    'Detail Kategori',
    'loans.index':        'Hutang & Piutang',
    'settings.index':     'Pengaturan',
    'settings.ai':        'Pengaturan AI',
    'profile.edit':       'Profil Saya',
}

// Deteksi judul dari route aktif
const routeTitle = computed(() => {
    try {
        // route().current() mengembalikan nama route yang aktif
        const current = route().current()
        if (current && routeTitleMap[current]) {
            return routeTitleMap[current]
        }
        // Fallback: coba cari partial match (misal 'wallets.*')
        const match = Object.keys(routeTitleMap).find((key) =>
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
        Desktop menggunakan sidebar BottomNav yang sudah menyediakan context.
    -->
    <header
        class="lg:hidden sticky top-0 z-30 flex items-center justify-between px-5 h-14 bg-gray-900/80 backdrop-blur-xl border-b border-white/5"
        aria-label="Header halaman"
    >
        <!-- Judul halaman aktif -->
        <h1 class="text-sm font-black text-white tracking-tight truncate flex-1 min-w-0 mr-3">
            {{ pageTitle }}
        </h1>

        <!-- Avatar → link ke profile -->
        <Link
            :href="route('profile.edit')"
            aria-label="Buka halaman profil"
            class="shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900 rounded-full"
        >
            <Avatar
                v-if="user"
                :src="user.avatar ?? null"
                :name="user.name ?? 'U'"
                size="sm"
                :ring="true"
            />
            <!-- Placeholder saat user belum load -->
            <div
                v-else
                class="w-8 h-8 rounded-full bg-gray-800 border border-white/10 animate-pulse"
                aria-hidden="true"
            />
        </Link>
    </header>
</template>
