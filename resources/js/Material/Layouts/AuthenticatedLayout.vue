<script setup>
/**
 * Material/Layouts/AuthenticatedLayout.vue
 *
 * M3 Expressive shell — dipakai HANYA oleh halaman `Material/*`.
 * Halaman lain tetap memakai layout Default.
 *
 * Struktur (per references/components/bars.md + navigation.md + fabs.md):
 * - Desktop: top app bar (small) + navigation rail (expanded 15rem / collapsed 5rem) + FAB
 * - Mobile: TANPA app bar — judul halaman dirender in-content (M3PageTitle);
 *   navigation bar 5 destinasi (Beranda, Anggaran, Catat, Analitik, More) —
 *   aksi Catat sejajar dengan destinasi lain
 * - Aksi global di mobile (chat, akun) diakses lewat destinasi "More"
 * - FAB record: primary-container, 3.5rem, shape 28dp
 * - Skeleton overlay + stale/bfcache handlers: perilaku identik layout Default
 *
 * Semua warna memakai token `--m3-*` (aktif hanya di bawah `.ui-m3`).
 */
import M3Snackbar from '@/Material/Components/M3Snackbar.vue'
import M3WavyProgress from '@/Material/Components/M3WavyProgress.vue'
import M3ChatSheet from '@/Material/Components/Chat/M3ChatSheet.vue'
import M3DashboardSkeleton from '@/Material/Components/Skeleton/M3DashboardSkeleton.vue'
import M3BudgetSkeleton from '@/Material/Components/Skeleton/M3BudgetSkeleton.vue'
import M3AnalyticsSkeleton from '@/Material/Components/Skeleton/M3AnalyticsSkeleton.vue'
import M3WalletsSkeleton from '@/Material/Components/Skeleton/M3WalletsSkeleton.vue'
import M3WalletShowSkeleton from '@/Material/Components/Skeleton/M3WalletShowSkeleton.vue'
import M3CategoriesSkeleton from '@/Material/Components/Skeleton/M3CategoriesSkeleton.vue'
import M3CategoryShowSkeleton from '@/Material/Components/Skeleton/M3CategoryShowSkeleton.vue'
import M3LoansSkeleton from '@/Material/Components/Skeleton/M3LoansSkeleton.vue'
import M3TransactionsSkeleton from '@/Material/Components/Skeleton/M3TransactionsSkeleton.vue'
import M3SettingsHubSkeleton from '@/Material/Components/Skeleton/M3SettingsHubSkeleton.vue'
import M3SettingsSkeleton from '@/Material/Components/Skeleton/M3SettingsSkeleton.vue'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import { usePageLoading } from '@/Composables/usePageLoading'
import { initTheme } from '@/Composables/useTheme'
import { syncUiThemeFromProps } from '@/Composables/useUiTheme.js'
import { usePushNotifications } from '@/Composables/usePushNotifications'
import { usePage, router, Link } from '@inertiajs/vue3'
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { initM3ColorScheme, syncM3ColorSchemeFromProps } from '@/Composables/useM3ColorScheme.js'
import { consumeStaleForRoute } from '@/utils/stale.js'

const { t } = useI18n()

const SKELETON_COMPONENTS = {
    M3DashboardSkeleton,
    M3BudgetSkeleton,
    M3AnalyticsSkeleton,
    M3WalletsSkeleton,
    M3WalletShowSkeleton,
    M3CategoriesSkeleton,
    M3CategoryShowSkeleton,
    M3LoansSkeleton,
    M3TransactionsSkeleton,
    M3SettingsHubSkeleton,
    M3SettingsSkeleton,
}

const props = defineProps({
    fullWidth: { type: Boolean, default: false },
    hideNav: { type: Boolean, default: false },
    hideAppbar: { type: Boolean, default: false },
    title: { type: String, default: '' },
})

