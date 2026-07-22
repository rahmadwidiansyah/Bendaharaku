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
 * Props:
 *   fullWidth — Izinkan layout melebar ke desktop (sidebar mode)
 *   hideNav   — Sembunyikan header dan bottom nav (halaman fullscreen seperti create/edit)
 */

import BottomNav    from '@/Components/BottomNav.vue'
import GlobalHeader from '@/Components/Header/GlobalHeader.vue'
import Toast        from '@/Components/Toast.vue'
import DashboardSkeleton   from '@/Components/Skeleton/DashboardSkeleton.vue'
import TransactionSkeleton from '@/Components/Skeleton/TransactionSkeleton.vue'
import AssetSkeleton       from '@/Components/Skeleton/AssetSkeleton.vue'
import StatisticsSkeleton  from '@/Components/Skeleton/StatisticsSkeleton.vue'
import SettingsSkeleton    from '@/Components/Skeleton/SettingsSkeleton.vue'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import { usePageLoading }      from '@/Composables/usePageLoading'
import { computed, ref }       from 'vue'


const SKELETON_COMPONENTS = {
    DashboardSkeleton,
    TransactionSkeleton,
    AssetSkeleton,
    StatisticsSkeleton,
    SettingsSkeleton,
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

const computedFullWidth = computed(() =>
    isDesktopLayout.value ? props.fullWidth : false
)

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
    <div class="font-sans antialiased bg-black text-white selection:bg-purple-400 selection:text-black">
        <Toast />

        <!-- Skip-to-content: aksesibilitas keyboard & screen reader -->
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[200] focus:px-4 focus:py-2 focus:rounded-xl focus:bg-purple-600 focus:text-white focus:font-bold focus:text-sm focus:shadow-lg focus:outline-none"
        >
            Langsung ke konten
        </a>

        <div class="flex justify-center min-h-screen">
            <!-- Panel konten utama -->
            <div :class="[
                'w-full bg-gray-800 min-h-screen flex flex-col',
                'border-x border-white/5 relative',
                'shadow-2xl shadow-black',
                computedFullWidth ? 'max-w-md lg:max-w-full' : 'max-w-md',
                !effectiveHideNav && computedFullWidth && isSidebarOpen ? 'lg:pl-64 transition-[padding] duration-300' : (!effectiveHideNav && computedFullWidth ? 'lg:pl-20 transition-[padding] duration-300' : ''),
            ]">
                <!-- Global Top App Bar — sticky header dengan greeting, AI Chat & profil -->
                <GlobalHeader v-if="!effectiveHideNav" />

                <!--
                    Content area — dibuat `relative` agar skeleton overlay bisa `absolute inset-0`.
                    Ini adalah satu-satunya stacking context yang perlu kita kelola.
                -->
                <div
                    :class="[
                        'flex-1 relative overflow-hidden',
                        !effectiveHideNav
                            ? (computedFullWidth ? 'pb-28 lg:pb-8' : 'pb-28')
                            : 'pb-0',
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
                        :aria-hidden="showSkeleton"
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
                            class="absolute inset-0 bg-gray-800 overflow-y-auto"
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
    </div>
</template>
