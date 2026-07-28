<script setup>
/**
 * MobileHeader.vue
 *
 * Top bar mobile — judul halaman aktif + avatar (link ke settings).
 * Tersembunyi di desktop.
 */

import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import Avatar from '@/Components/Avatar.vue'

const props = defineProps({
    title: {
        type: String,
        default: null,
    },
})

const { t } = useI18n()
const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)

// ─── Avatar src ───────────────────────────────────────────────────
const avatarSrc = computed(() => {
    const avatar = user.value?.avatar
    if (!avatar) return null
    if (avatar.startsWith('http://') || avatar.startsWith('https://')) return avatar
    return `/storage/${avatar}`
})

// ─── Route → translation key ─────────────────────────────────────
// Nilai berupa t() key, bukan string hardcoded
const routeTitleMap = computed(() => ({
    'dashboard':           t('dashboard.title'),
    'wallets.index':       t('wallet.title'),
    'wallets.show':        t('wallet.titleEdit'),
    'wallets.create':      t('wallet.titleCreate'),
    'wallets.edit':        t('wallet.titleEdit'),
    'transactions.index':  t('transaction.title'),
    'transactions.create': t('transaction.title'),
    'transactions.edit':   t('transaction.titleEdit'),
    'analytics.index':     t('analytics.title'),
    'categories.index':    t('category.title'),
    'categories.create':   t('category.titleCreate'),
    'categories.edit':     t('category.titleEdit'),
    'categories.show':     t('category.titleEdit'),
    'loans.index':         t('loan.title'),
    'settings.index':      t('settings.title'),
    'settings.ai':         t('ai.title'),
    'settings.account.profile':        t('settings.title'),
}))

const routeTitle = computed(() => {
    try {
        const current = route().current()
        if (current && routeTitleMap.value[current]) return routeTitleMap.value[current]
        const match = Object.keys(routeTitleMap.value).find(key =>
            current?.startsWith(key.replace('.*', ''))
        )
        return match ? routeTitleMap.value[match] : 'Bendaharaku'
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
    -->
    <header
        class="lg:hidden sticky top-0 z-30 flex items-center justify-between px-4 h-12 bg-gradient-to-b from-gray-900 via-gray-900 to-gray-900/95 backdrop-blur-xl border-b border-white/5 shadow-md"
        :aria-label="$t('nav.mainNav')"
    >
        <!-- Judul halaman aktif -->
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
                :aria-label="$t('header.openSettings')"
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
