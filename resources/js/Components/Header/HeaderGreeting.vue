<script setup>
/**
 * HeaderGreeting.vue
 *
 * Bagian kiri GlobalHeader.
 *
 * ── Dua mode konten ──────────────────────────────────────────────
 *
 *   isDashboard = true  →  expanded: greeting + nama LENGKAP
 *                          collapsed: "Dashboard / Bendaharaku"
 *
 *   isDashboard = false →  expanded: judul halaman + "Bendaharaku"
 *                          collapsed: judul halaman (lebih kecil)
 *
 * ── Props ────────────────────────────────────────────────────────
 *   userName     — Nama lengkap user (bukan firstName)
 *   routeLabel   — Label halaman aktif
 *   isDashboard  — Apakah halaman aktif adalah Dashboard
 *   isCollapsed  — Scroll state: true = header mengecil
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
    isDashboard: {
        type: Boolean,
        default: false,
    },
    isCollapsed: {
        type: Boolean,
        default: false,
    },
})

const { locale } = useI18n()

// ─── Greeting berdasarkan jam lokal ──────────────────────────────
const greeting = computed(() => {
    const hour = new Date().getHours()
    const isId = locale.value !== 'en'

    if (hour >= 0 && hour < 5)  return { text: isId ? 'Tengah Malam' : 'Good Night',     emoji: '🌙' }
    if (hour < 11)              return { text: isId ? 'Selamat Pagi'  : 'Good Morning',   emoji: '☀️' }
    if (hour < 15)              return { text: isId ? 'Selamat Siang' : 'Good Afternoon', emoji: '🌤️' }
    if (hour < 19)              return { text: isId ? 'Selamat Sore'  : 'Good Evening',   emoji: '🌇' }
    return                             { text: isId ? 'Selamat Malam' : 'Good Night',     emoji: '🌙' }
})

// Nama lengkap — fallback ke 'Pengguna' jika kosong
const displayName = computed(() =>
    props.userName?.trim() || 'Pengguna'
)
</script>

<template>
    <div class="flex-1 min-w-0 pr-2">
        <Transition
            enter-active-class="transition-all duration-250 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
            mode="out-in"
        >
            <!--
                ── DASHBOARD EXPANDED ────────────────────────────────
                Greeting waktu + nama lengkap user
            -->
            <div v-if="isDashboard && !isCollapsed" key="dashboard-expanded">
                <div class="flex items-center gap-1">
                    <p class="text-2xs font-black uppercase tracking-[0.2em] text-purple-400 truncate leading-tight">
                        {{ greeting.text }}
                    </p>
                    <span class="text-xs leading-none shrink-0" aria-hidden="true">{{ greeting.emoji }}</span>
                </div>
                <h1 class="text-sm font-black text-white tracking-tight truncate leading-tight">
                    {{ displayName }}
                </h1>
            </div>

            <!--
                ── DASHBOARD COLLAPSED ───────────────────────────────
                Label "Dashboard" + subtitle "Bendaharaku"
            -->
            <div v-else-if="isDashboard && isCollapsed" key="dashboard-collapsed">
                <h1 class="text-sm font-black text-white tracking-tight truncate leading-tight">
                    {{ routeLabel }}
                </h1>
                <p class="text-2xs text-gray-500 font-semibold uppercase tracking-widest leading-tight truncate">
                    Bendaharaku
                </p>
            </div>

            <!--
                ── HALAMAN LAIN ──────────────────────────────────────
                Langsung tampilkan judul halaman + "Bendaharaku"
                Tidak ada greeting, tidak peduli collapsed atau tidak.
            -->
            <div v-else key="page-title">
                <h1 class="text-sm font-black text-white tracking-tight truncate leading-tight">
                    {{ routeLabel }}
                </h1>
                <p class="text-2xs text-gray-500 font-semibold uppercase tracking-widest leading-tight truncate">
                    Bendaharaku
                </p>
            </div>
        </Transition>
    </div>
</template>