// State collapse rail dipertahankan antar navigasi (layout di-remount
// per halaman Inertia) dan antar reload via localStorage.
const RAIL_OPEN_KEY = 'bendaharaku:m3-rail-open'
const isRailOpen = ref(localStorage.getItem(RAIL_OPEN_KEY) !== 'closed')

const toggleRail = () => {
    isRailOpen.value = !isRailOpen.value
    localStorage.setItem(RAIL_OPEN_KEY, isRailOpen.value ? 'open' : 'closed')
}

// ── Chat side sheet (P1): bubble global, hanya desktop & di luar /chat ──
const chatSheetOpen = ref(false)
const isChatRoute = computed(() => {
    try { return route().current('chat.*') } catch { return false }
})
// Catatan: `isDesktop.value` — tanpa .value, ComputedRef selalu truthy dan
// bubble bocor ke mobile di semua halaman non-chat.
const showChatBubble = computed(() => isDesktop.value && !isChatRoute.value)
const { isDesktopLayout } = useLayoutPreference()
const { isLoading, currentSkeletonM3, pendingUrl } = usePageLoading()

// ── Theme init: verifikasi (kelas sudah dipasang pre-paint oleh Blade) ──
const page = usePage()
onMounted(() => {
    const userTheme = page.props.auth?.user?.theme ?? 'system'
    initTheme(userTheme)
    syncUiThemeFromProps(page.props)
    // Skema sudah dipasang head script Blade (tanpa flash); ini verifikasi
    // + sinkronisasi bila props berubah tanpa reload.
    if (page.props.auth?.user?.ui_theme === 'm3') {
        initM3ColorScheme()
        syncM3ColorSchemeFromProps(page.props)
    }
})

// ── Push presence ──
const push = usePushNotifications()
onMounted(() => {
    push.setVapidKey(page.props.vapidPublicKey ?? null)
    push.startPresence()
    push.updateState()
})
onUnmounted(() => {
    push.stopPresence()
})

// ── Global stale handler (perilaku identik layout Default) ──
function currentRouteName() {
    try { return route().current() ?? '' } catch { return '' }
}
function handleGlobalPageshow(e) {
    const r = currentRouteName()
    if (e?.persisted) {
        if (r && !r.startsWith('chat.')) {
            router.reload({ preserveScroll: true, preserveState: false })
        }
        return
    }
    if (consumeStaleForRoute(r)) {
        router.reload({ preserveScroll: true, preserveState: false })
    }
}
function handleGlobalVisibility() {
    if (document.visibilityState !== 'visible') return
    const r = currentRouteName()
    if (consumeStaleForRoute(r)) {
        router.reload({ preserveScroll: true, preserveState: false })
    }
}
function handleGlobalStaleEvent() {
    const r = currentRouteName()
    if (consumeStaleForRoute(r)) {
        router.reload({ preserveScroll: true, preserveState: false })
    }
}
function handleGlobalStorage(e) {
    if (!e.key?.startsWith('bendaharaku:stale')) return
    const r = currentRouteName()
    try {
        if (sessionStorage.getItem(e.key)) {
            if (consumeStaleForRoute(r)) router.reload({ preserveScroll: true, preserveState: false })
        } else if (e.newValue) {
            sessionStorage.setItem(e.key, e.newValue)
            if (consumeStaleForRoute(r)) router.reload({ preserveScroll: true, preserveState: false })
        }
    } catch {}
}
onMounted(() => {
    window.addEventListener('pageshow', handleGlobalPageshow)
    document.addEventListener('visibilitychange', handleGlobalVisibility)
    window.addEventListener('stale:updated', handleGlobalStaleEvent)
    window.addEventListener('bendaharaku:stale', handleGlobalStaleEvent)
    window.addEventListener('dashboard:stale', handleGlobalStaleEvent)
    window.addEventListener('storage', handleGlobalStorage)
    setTimeout(() => {
        const r = currentRouteName()
        if (r && !r.startsWith('chat.') && consumeStaleForRoute(r)) {
            router.reload({ preserveScroll: true, preserveState: false })
        }
    }, 300)
})
onUnmounted(() => {
    window.removeEventListener('pageshow', handleGlobalPageshow)
    document.removeEventListener('visibilitychange', handleGlobalVisibility)
    window.removeEventListener('stale:updated', handleGlobalStaleEvent)
    window.removeEventListener('bendaharaku:stale', handleGlobalStaleEvent)
    window.removeEventListener('dashboard:stale', handleGlobalStaleEvent)
    window.removeEventListener('storage', handleGlobalStorage)
})

