<script setup>
/**
 * Material/Layouts/AuthenticatedLayout.vue
 *
 * M3 Expressive shell — dipakai HANYA oleh halaman `Material/*`
 * (phase 1: Dashboard saja). Halaman lain tetap memakai layout Default.
 *
 * Struktur (per references/components/bars.md + navigation.md):
 * - Top app bar (small): surface-container, 4rem, judul + aksi chat/avatar
 * - Mobile: navigation bar 5rem, surface-container, active indicator pill
 * - Desktop: navigation rail (expanded 15rem / collapsed 5rem) + FAB
 * - FAB record: primary-container, 3.5rem, shape 28dp
 * - Skeleton overlay + stale/bfcache handlers: perilaku identik layout Default
 *
 * Semua warna memakai token `--m3-*` (aktif hanya di bawah `.ui-m3`).
 */
import Toast from '@/Components/Toast.vue'
import DashboardSkeleton from '@/Components/Skeleton/DashboardSkeleton.vue'
import TransactionSkeleton from '@/Components/Skeleton/TransactionSkeleton.vue'
import AssetSkeleton from '@/Components/Skeleton/AssetSkeleton.vue'
import StatisticsSkeleton from '@/Components/Skeleton/StatisticsSkeleton.vue'
import SettingsSkeleton from '@/Components/Skeleton/SettingsSkeleton.vue'
import BudgetingSkeleton from '@/Components/Skeleton/BudgetingSkeleton.vue'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import { usePageLoading } from '@/Composables/usePageLoading'
import { initTheme } from '@/Composables/useTheme'
import { syncUiThemeFromProps } from '@/Composables/useUiTheme.js'
import { usePushNotifications } from '@/Composables/usePushNotifications'
import { usePage, router, Link } from '@inertiajs/vue3'
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { consumeStaleForRoute } from '@/utils/stale.js'

const { t } = useI18n()

const SKELETON_COMPONENTS = {
    DashboardSkeleton,
    TransactionSkeleton,
    AssetSkeleton,
    StatisticsSkeleton,
    SettingsSkeleton,
    BudgetingSkeleton,
}

const props = defineProps({
    fullWidth: { type: Boolean, default: false },
    hideNav: { type: Boolean, default: false },
    title: { type: String, default: '' },
})

const isRailOpen = ref(true)
const { isDesktopLayout } = useLayoutPreference()
const { isLoading, currentSkeleton, pendingUrl } = usePageLoading()

// ── Theme init: verifikasi (kelas sudah dipasang pre-paint oleh Blade) ──
const page = usePage()
onMounted(() => {
    const userTheme = page.props.auth?.user?.theme ?? 'system'
    initTheme(userTheme)
    syncUiThemeFromProps(page.props)
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
        return false
    })
}

const userName = computed(() => page.props.auth?.user?.name ?? '')
const avatarUrl = computed(() => page.props.auth?.user?.avatar_url ?? '')

const isDesktop = computed(() => isDesktopLayout.value && !props.hideNav)

const activeSkeletonComponent = computed(() =>
    currentSkeleton.value ? (SKELETON_COMPONENTS[currentSkeleton.value] ?? null) : null,
)

const showSkeleton = computed(() =>
    isLoading.value && activeSkeletonComponent.value !== null && !props.hideNav,
)
</script>

<template>
    <div class="m3-shell">
        <Toast />

        <a href="#main-content" class="m3-skip-link">Langsung ke konten</a>

        <!-- ── Top app bar (small) ── -->
        <header v-if="!hideNav" class="m3-appbar">
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
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
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
            <button type="button" class="m3-rail__toggle" :aria-expanded="isRailOpen" :aria-label="$t('common.close')" @click="isRailOpen = !isRailOpen">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
            </button>
        </nav>

        <!-- ── Content ── -->
        <div class="m3-content" :class="isDesktop && !hideNav ? (isRailOpen ? 'm3-content--rail-open' : 'm3-content--rail') : ''">
            <main id="main-content" tabindex="-1" :class="['m3-main', showSkeleton ? 'm3-main--loading' : '']" :inert="showSkeleton ? '' : false">
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

        <!-- ── Mobile navigation bar ── -->
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </span>
                <span class="m3-navbar__label">{{ $t('nav.budgeting') }}</span>
            </Link>
            <div class="m3-navbar__fab-wrap">
                <Link :href="route('transactions.create')" class="m3-fab m3-fab--nav" :aria-label="$t('nav.record')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </Link>
                <span class="m3-navbar__label">{{ $t('nav.record') }}</span>
            </div>
            <Link :href="route('analytics.index')" class="m3-navbar__item" :class="{ 'm3-navbar__item--active': isActive(['analytics.*']) }" :aria-current="isActive(['analytics.*']) ? 'page' : undefined">
                <span class="m3-navbar__indicator">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.003 9.003 0 003.055 11H11V3.055zM20.945 13H13v7.945a9.003 9.003 0 007.945-7.945z" />
                    </svg>
                </span>
                <span class="m3-navbar__label">{{ $t('nav.analytics') }}</span>
            </Link>
            <Link :href="route('categories.index')" class="m3-navbar__item" :class="{ 'm3-navbar__item--active': isActive(['categories.*']) }" :aria-current="isActive(['categories.*']) ? 'page' : undefined">
                <span class="m3-navbar__indicator">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                </span>
                <span class="m3-navbar__label">{{ $t('nav.label') }}</span>
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
    top: 1rem;
    left: 1rem;
    z-index: 200;
    width: auto;
    height: auto;
    clip: auto;
    padding: 0.5rem 1rem;
    border-radius: var(--m3-radius-sm);
    background: var(--m3-color-primary);
    color: var(--m3-color-on-primary);
    font-size: var(--m3-type-label-large-size);
    font-weight: var(--m3-type-emphasized-label-large-weight);
}

