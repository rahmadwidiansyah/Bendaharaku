<script setup>
/**
 * AuthenticatedLayout.vue
 *
 * Layout utama untuk semua halaman yang memerlukan autentikasi.
 *
 * ── Arsitektur Global Skeleton Loading ──────────────────────────────
 *
 * Skeleton TIDAK menggunakan z-index tinggi untuk "menindih" konten lama.
 * Sebaliknya, kita kontrol KEDUANYA via opacity:
 *
 *   <content-wrapper>            ← container relatif
 *     <slot />                   ← konten aktif, opacity 0→1
 *     <skeleton-layer />         ← overlay, opacity 1→0
 *   </content-wrapper>
 *
 *   Saat loading  : slot opacity=0, skeleton opacity=1
 *   Saat selesai  : skeleton fade-out, slot fade-in
 *
 * Ini menjamin skeleton SELALU menutupi konten lama dari halaman manapun,
 * tanpa peduli z-index, stacking context, atau animasi internal page.
 *
 * ── Layout Width ────────────────────────────────────────────────────
 *
 * SEBELUM (bug): root panel terkunci `max-w-md mx-auto` (448px) saat
 * `fullWidth` false (default) → SELURUH aplikasi tampak seperti mobile
 * di desktop.
 *
 * SEKARANG: root panel SELALU `w-full`. Desktop memakai ruang layar
 * sepenuhnya. Lebar konten halaman dikendalikan oleh `PageContainer`
 * yang dipakai di masing-masing halaman (lihat Components/PageContainer.vue).
 *
 * Props:
 *   fullWidth — DIPERTAHANKAN UNTUK BACKWARD-COMPAT. Nilai apa pun sekarang
 *               menghasilkan root panel `w-full`. Dulu: false → max-w-md.
 *   hideNav   — Sembunyikan header dan bottom nav (halaman fullscreen seperti create/edit)
 *   containerSize — 'full' | 'fluid' | 'narrow' — mengatur max-width panel
 *               konten. Default 'fluid' (responsive, hingga 7xl di layar lebar).
 *
 * Breaking change:
 *   - Caller lama `:full-width="false"` yang mengharapkan kolom 448px
 *     sekarang mendapat panel full width. Ini perilaku yang diinginkan.
 */

import BottomNav    from '@/Components/BottomNav.vue'
import GlobalHeader from '@/Components/Header/GlobalHeader.vue'
import Toast        from '@/Components/Toast.vue'
import DashboardSkeleton   from '@/Components/Skeleton/DashboardSkeleton.vue'
import TransactionSkeleton from '@/Components/Skeleton/TransactionSkeleton.vue'
import AssetSkeleton       from '@/Components/Skeleton/AssetSkeleton.vue'
import StatisticsSkeleton  from '@/Components/Skeleton/StatisticsSkeleton.vue'
import SettingsSkeleton    from '@/Components/Skeleton/SettingsSkeleton.vue'
import BudgetingSkeleton   from '@/Components/Skeleton/BudgetingSkeleton.vue'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import { usePageLoading }      from '@/Composables/usePageLoading'
import { initTheme }           from '@/Composables/useTheme'
import { usePushNotifications } from '@/Composables/usePushNotifications'
import { usePage, router }             from '@inertiajs/vue3'
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { consumeStaleForRoute } from '@/utils/stale.js'


const SKELETON_COMPONENTS = {
    DashboardSkeleton,
    TransactionSkeleton,
    AssetSkeleton,
    StatisticsSkeleton,
    SettingsSkeleton,
    BudgetingSkeleton,
}

const props = defineProps({
    fullWidth: {
        type: Boolean,
        default: false,
    },
    hideNav: {
        type: Boolean,
        default: false,
    },
})

const isSidebarOpen = ref(true)
const { isDesktopLayout } = useLayoutPreference()
const { isLoading, currentSkeleton } = usePageLoading()

// ── Theme initialization ──────────────────────────────────────────
const page = usePage()
onMounted(() => {
    const userTheme = page.props.auth?.user?.theme ?? 'system'
    initTheme(userTheme)
})

// ── Push presence: sinyal aktif/away + registrasi service worker ──
const push = usePushNotifications()
onMounted(() => {
    push.setVapidKey(page.props.vapidPublicKey ?? null)
    push.startPresence()
    push.updateState()
})
onUnmounted(() => {
    push.stopPresence()
})