// ── Active detection (logika identik BottomNav Default) ──
const isActive = (patterns) => {
    if (!isLoading.value || !pendingUrl.value) {
        return patterns.some(p => route().current(p))
    }
    const url = pendingUrl.value
    return patterns.some(p => {
        if (p === 'dashboard') return /^\/$|\/dashboard/.test(url)
        if (p === 'budgeting.*') return /\/budgeting/.test(url)
        if (p === 'transactions.*') return /\/transactions/.test(url)
        if (p === 'analytics.*') return /\/analytics/.test(url)
        if (p === 'categories.*') return /\/categories/.test(url)
        if (p === 'loans.*') return /\/loans/.test(url)
        if (p === 'settings.*') return /\/settings/.test(url)
        if (p === 'chat.*') return /\/chat/.test(url)
        if (p === 'search.*') return /\/search/.test(url)
        if (p === 'more') return /\/more/.test(url)
        return false
    })
}

// Destinasi "More" mencakup semua halaman yang tidak punya slot di navbar
// (label, tanggungan, chat, pencarian) agar salah satu destinasi selalu aktif.
const isMoreActive = computed(() => isActive(['more', 'categories.*', 'loans.*', 'chat.*', 'search.*']))

const userName = computed(() => page.props.auth?.user?.name ?? '')
const avatarUrl = computed(() => page.props.auth?.user?.avatar_url ?? '')

const isDesktop = computed(() => isDesktopLayout.value && !props.hideNav)

const activeSkeletonComponent = computed(() =>
    currentSkeletonM3.value ? (SKELETON_COMPONENTS[currentSkeletonM3.value] ?? null) : null,
)

const showSkeleton = computed(() =>
    isLoading.value && activeSkeletonComponent.value !== null && !props.hideNav,
)
</script>

