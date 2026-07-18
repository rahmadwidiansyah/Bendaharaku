<script setup>
/**
 * AuthenticatedLayout.vue
 *
 * Layout utama untuk semua halaman yang memerlukan autentikasi.
 *
 * Perubahan layout:
 *   - Mount MobileHeader — top bar mobile dengan judul + avatar
 *   - Tambah skip-to-content link untuk keyboard/screen reader navigation
 *   - Ganti pb-safe class string dengan CSS var env(safe-area-inset-bottom)
 *   - Konsistensi padding bottom: mobile pakai pb-28 (BottomNav ~56px + safe area + margin)
 *
 * Props:
 *   fullWidth — Izinkan layout melebar ke desktop (sidebar mode)
 */

import BottomNav from '@/Components/BottomNav.vue'
import MobileHeader from '@/Components/MobileHeader.vue'
import Toast from '@/Components/Toast.vue'
import DashboardSkeleton    from '@/Components/Skeleton/DashboardSkeleton.vue'
import TransactionSkeleton  from '@/Components/Skeleton/TransactionSkeleton.vue'
import AssetSkeleton        from '@/Components/Skeleton/AssetSkeleton.vue'
import StatisticsSkeleton   from '@/Components/Skeleton/StatisticsSkeleton.vue'
import SettingsSkeleton     from '@/Components/Skeleton/SettingsSkeleton.vue'
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
                computedFullWidth && isSidebarOpen ? 'lg:pl-64 transition-[padding] duration-300' : (computedFullWidth ? 'lg:pl-20 transition-[padding] duration-300' : ''),
            ]">
                <!-- Mobile top bar — hidden di desktop, hidden juga di halaman create/edit -->
                <MobileHeader v-if="!hideNav" />

                <main
                    id="main-content"
                    :class="[
                        'flex-1 animate-page-enter overflow-x-hidden relative',
                        !hideNav
                            ? (computedFullWidth ? 'pb-28 lg:pb-8' : 'pb-28')
                            : 'pb-0',
                    ]"
                    :style="!hideNav ? 'padding-bottom: max(7rem, calc(3.5rem + env(safe-area-inset-bottom, 0px) + 1rem));' : ''"
                    tabindex="-1"
                >
                    <!-- Skeleton overlay — muncul di atas konten saat navigasi -->
                    <Transition
                        enter-active-class="transition-opacity duration-150 ease-out"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition-opacity duration-250 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <div
                            v-if="isLoading && activeSkeletonComponent && !hideNav"
                            class="absolute inset-0 z-10 bg-gray-800 overflow-hidden"
                            aria-hidden="true"
                        >
                            <component :is="activeSkeletonComponent" />
                        </div>
                    </Transition>

                    <slot />
                </main>

                <BottomNav
                    v-if="!hideNav"
                    :is-sidebar-open="isSidebarOpen"
                    @toggle="isSidebarOpen = $event"
                />
            </div>
        </div>
    </div>
</template>