// ── Global stale handler — semua page auto-reload jika kembali via bfcache/hardware back ──
function currentRouteName() {
    try { return route().current() ?? '' } catch { return '' }
}
function handleGlobalPageshow(e) {
    const r = currentRouteName()
    if (e?.persisted) {
        // bfcache restore → selalu fresh (tanpa only, biar semua props update)
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
    // event dari chat di tab sama — jika user sudah di page yang stale, reload langsung
    // tapi jangan spam jika baru saja reload; consume akan hapus flag
    const r = currentRouteName()
    if (consumeStaleForRoute(r)) {
        router.reload({ preserveScroll: true, preserveState: false })
    }
}
function handleGlobalStorage(e) {
    if (!e.key?.startsWith('bendaharaku:stale')) return
    const r = currentRouteName()
    // storage event hanya fire di tab lain — cek flag sessionStorage (sudah set via markStale localStorage sync)
    // simple: jika ada flag untuk route ini, reload
    try {
        if (sessionStorage.getItem(e.key)) {
            if (consumeStaleForRoute(r)) router.reload({ preserveScroll: true, preserveState: false })
        } else if (e.newValue) {
            // tab lain set localStorage, tab ini belum punya sessionStorage — set lalu consume
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
    // initial check (jika navigate via Inertia visit normal tapi flag masih ada)
    // delay 100ms agar initial page render tidak double-fetch saat fresh visit dari chat
    // hanya reload jika benar-benar bfcache? kita cek flag tapi debounce
    setTimeout(() => {
        const r = currentRouteName()
        // jangan reload langsung setelah fresh visit — bfcache handler sudah cover
        // hanya reload jika flag ada dan page bukan chat
        if (r && !r.startsWith('chat.') && consumeStaleForRoute(r)) {
            // debounce: hanya jika bukan navigasi baru (< 2s setelah mount, Inertia sudah fresh)
            // kita tetap reload karena chat transaksi bisa belum masuk ke props initial
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

// Root panel selalu full width di desktop.
// Prop `fullWidth` lama diabaikan untuk width (backward-compat: tidak lagi
// menghasilkan mode max-w-md). Sidebar padding hanya aktif di layout desktop.
const isDesktop = computed(() => isDesktopLayout.value && !props.hideNav)

const rootPanelClasses = computed(() => [
    'w-full bg-[var(--color-surface-base)] min-h-screen flex flex-col',
    'border-x border-[var(--color-border-default)] relative',
    'shadow-2xl shadow-black/50',
    isDesktop.value
        ? (isSidebarOpen.value ? 'lg:pl-64' : 'lg:pl-20')
        : '',
    'transition-[padding] duration-300',
])

// Resolve skeleton component dinamis dari nama string
const activeSkeletonComponent = computed(() =>
    currentSkeleton.value ? (SKELETON_COMPONENTS[currentSkeleton.value] ?? null) : null
)

// Tampilkan skeleton overlay hanya jika:
// 1. Sedang loading
// 2. Ada skeleton yang cocok untuk halaman tujuan
// 3. Bukan halaman fullscreen (hideNav = false)
const effectiveHideNav = computed(() => props.hideNav)

const showSkeleton = computed(() =>
    isLoading.value && activeSkeletonComponent.value !== null && !effectiveHideNav.value
)
</script>

<template>
    <div class="font-sans antialiased bg-[var(--color-surface-overlay)] text-[var(--color-text-primary)] selection:bg-purple-400 selection:text-black">
        <Toast />

        <!-- Skip-to-content: aksesibilitas keyboard & screen reader -->
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[200] focus:px-4 focus:py-2 focus:rounded-xl focus:bg-[var(--color-brand)] focus:text-white focus:font-bold focus:text-sm focus:shadow-lg focus:outline-none"
        >
            Langsung ke konten
        </a>

        <!-- Root panel: selalu w-full. Desktop memakai seluruh ruang layar. -->
        <div :class="rootPanelClasses">
            <!-- Global Top App Bar — sticky header dengan greeting, AI Chat & profil -->
            <GlobalHeader v-if="!effectiveHideNav" />

            <!--
                Content area — dibuat `relative` agar skeleton overlay bisa `absolute inset-0`.
                Ini adalah satu-satunya stacking context yang perlu kita kelola.
            -->
            <div
                :class="[
                    'flex-1 relative overflow-hidden',
                    !effectiveHideNav ? 'pb-28 lg:pb-8' : 'pb-0',
                ]"
                :style="!effectiveHideNav ? 'padding-bottom: max(7rem, calc(3.5rem + env(safe-area-inset-bottom, 0px) + 1rem));' : ''"
            >
                <!--
                    ── Konten Halaman (slot) ──────────────────────────────────────
                    Saat loading  : opacity 0  (disembunyikan, tapi tidak di-unmount)
                    Saat selesai  : opacity 1, fade-in

                    Menggunakan `transition-opacity` bukan `animate-page-enter` karena:
                    - animate-page-enter hanya dijalankan sekali saat mount
                    - Kita butuh transisi yang reaktif terhadap state isLoading
                -->
                <main
                    id="main-content"
                    :class="[
                        'overflow-x-hidden transition-opacity',
                        showSkeleton
                            ? 'opacity-0 duration-100'
                            : 'opacity-100 duration-200',
                    ]"
                    tabindex="-1"
                    :inert="showSkeleton ? '' : false"
                >
                    <slot />
                </main>

                <!--
                    ── Skeleton Overlay ───────────────────────────────────────────
                    Posisi absolute inset-0, menutupi seluruh content area.
                    z-index tidak dibutuhkan karena konten lama sudah opacity-0.

                    Menggunakan <Transition> untuk:
                    - Saat skeleton muncul: fade in cepat (150ms) agar terasa responsif
                    - Saat skeleton selesai: fade out halus (200ms)
                -->
                <Transition
                    enter-active-class="transition-opacity duration-150 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-200 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="showSkeleton"
                        class="absolute inset-0 bg-[var(--color-surface-base)] overflow-y-auto"
                        aria-hidden="true"
                    >
                        <component :is="activeSkeletonComponent" />
                    </div>
                </Transition>
            </div>

            <BottomNav
                v-if="!effectiveHideNav"
                :is-sidebar-open="isSidebarOpen"
                @toggle="isSidebarOpen = $event"
            />
        </div>
    </div>
</template>