<template>
    <div class="m3-shell">
        <M3Snackbar />

        <a href="#main-content" class="m3-skip-link">Langsung ke konten</a>

        <!-- Indikator navigasi global: wavy linear (M3 Expressive) -->
        <M3WavyProgress
            :active="isLoading"
            :below-app-bar="isDesktop && !hideAppbar"
            :class="isDesktop && !hideNav ? (isRailOpen ? 'm3-shell__wavy--rail-open' : 'm3-shell__wavy--rail') : ''"
            :aria-label="$t('common.loading')"
        />

        <!-- ── Top app bar (small, 4rem) — desktop only; mobile memakai judul in-content ── -->
        <header
            v-if="isDesktop && !hideAppbar"
            class="m3-appbar"
            :class="{
                'm3-appbar--rail': isDesktop && isRailOpen,
                'm3-appbar--rail-collapsed': isDesktop && !isRailOpen,
            }"
        >
            <div class="m3-appbar__leading">
                <Link :href="route('dashboard')" class="m3-appbar__brand-btn" aria-label="Bendaharaku">
                    <img src="/icons/logo.png" alt="Bendaharaku" class="m3-appbar__brand-logo" />
                </Link>
            </div>
            <div class="m3-appbar__title-block">
                <p class="m3-appbar__title">{{ title || $t('dashboard.title') }}</p>
                <p v-if="userName" class="m3-appbar__subtitle">{{ userName }}</p>
            </div>
            <div class="m3-appbar__actions">
                <Link :href="route('chat.index')" class="m3-appbar__icon-btn" :aria-label="$t('nav.chat')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z" />
                    </svg>
                </Link>
                <Link :href="route('search.page')" class="m3-appbar__icon-btn" :aria-label="$t('nav.search')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </Link>
                <Link :href="route('settings.index')" class="m3-appbar__avatar" :aria-label="$t('header.openSettings')">
                    <img v-if="avatarUrl" :src="avatarUrl" alt="" class="m3-appbar__avatar-img" />
                    <span v-else class="m3-appbar__avatar-fallback" aria-hidden="true">{{ (userName || 'B').charAt(0).toUpperCase() }}</span>
                </Link>
            </div>
        </header>

        <!-- ── Desktop navigation rail ── -->
        <nav v-if="isDesktop && !hideNav" class="m3-rail" :class="isRailOpen ? 'm3-rail--open' : ''" :aria-label="$t('nav.mainNav')" role="navigation">
            <Link :href="route('transactions.create')" class="m3-fab" :aria-label="$t('nav.record')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </Link>
            <div class="m3-rail__items">
                <Link :href="route('dashboard')" class="m3-rail__item" :class="{ 'm3-rail__item--active': isActive(['dashboard']) }" :aria-current="isActive(['dashboard']) ? 'page' : undefined">
                    <span class="m3-rail__indicator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                    </span>
                    <span v-if="isRailOpen" class="m3-rail__label">{{ $t('nav.home') }}</span>
                </Link>
                <Link :href="route('budgeting.index')" class="m3-rail__item" :class="{ 'm3-rail__item--active': isActive(['budgeting.*']) }" :aria-current="isActive(['budgeting.*']) ? 'page' : undefined">
                    <span class="m3-rail__indicator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </span>
                    <span v-if="isRailOpen" class="m3-rail__label">{{ $t('nav.budgeting') }}</span>
                </Link>
                <Link :href="route('analytics.index')" class="m3-rail__item" :class="{ 'm3-rail__item--active': isActive(['analytics.*']) }" :aria-current="isActive(['analytics.*']) ? 'page' : undefined">
                    <span class="m3-rail__indicator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.003 9.003 0 003.055 11H11V3.055zM20.945 13H13v7.945a9.003 9.003 0 007.945-7.945z" />
                        </svg>
                    </span>
                    <span v-if="isRailOpen" class="m3-rail__label">{{ $t('nav.analytics') }}</span>
                </Link>
                <Link :href="route('categories.index')" class="m3-rail__item" :class="{ 'm3-rail__item--active': isActive(['categories.*']) }" :aria-current="isActive(['categories.*']) ? 'page' : undefined">
                    <span class="m3-rail__indicator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                    </span>
                    <span v-if="isRailOpen" class="m3-rail__label">{{ $t('nav.label') }}</span>
                </Link>
                <Link :href="route('loans.index', { type: 'debt' })" class="m3-rail__item" :class="{ 'm3-rail__item--active': isActive(['loans.*']) }" :aria-current="isActive(['loans.*']) ? 'page' : undefined">
                    <span class="m3-rail__indicator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75" />
                        </svg>
                    </span>
                    <span v-if="isRailOpen" class="m3-rail__label">{{ $t('nav.loan') }}</span>
                </Link>
                <Link :href="route('settings.index')" class="m3-rail__item" :class="{ 'm3-rail__item--active': isActive(['settings.*']) }" :aria-current="isActive(['settings.*']) ? 'page' : undefined">
                    <span class="m3-rail__indicator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    <span v-if="isRailOpen" class="m3-rail__label">{{ $t('nav.settings') }}</span>
                </Link>
            </div>
            <button type="button" class="m3-rail__toggle" :aria-expanded="isRailOpen" :aria-label="$t('common.close')" @click="toggleRail">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
            </button>
        </nav>

        <!-- ── Content ── -->
        <div class="m3-content" :class="isDesktop && !hideNav ? (isRailOpen ? 'm3-content--rail-open' : 'm3-content--rail') : ''">
            <main
                id="main-content"
                tabindex="-1"
                :class="['m3-main', showSkeleton ? 'm3-main--loading' : '']"
                :inert="showSkeleton ? '' : false"
                :aria-busy="isLoading ? 'true' : 'false'"
            >
                <slot />
            </main>
            <Transition
                enter-active-class="m3-fade-enter-active"
                enter-from-class="m3-fade-enter-from"
                enter-to-class="m3-fade-enter-to"
                leave-active-class="m3-fade-leave-active"
                leave-from-class="m3-fade-leave-from"
                leave-to-class="m3-fade-leave-to"
            >
                <div v-if="showSkeleton" class="m3-skeleton" aria-hidden="true">
                    <component :is="activeSkeletonComponent" />
                </div>
            </Transition>
        </div>

        <!-- ── Chat bubble + side sheet (desktop, di luar halaman chat) ── -->
        <Transition name="m3-bubble-fade">
            <button
                v-if="showChatBubble && !chatSheetOpen"
                type="button"
                class="m3-chat-bubble"
                :aria-label="$t('nav.chat')"
                :title="$t('nav.chat')"
                @click="chatSheetOpen = true"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z" />
                </svg>
            </button>
        </Transition>

        <M3ChatSheet :show="chatSheetOpen && isDesktop" @close="chatSheetOpen = false" />

        <!-- ── Mobile navigation bar: 5 destinasi, aksi Catat sejajar dengan destinasi lain ── -->
        <nav v-if="!isDesktop && !hideNav" class="m3-navbar" :aria-label="$t('nav.mainNav')" role="navigation">
            <Link :href="route('dashboard')" class="m3-navbar__item" :class="{ 'm3-navbar__item--active': isActive(['dashboard']) }" :aria-current="isActive(['dashboard']) ? 'page' : undefined">
                <span class="m3-navbar__indicator">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                </span>
                <span class="m3-navbar__label">{{ $t('nav.home') }}</span>
            </Link>
            <Link :href="route('budgeting.index')" class="m3-navbar__item" :class="{ 'm3-navbar__item--active': isActive(['budgeting.*']) }" :aria-current="isActive(['budgeting.*']) ? 'page' : undefined">
                <span class="m3-navbar__indicator">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </span>
                <span class="m3-navbar__label">{{ $t('nav.budgeting') }}</span>
            </Link>
            <Link :href="route('transactions.create')" class="m3-navbar__item">
                <span class="m3-navbar__indicator">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </span>
                <span class="m3-navbar__label">{{ $t('nav.record') }}</span>
            </Link>
            <Link :href="route('analytics.index')" class="m3-navbar__item" :class="{ 'm3-navbar__item--active': isActive(['analytics.*']) }" :aria-current="isActive(['analytics.*']) ? 'page' : undefined">
                <span class="m3-navbar__indicator">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.003 9.003 0 003.055 11H11V3.055zM20.945 13H13v7.945a9.003 9.003 0 007.945-7.945z" />
                    </svg>
                </span>
                <span class="m3-navbar__label">{{ $t('nav.analytics') }}</span>
            </Link>
            <Link :href="route('more')" class="m3-navbar__item" :class="{ 'm3-navbar__item--active': isMoreActive }" :aria-current="isMoreActive ? 'page' : undefined">
                <span class="m3-navbar__indicator">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <circle cx="5" cy="12" r="1.9" />
                        <circle cx="12" cy="12" r="1.9" />
                        <circle cx="19" cy="12" r="1.9" />
                    </svg>
                </span>
                <span class="m3-navbar__label">{{ $t('nav.more') }}</span>
            </Link>
        </nav>
    </div>
