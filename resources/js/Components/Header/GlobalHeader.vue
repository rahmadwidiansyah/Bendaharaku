<script setup>
/**
 * GlobalHeader.vue
 *
 * Global Top App Bar — muncul di seluruh halaman AuthenticatedLayout.
 *
 * ── Layout ──────────────────────────────────────────────────────────
 *
 *   Dashboard:
 *   ┌─────────────────────────────────────────┐
 *   │ Selamat Pagi ☀️              🔔  💬  👤 │
 *   │ Rahmad Widiansyah                        │
 *   └─────────────────────────────────────────┘
 *
 *   Halaman lain:
 *   ┌─────────────────────────────────────────┐
 *   │ Wallet                       🔔  💬  👤 │
 *   │ Bendaharaku                              │
 *   └─────────────────────────────────────────┘
 *
 * ── Scroll behavior ─────────────────────────────────────────────────
 *   expanded  → h-16 (default)
 *   collapsed → h-14 (setelah scroll 40px, hanya di Dashboard)
 *
 * ── Props (semua opsional, default otomatis) ────────────────────────
 *   title            — Override judul halaman
 *   showGreeting     — Paksa tampilkan/sembunyikan greeting (default: auto dari route)
 *   showNotification — Tampilkan tombol notifikasi (default: true)
 *   showChat         — Tampilkan shortcut AI Chat (default: true)
 *   showProfile      — Tampilkan avatar (default: true)
 *   showBackButton   — Tampilkan tombol back (default: false)
 *   backHref         — URL tombol back (default: browser history)
 */

import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, router }  from '@inertiajs/vue3'
import { useI18n }          from 'vue-i18n'
import HeaderGreeting       from '@/Components/Header/HeaderGreeting.vue'
import HeaderActions        from '@/Components/Header/HeaderActions.vue'
import ProfileMenu          from '@/Components/Header/ProfileMenu.vue'

const props = defineProps({
    title:            { type: String,  default: null },
    showGreeting:     { type: Boolean, default: null },   // null = auto
    showNotification: { type: Boolean, default: true  },
    showChat:         { type: Boolean, default: true  },
    showProfile:      { type: Boolean, default: true  },
    showBackButton:   { type: Boolean, default: false },
    backHref:         { type: String,  default: null  },
})

// ─── Auth user ────────────────────────────────────────────────────
const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const { t } = useI18n()

// ─── Deteksi halaman Dashboard ────────────────────────────────────
const isDashboard = computed(() => {
    try {
        return route().current('dashboard')
    } catch {
        return false
    }
})

// showGreeting: jika prop diset manual, ikuti prop; jika null, auto dari route
const shouldShowGreeting = computed(() =>
    props.showGreeting !== null ? props.showGreeting : isDashboard.value
)

// ─── Scroll collapse (hanya aktif di Dashboard) ───────────────────
const SCROLL_THRESHOLD = 40
const isCollapsed       = ref(false)
let   scrollTarget      = null

const onScroll = (e) => {
    if (!shouldShowGreeting.value) {
        isCollapsed.value = false
        return
    }
    const scrollY = e?.target?.scrollTop ?? window.scrollY
    isCollapsed.value = scrollY > SCROLL_THRESHOLD
}

onMounted(() => {
    scrollTarget = document.getElementById('main-content')
    if (scrollTarget) {
        scrollTarget.addEventListener('scroll', onScroll, { passive: true })
    } else {
        window.addEventListener('scroll', onScroll, { passive: true })
    }
    // Reset collapsed saat mount (navigasi halaman baru)
    isCollapsed.value = false
})

onUnmounted(() => {
    if (scrollTarget) {
        scrollTarget.removeEventListener('scroll', onScroll)
    } else {
        window.removeEventListener('scroll', onScroll)
    }
})

// ─── Route → label halaman ────────────────────────────────────────
const routeTitleMap = computed(() => ({
    'dashboard':                  t('dashboard.title'),
    'wallets.index':              t('wallet.title'),
    'wallets.show':               t('wallet.titleEdit'),
    'wallets.create':             t('wallet.titleCreate'),
    'wallets.edit':               t('wallet.titleEdit'),
    'transactions.index':         t('transaction.title'),
    'transactions.create':        t('transaction.title'),
    'transactions.edit':          t('transaction.titleEdit'),
    'analytics.index':            t('analytics.title'),
    'categories.index':           t('category.title'),
    'categories.create':          t('category.titleCreate'),
    'categories.edit':            t('category.titleEdit'),
    'categories.show':            t('category.titleEdit'),
    'loans.index':                t('loan.title'),
    'settings.index':             t('settings.title'),
    'settings.ai.index':          t('ai.title'),
    'settings.ai.bot':            t('chatBot.title'),
    'chat.index':                 'AI Chat',
    'settings.account.profile':   t('profile.title'),
}))