/* ── Top app bar (small, 4rem) ── */
.m3-appbar {
    position: sticky;
    top: 0;
    z-index: 40;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    min-height: 4rem;
    padding: 0.5rem 1rem;
    background: var(--m3-color-surface-container);
    color: var(--m3-color-on-surface);
}

.m3-appbar__title {
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-title-large-weight);
    margin: 0;
}

.m3-appbar__subtitle {
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 12rem;
}

.m3-appbar__actions {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.m3-appbar__icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: var(--m3-radius-full);
    color: var(--m3-color-on-surface-variant);
}

.m3-appbar__icon-btn:hover {
    background: rgba(227, 226, 230, 0.08);
}

.m3-appbar__icon-btn svg {
    width: 1.5rem;
    height: 1.5rem;
}

.m3-appbar__avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
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
    gap: 0.75rem;
    width: 5rem;
    padding: 1rem 0;
    background: var(--m3-color-surface-container);
}

@media (min-width: 1024px) {
    .m3-rail {
        display: flex;
    }
}

.m3-rail--open {
    width: 15rem;
    align-items: stretch;
    padding-left: 1rem;
    padding-right: 1rem;
}

.m3-rail__items {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    width: 100%;
}

.m3-rail__item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.25rem 0;
    border-radius: var(--m3-radius-full);
    color: var(--m3-color-on-surface-variant);
    text-decoration: none;
}

.m3-rail__indicator {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3.5rem;
    height: 2rem;
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
}

.m3-rail__item--active .m3-rail__indicator {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-rail__indicator svg {
    width: 1.5rem;
    height: 1.5rem;
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
    margin-top: auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border: none;
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    cursor: pointer;
}

.m3-rail__toggle:hover {
    background: rgba(227, 226, 230, 0.08);
}

.m3-rail__toggle svg {
    width: 1.25rem;
    height: 1.25rem;
}

/* ── FAB (56dp, primary-container, 28dp shape) ── */
.m3-fab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3.5rem;
    height: 3.5rem;
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
    width: 1.5rem;
    height: 1.5rem;
}

.m3-fab--nav {
    margin-top: -1.75rem;
}

/* ── Content ── */
.m3-content {
    position: relative;
    min-height: 100vh;
    padding-bottom: calc(5rem + env(safe-area-inset-bottom, 0px));
}

@media (min-width: 1024px) {
    .m3-content {
        padding-bottom: 2rem;
    }

    .m3-content--rail {
        padding-left: 5rem;
    }

    .m3-content--rail-open {
        padding-left: 15rem;
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

/* ── Mobile navigation bar (80dp) ── */
.m3-navbar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    display: flex;
    align-items: flex-start;
    justify-content: space-around;
    min-height: 5rem;
    padding: 0.75rem 0.5rem calc(0.75rem + env(safe-area-inset-bottom, 0px));
    background: var(--m3-color-surface-container);
}

@media (min-width: 1024px) {
    .m3-navbar {
        display: none;
    }
}

.m3-navbar__item,
.m3-navbar__fab-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    flex: 1;
    color: var(--m3-color-on-surface-variant);
    text-decoration: none;
    background: none;
    border: none;
}

.m3-navbar__indicator {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 4rem;
    height: 2rem;
    border-radius: var(--m3-radius-full);
}

.m3-navbar__item--active .m3-navbar__indicator {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-navbar__item--active {
    color: var(--m3-color-on-surface);
}

.m3-navbar__indicator svg {
    width: 1.5rem;
    height: 1.5rem;
}

.m3-navbar__label {
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
}

.m3-navbar__item--active .m3-navbar__label {
    font-weight: var(--m3-type-emphasized-label-medium-weight);
}
</style>