</template>

<style scoped>
.m3-shell {
    min-height: 100vh;
    background: var(--m3-color-surface);
    color: var(--m3-color-on-surface);
    font-family: inherit;
}

/* Skip link — hanya tampil saat focus keyboard */
.m3-skip-link {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
}

.m3-skip-link:focus {
    position: fixed;
    top: var(--m3-space-4);
    left: var(--m3-space-4);
    z-index: 200;
    width: auto;
    height: auto;
    clip: auto;
    padding: var(--m3-space-2) var(--m3-space-4);
    border-radius: var(--m3-radius-sm);
    background: var(--m3-color-primary);
    color: var(--m3-color-on-primary);
    font-size: var(--m3-type-label-large-size);
    font-weight: var(--m3-type-emphasized-label-large-weight);
}

/* ── Top app bar (small, 4rem / 64dp) ── */
.m3-appbar {
    position: sticky;
    top: 0;
    z-index: 40;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-4);
    min-height: var(--m3-size-appbar);
    height: var(--m3-size-appbar);
    padding: var(--m3-space-2) var(--m3-space-4);
    background: var(--m3-color-surface-container);
    color: var(--m3-color-on-surface);
    transition:
        padding-left var(--m3-motion-default-spatial-duration) var(--m3-motion-default-spatial),
        padding-right var(--m3-motion-default-spatial-duration) var(--m3-motion-default-spatial);
}

