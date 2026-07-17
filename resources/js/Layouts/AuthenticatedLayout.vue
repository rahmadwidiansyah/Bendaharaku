<script setup>
/**
 * AuthenticatedLayout.vue
 *
 * Layout utama untuk semua halaman yang memerlukan autentikasi.
 *
 * Perubahan dari versi sebelumnya:
 *   - Hapus @keyframes fadeInPage lokal — sudah ada animate-page-enter di app.css
 *   - Ganti hardcoded border-[#1e1e1e] dengan border-white/5 (menggunakan token)
 *   - Hapus duplikasi scrollbar CSS (::-webkit-scrollbar) — sudah di app.css
 *   - Gunakan animate-page-enter yang terdefinisi di app.css
 *
 * Props:
 *   fullWidth — Izinkan layout melebar ke desktop (sidebar mode)
 */

import BottomNav from '@/Components/BottomNav.vue'
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

        <div class="flex justify-center min-h-screen">
            <!-- Panel konten utama -->
            <div :class="[
                'w-full bg-gray-800 min-h-screen flex flex-col',
                'border-x border-white/5 relative overflow-x-hidden',
                'shadow-2xl shadow-black transition-all duration-300',
                computedFullWidth ? 'max-w-md lg:max-w-full' : 'max-w-md',
                computedFullWidth && isSidebarOpen ? 'lg:pl-64' : (computedFullWidth ? 'lg:pl-20' : ''),
            ]">
                <main
                    id="main-content"
                    :class="[
                        'flex-1 animate-page-enter',
                        computedFullWidth ? 'pb-24 lg:pb-8' : 'pb-24',
                    ]"
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
