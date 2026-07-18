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
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import { computed, ref } from 'vue'

const props = defineProps({
    fullWidth: {
        type: Boolean,
        default: false,
    },
})

const isSidebarOpen = ref(true)
const { isDesktopLayout } = useLayoutPreference()

const computedFullWidth = computed(() =>
    isDesktopLayout.value ? props.fullWidth : false
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
                <!-- Mobile top bar — hidden di desktop -->
                <MobileHeader />

                <main
                    id="main-content"
                    :class="[
                        'flex-1 animate-page-enter overflow-x-hidden',
                        // Mobile: beri ruang untuk BottomNav (56px) + safe area + sedikit margin
                        // Desktop: cukup pb-8
                        computedFullWidth ? 'pb-28 lg:pb-8' : 'pb-28',
                    ]"
                    style="padding-bottom: max(7rem, calc(3.5rem + env(safe-area-inset-bottom, 0px) + 1rem));"
                    tabindex="-1"
                >
                    <slot />
                </main>

                <BottomNav
                    :is-sidebar-open="isSidebarOpen"
                    @toggle="isSidebarOpen = $event"
                />
            </div>
        </div>
    </div>
</template>