.m3-appbar--rail {
    padding-left: var(--m3-size-rail-open);
}

.m3-appbar--rail-collapsed {
    padding-left: var(--m3-size-rail);
}

.m3-appbar__leading {
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
}

.m3-appbar__brand-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-md);
    transition: transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-appbar__brand-btn:hover {
    transform: scale(1.05);
}

.m3-appbar__brand-btn:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-appbar__brand-logo {
    width: var(--m3-size-logo);
    height: var(--m3-size-logo);
    object-fit: contain;
}

.m3-appbar__title-block {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
    flex: 1;
}

.m3-appbar__title {
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-title-large-weight);
    margin: 0;
}

.m3-appbar__subtitle {
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 14rem;
}

.m3-appbar__actions {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1);
}

.m3-appbar__icon-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    color: var(--m3-color-on-surface-variant);
    overflow: hidden;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-appbar__icon-btn::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-appbar__icon-btn:hover::after {
    opacity: 0.08;
}

.m3-appbar__icon-btn:active::after {
    opacity: 0.12;
}

.m3-appbar__icon-btn:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-appbar__icon-btn svg {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-appbar__avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    overflow: hidden;
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    text-decoration: none;
}

.m3-appbar__avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* ── Desktop navigation rail ── */
.m3-rail {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 50;
    display: none;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-3);
    width: var(--m3-size-rail);
    padding: var(--m3-space-4) 0;
    background: var(--m3-color-surface-container);
}

@media (min-width: 1024px) {
    .m3-rail {
        display: flex;
    }
}

.m3-rail--open {
    width: var(--m3-size-rail-open);
    align-items: stretch;
    padding-left: var(--m3-space-4);
    padding-right: var(--m3-space-4);
}

.m3-rail__items {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    width: 100%;
}

.m3-rail__item {
    position: relative;
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    padding: var(--m3-space-1) 0;
    border-radius: var(--m3-radius-full);
    color: var(--m3-color-on-surface-variant);
    text-decoration: none;
}

.m3-rail__item:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-rail__indicator {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-indicator-rail);
    height: var(--m3-size-indicator-height);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    overflow: visible;
}

.m3-rail__indicator::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    width: 0;
    height: 100%;
    background: var(--m3-color-secondary-container);
    border-radius: inherit;
    transform: translateX(-50%);
    transition: width var(--m3-motion-default-spatial-duration) var(--m3-motion-default-spatial);
}

