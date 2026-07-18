<script setup>
/**
 * GlobalHeader.vue
 *
 * Global Top App Bar — muncul di seluruh halaman yang menggunakan
 * AuthenticatedLayout.  Menggantikan MobileHeader.vue sepenuhnya.
 *
 * ── Layout ──────────────────────────────────────────────────────────
 *
 *   ┌────────────────────────────────────────┐
 *   │ 👋 Good Morning, Rahmad        🔔 💬 👤│
 *   │ Bendaharaku                        │
 *   └────────────────────────────────────────┘
 *
 * ── Scroll behavior ─────────────────────────────────────────────────
 *
 *   expanded  → greeting + nama user (scroll position 0)
 *   collapsed → nama halaman + "Bendaharaku" (saat user scroll ke bawah)
 *
 *   Threshold scroll: 40px
 *   Animasi: smooth 300ms via CSS transition
 *
 * ── Safe Area ───────────────────────────────────────────────────────
 *
 *   Padding atas menggunakan env(safe-area-inset-top) untuk support
 *   iPhone notch, Dynamic Island, dan Android cutout.
 *
 * Props:
 *   title — Override judul halaman (opsional, default: route-based)
 */

import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage }       from '@inertiajs/vue3'
import { useI18n }       from 'vue-i18n'
import HeaderGreeting    from '@/Components/Header/HeaderGreeting.vue'
import HeaderActions     from '@/Components/Header/HeaderActions.vue'
import ProfileMenu       from '@/Components/Header/ProfileMenu.vue'

const props = defineProps({
    title: {
        type: String,
        default: null,
    },
})

// ─── Auth user ────────────────────────────────────────────────────
const page    = usePage()
const user    = computed(() => page.props.auth?.user ?? null)
const { t }   = useI18n()

// ─── Scroll collapse ──────────────────────────────────────────────
const SCROLL_THRESHOLD = 40
const isCollapsed       = ref(false)
let scrollTarget        = null

const onScroll = (e) => {
    const scrollY = e?.target?.scrollTop ?? window.scrollY
    isCollapsed.value = scrollY > SCROLL_THRESHOLD
}

// Mount: listen ke scroll di #main-content (overflow container utama)
onMounted(() => {
    // Coba listen ke elemen main (lebih akurat untuk mobile scroll)
    scrollTarget = document.getElementById('main-content')
    if (scrollTarget) {
        scrollTarget.addEventListener('scroll', onScroll, { passive: true })
    } else {
        window.addEventListener('scroll', onScroll, { passive: true })
    }
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
    'chat.index':          'AI Chat',
    'profile.edit':        t('profile.title'),
}))

const routeLabel = computed(() => {
    if (props.title) return props.title
    try {
        const current = route().current()
        if (current && routeTitleMap.value[current]) return routeTitleMap.value[current]
        const match = Object.keys(routeTitleMap.value).find((key) =>
            current?.startsWith(key.replace('.*', ''))
        )
        return match ? routeTitleMap.value[match] : 'Bendaharaku'
    } catch {
        return 'Bendaharaku'
    }
})

// ─── Profile menu ─────────────────────────────────────────────────
const showProfileMenu       = ref(false)
const profileMenuPosition   = ref({ top: 72, right: 16 })

const toggleProfileMenu = (rect) => {
    if (showProfileMenu.value) {
        showProfileMenu.value = false
        return
    }
    profileMenuPosition.value = {
        top:   Math.min(rect.bottom + 8, window.innerHeight - 280),
        right: Math.max(12, window.innerWidth - rect.right),
    }
    showProfileMenu.value = true
}

const closeProfileMenu = () => {
    showProfileMenu.value = false
}

// ─── Notifikasi (placeholder, future ready) ───────────────────────
const notifCount    = ref(0)
const hasUnreadChat = ref(false)

const handleOpenNotif = () => {
    // TODO: Buka notification center saat fitur tersedia
}
</script>

<template>
    <!--
        sticky top-0 z-30: tetap di atas, z-index sama dengan MobileHeader sebelumnya
        pt-safe: padding atas = max(16px, safe-area-inset-top) untuk notch/Dynamic Island
    -->
    <header
        class="lg:hidden sticky top-0 z-30 w-full"
        :class="[
            'transition-all duration-300 ease-out',
            isCollapsed
                ? 'bg-gray-900/98 backdrop-blur-2xl shadow-lg shadow-black/40 border-b border-white/6'
                : 'bg-gradient-to-b from-gray-900/95 to-gray-900/85 backdrop-blur-xl border-b border-white/5',
        ]"
        :aria-label="$t('nav.mainNav')"
        style="padding-top: max(0px, env(safe-area-inset-top, 0px));"
    >
        <!--
            Inner row — tinggi adaptif:
              expanded  : h-16 (64px)
              collapsed : h-14 (56px)
        -->
        <div
            class="flex items-center gap-2 px-4 transition-all duration-300"
            :class="isCollapsed ? 'h-14' : 'h-16'"
        >
            <!-- Kiri: Greeting atau judul halaman -->
            <HeaderGreeting
                :user-name="user?.name ?? ''"
                :route-label="routeLabel"
                :is-collapsed="isCollapsed"
            />

            <!-- Kanan: Action buttons -->
            <HeaderActions
                :user="user"
                :notif-count="notifCount"
                :has-unread-chat="hasUnreadChat"
                :is-profile-open="showProfileMenu"
                @open-notif="handleOpenNotif"
                @toggle-profile="toggleProfileMenu"
            />
        </div>
    </header>

    <!-- Profile menu — di-Teleport di dalam ProfileMenu sendiri -->
    <ProfileMenu
        :show="showProfileMenu"
        :user="user"
        :position="profileMenuPosition"
        @close="closeProfileMenu"
    />
</template>
