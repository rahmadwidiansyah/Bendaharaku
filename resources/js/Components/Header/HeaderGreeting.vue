<script setup>
/**
 * HeaderGreeting.vue
 *
 * Bagian kiri GlobalHeader.
 *
 * Dua mode tampilan:
 *   1. expanded  — "Good Morning / Rahmad"  (saat belum scroll)
 *   2. collapsed — "Dashboard / Bendaharaku" (saat scroll atau di halaman non-dashboard)
 *
 * Props:
 *   userName     — Nama user yang akan ditampilkan
 *   routeLabel   — Label halaman aktif (dari routeTitleMap)
 *   isCollapsed  — Toggle antara expanded & collapsed
 */

import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
    userName: {
        type: String,
        default: '',
    },
    routeLabel: {
        type: String,
        default: 'Bendaharaku',
    },
    isCollapsed: {
        type: Boolean,
        default: false,
    },
})

const { locale } = useI18n()

// ─── Greeting berdasarkan waktu lokal ─────────────────────────────
const greeting = computed(() => {
    const hour = new Date().getHours()
    const isId = locale.value === 'id'

    if (hour >= 0 && hour < 5) {
        return { short: isId ? 'Tengah Malam' : 'Good Night',   emoji: '🌙' }
    }
    if (hour < 11) {
        return { short: isId ? 'Selamat Pagi'  : 'Good Morning', emoji: '☀️' }
    }
    if (hour < 15) {
        return { short: isId ? 'Selamat Siang' : 'Good Afternoon', emoji: '🌤️' }
    }
    if (hour < 19) {
        return { short: isId ? 'Selamat Sore'  : 'Good Evening', emoji: '🌇' }
    }
    return             { short: isId ? 'Selamat Malam' : 'Good Night',  emoji: '🌙' }
})

const firstName = computed(() => {
    if (!props.userName) return ''
    return props.userName.split(' ')[0]
})
</script>

<template>
    <div class="flex-1 min-w-0 pr-2">
        <!-- Expanded mode: greeting + nama user -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
            mode="out-in"
        >
            <div v-if="!isCollapsed" key="expanded">
                <!-- Baris 1: greeting + emoji -->
                <div class="flex items-center gap-1.5">
                    <p class="text-2xs font-black uppercase tracking-[0.22em] text-purple-400 truncate leading-none">
                        {{ greeting.short }}
                    </p>
                    <span class="text-xs leading-none shrink-0" aria-hidden="true">{{ greeting.emoji }}</span>
                </div>

                <!-- Baris 2: nama user -->
                <h1 class="text-lg font-black text-white tracking-tight leading-tight truncate mt-0.5">
                    {{ firstName || 'Pengguna' }}
                </h1>
            </div>

            <!-- Collapsed mode: nama halaman + Bendaharaku -->
            <div v-else key="collapsed">
                <h1 class="text-base font-black text-white tracking-tight truncate leading-tight">
                    {{ routeLabel }}
                </h1>
                <p class="text-2xs text-gray-500 font-semibold uppercase tracking-widest mt-0.5 truncate">
                    Bendaharaku
                </p>
            </div>
        </Transition>
    </div>
</template>