.m3-rail__indicator svg {
    position: relative;
    z-index: 1;
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-rail__indicator::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-rail__item:hover .m3-rail__indicator::after {
    opacity: 0.08;
}

.m3-rail__item:active .m3-rail__indicator::after {
    opacity: 0.1;
}

.m3-rail__item--active .m3-rail__indicator::before {
    width: 100%;
}

.m3-rail__item--active .m3-rail__indicator::after {
    opacity: 0;
}

.m3-rail__item--active .m3-rail__indicator svg {
    color: var(--m3-color-on-secondary-container);
}

.m3-rail__label {
    font-size: var(--m3-type-label-large-size);
    font-weight: var(--m3-type-label-large-weight);
}

.m3-rail__item--active .m3-rail__label {
    color: var(--m3-color-on-surface);
    font-weight: var(--m3-type-emphasized-label-large-weight);
}

.m3-rail__toggle {
    position: relative;
    margin-top: auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border: none;
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    cursor: pointer;
    overflow: hidden;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-rail__toggle::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-rail__toggle:hover::after {
    opacity: 0.08;
}

.m3-rail__toggle:active::after {
    opacity: 0.12;
}

.m3-rail__toggle:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-rail__toggle svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

/* ── FAB (56dp, primary-container, 28dp shape) ── */
.m3-fab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-fab);
    height: var(--m3-size-fab);
    border-radius: var(--m3-radius-xl);
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
    box-shadow: var(--m3-elevation-3);
    text-decoration: none;
    transition: transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-fab:hover {
    box-shadow: var(--m3-elevation-4);
}

.m3-fab:active {
    transform: scale(0.94);
}

.m3-fab svg {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

/* ── Content ── */
.m3-content {
    position: relative;
    min-height: 100vh;
    padding-top: env(safe-area-inset-top, 0px);
    padding-bottom: calc(var(--m3-size-navbar) + var(--m3-space-6) + env(safe-area-inset-bottom, 0px));
}

@media (min-width: 1024px) {
    .m3-content {
        padding-bottom: var(--m3-space-8);
        /* Inset kanan agar konten tidak menempel tepi viewport pada
           1024–1392px (cap 72rem baru memberi margin di atasnya). */
        padding-right: var(--m3-space-4);
    }

    .m3-content--rail {
        padding-left: var(--m3-size-rail);
    }

    .m3-content--rail-open {
        padding-left: var(--m3-size-rail-open);
    }
}

.m3-main {
    transition: opacity var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-main--loading {
    opacity: 0;
}

.m3-skeleton {
    position: absolute;
    inset: 0;
    background: var(--m3-color-surface);
    overflow-y: auto;
    transition: left var(--m3-motion-default-spatial-duration) var(--m3-motion-default-spatial);
}

/* Desktop: batasi overlay ke area konten (kanan rail) agar centering
   skeleton identik dengan halaman final, mengikuti state rail. */
@media (min-width: 1024px) {
    .m3-content--rail-open .m3-skeleton {
        left: var(--m3-size-rail-open);
    }

    .m3-content--rail .m3-skeleton {
        left: var(--m3-size-rail);
    }

    /* Loading bar (fixed) mengikuti offset rail yang sama — tidak
       melintasi sidebar. Selector .m3-shell untuk specificity di atas
       .m3-wavy milik komponen. */
    .m3-shell .m3-shell__wavy--rail-open {
        left: var(--m3-size-rail-open);
    }

    .m3-shell .m3-shell__wavy--rail {
        left: var(--m3-size-rail);
    }
}

.m3-skeleton :deep(.m3-bud-skel) {
    width: 100%;
    max-width: var(--m3-size-content-max);
    margin: 0 auto;
    padding: var(--m3-space-4);
}

.m3-fade-enter-active {
    transition: opacity 150ms ease-out;
}

.m3-fade-enter-from,
.m3-fade-leave-to {
    opacity: 0;
}

.m3-fade-enter-to,
.m3-fade-leave-from {
    opacity: 1;
}

.m3-fade-leave-active {
    transition: opacity 200ms ease-in;
}

/* ── Chat bubble (desktop, zona istirahat F-pattern: kanan-bawah) ── */
.m3-chat-bubble {
    position: fixed;
    right: var(--m3-space-6);
    bottom: var(--m3-space-6);
    z-index: 40;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-fab);
    height: var(--m3-size-fab);
    border: none;
    border-radius: var(--m3-radius-xl);
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
    box-shadow: var(--m3-elevation-3);
    cursor: pointer;
    transition: box-shadow var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-chat-bubble:hover {
    box-shadow: var(--m3-elevation-4);
}

.m3-chat-bubble:active {
    transform: scale(0.94);
}

.m3-chat-bubble svg {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-bubble-fade-enter-active,
.m3-bubble-fade-leave-active {
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-bubble-fade-enter-from,
.m3-bubble-fade-leave-to {
    opacity: 0;
    transform: scale(0.9);
}

/* ── Mobile navigation bar — floating bar (model BottomNav Default, styling M3) ──
   Warna/indikator memakai token M3; geometri (floating margin, label selalu
   tampil) mengikuti varian "label always" M3. */
.m3-navbar {
    position: fixed;
    left: var(--m3-space-4);
    right: var(--m3-space-4);
    bottom: calc(var(--m3-space-3) + env(safe-area-inset-bottom, 0px));
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    height: var(--m3-size-navbar);
    max-width: var(--m3-size-navbar-max);
    margin: 0 auto;
    padding: var(--m3-space-1-5) var(--m3-space-2);
    background: color-mix(in srgb, var(--m3-color-surface-container) 95%, transparent);
    -webkit-backdrop-filter: blur(var(--m3-blur-glass));
    backdrop-filter: blur(var(--m3-blur-glass));
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-lg);
    box-shadow: var(--m3-elevation-3);
}

@media (min-width: 1024px) {
    .m3-navbar {
        display: none;
    }
}

.m3-navbar__item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    color: var(--m3-color-on-surface-variant);
    text-decoration: none;
    background: none;
    border: none;
    border-radius: var(--m3-radius-sm);
    min-width: 0;
    height: var(--m3-size-navbar-item);
    padding: 0 var(--m3-space-1);
}

.m3-navbar__item:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-navbar__indicator {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-indicator-nav);
    height: var(--m3-size-indicator-height);
    border-radius: var(--m3-radius-full);
    overflow: visible;
    transform: translateY(0) scale(1);
    transition:
        transform var(--m3-motion-default-spatial-duration) var(--m3-motion-default-spatial);
}

.m3-navbar__indicator::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    width: 0;
    height: 100%;
    background: var(--m3-color-secondary-container);
    border-radius: inherit;
    transform: translateX(-50%);
    transition: width var(--m3-motion-default-spatial-duration) var(--m3-motion-default-spatial);
}

.m3-navbar__indicator svg {
    position: relative;
    z-index: 1;
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-navbar__indicator::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-navbar__item:hover .m3-navbar__indicator::after {
    opacity: 0.08;
}

.m3-navbar__item:active .m3-navbar__indicator::after {
    opacity: 0.1;
}

/* Item aktif: pill indicator M3 muncul di belakang ikon (ikon tidak bergeser,
   karena label selalu tampil di bawahnya) */
.m3-navbar__item--active .m3-navbar__indicator {
    transform: scale(1.05);
}

.m3-navbar__item--active .m3-navbar__indicator::before {
    width: 100%;
}

.m3-navbar__item--active .m3-navbar__indicator::after {
    opacity: 0;
}

.m3-navbar__item--active .m3-navbar__indicator svg {
    color: var(--m3-color-on-secondary-container);
}

.m3-navbar__item--active {
    color: var(--m3-color-on-surface);
}

/* Label: selalu tampil (varian "label always" M3); saat aktif hanya
   warna & ketebalan yang berubah. Di layar sangat sempit (<360px)
   memakai skala label-small agar nama panjang tetap utuh. */
.m3-navbar__label {
    margin-top: var(--m3-space-1);
    white-space: nowrap;
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    color: inherit;
    transition: color var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-navbar__item--active .m3-navbar__label {
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    color: var(--m3-color-secondary);
}

@media (max-width: 359px) {
    .m3-navbar__label {
        font-size: var(--m3-type-label-small-size);
        letter-spacing: var(--m3-type-label-small-tracking);
    }

    .m3-navbar__item--active .m3-navbar__label {
        font-weight: var(--m3-type-emphasized-label-small-weight);
    }
}
</style>