const routeLabel = computed(() => {
    if (props.title) return props.title
    try {
        const current = route().current()
        if (current && routeTitleMap.value[current]) return routeTitleMap.value[current]
        // Prefix match: wallets.* → wallet.title, dst.
        const match = Object.keys(routeTitleMap.value).find(key =>
            current?.startsWith(key.split('.')[0])
        )
        return match ? routeTitleMap.value[match] : 'Bendaharaku'
    } catch {
        return 'Bendaharaku'
    }
})

// ─── Tinggi header ────────────────────────────────────────────────
// Dashboard expanded → h-16; collapsed atau halaman lain → h-14
const headerHeight = computed(() =>
    shouldShowGreeting.value && !isCollapsed.value ? 'h-16' : 'h-14'
)

// ─── Profile menu ─────────────────────────────────────────────────
const showProfileMenu     = ref(false)
const profileMenuPosition = ref({ top: 76, right: 16 })

const toggleProfileMenu = (rect) => {
    if (showProfileMenu.value) {
        showProfileMenu.value = false
        return
    }
    // rect dari button 44px, menu muncul tepat di bawah visual avatar (sekitar 36px dari top button)
    const safeTop   = rect.bottom + 4
    const safeRight = Math.max(12, window.innerWidth - rect.right)
    profileMenuPosition.value = {
        top:   Math.min(safeTop, window.innerHeight - 320),
        right: safeRight,
    }
    showProfileMenu.value = true
}

const closeProfileMenu = () => { showProfileMenu.value = false }

// ─── Back button ──────────────────────────────────────────────────
const handleBack = () => {
    if (props.backHref) {
        router.visit(props.backHref)
    } else {
        window.history.back()
    }
}

// ─── Notifikasi (future-ready) ────────────────────────────────────
const notifCount    = ref(0)
const hasUnreadChat = ref(false)
const handleOpenNotif = () => { /* TODO: notification center */ }
</script>

<template>
    <!--
        lg:hidden — Header ini hanya untuk mobile/tablet.
        Desktop menggunakan sidebar (BottomNav lgVariant).
        safe-area-inset-top: support iPhone notch, Dynamic Island, Android cutout.
    -->
    <header
        class="lg:hidden sticky top-0 z-30 w-full transition-all duration-300 ease-out"
        :class="[
            isCollapsed
                ? 'bg-gray-800/98 backdrop-blur-2xl shadow-md shadow-black/50 border-b border-white/[0.06]'
                : 'bg-gray-800/90 backdrop-blur-xl border-b border-white/[0.05]',
        ]"
        :aria-label="$t('nav.mainNav')"
        style="padding-top: env(safe-area-inset-top, 0px);"
    >
        <div
            class="flex items-center gap-2 px-4 transition-all duration-300"
            :class="headerHeight"
        >
            <!-- ── Back Button (opsional) ── -->
            <button
                v-if="showBackButton"
                type="button"
                class="shrink-0 w-9 h-9 flex items-center justify-center rounded-2xl text-gray-400 hover:text-white hover:bg-white/8 active:scale-90 transition-all duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400 mr-0.5"
                :aria-label="$t('btn.back')"
                @click="handleBack"
            >
                <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- ── Kiri: Judul / Greeting ── -->
            <HeaderGreeting
                :user-name="user?.name ?? ''"
                :route-label="routeLabel"
                :is-dashboard="shouldShowGreeting"
                :is-collapsed="isCollapsed"
            />

            <!-- ── Kanan: Action icons ── -->
            <HeaderActions
                :user="user"
                :notif-count="notifCount"
                :has-unread-chat="hasUnreadChat"
                :is-profile-open="showProfileMenu"
                :show-notification="showNotification"
                :show-chat="showChat"
                :show-profile="showProfile"
                @open-notif="handleOpenNotif"
                @toggle-profile="toggleProfileMenu"
            />
        </div>
    </header>

    <!-- Profile menu — Teleport ada di dalam ProfileMenu sendiri -->
    <ProfileMenu
        :show="showProfileMenu"
        :user="user"
        :position="profileMenuPosition"
        @close="closeProfileMenu"
    />
</template>
