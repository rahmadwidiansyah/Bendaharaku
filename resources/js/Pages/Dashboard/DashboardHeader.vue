<script setup>
/**
 * DashboardHeader.vue (DEPRECATED)
 *
 * ⚠️ KOMPONEN INI SUDAH TIDAK DIGUNAKAN LAGI
 *
 * Gunakan MobileHeader.vue di AuthenticatedLayout.vue untuk menampilkan
 * user greeting, nama, dan avatar dropdown menu.
 *
 * Alasan penghapusan:
 *   - Menghindari duplikasi informasi user di halaman
 *   - MobileHeader sudah menampilkan semua informasi ini dengan UI yang lebih baik
 *   - Header sebagai single source of truth untuk identitas user
 *
 * Jika Anda perlu menampilkan user greeting khusus di halaman tertentu,
 * gunakan MobileHeader sebagai basis dan extend sesuai kebutuhan.
 */

import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import Avatar from '@/Components/Avatar.vue'

const page = usePage()
const user = computed(() => page.props.auth.user)

const showProfileMenu = ref(false)
const profileMenuPosition = ref({ top: 76, right: 16 })

const greeting = computed(() => {
    const hour = new Date().getHours()
    if (hour < 12) return { text: 'Selamat Pagi',  emoji: '☀️' }
    if (hour < 15) return { text: 'Selamat Siang', emoji: '🌤️' }
    if (hour < 18) return { text: 'Selamat Sore',  emoji: '🌇' }
    return              { text: 'Selamat Malam',   emoji: '🌙' }
})

const avatarSrc = computed(() => {
    const avatar = user.value?.avatar
    if (!avatar) return null
    if (avatar.startsWith('http://') || avatar.startsWith('https://')) return avatar
    return `/storage/${avatar}`
})

const toggleProfileMenu = (event) => {
    if (showProfileMenu.value) {
        showProfileMenu.value = false
        return
    }
    const rect = event.currentTarget.getBoundingClientRect()
    profileMenuPosition.value = {
        top:   Math.min(rect.bottom + 12, window.innerHeight - 190),
        right: Math.max(16, window.innerWidth - rect.right),
    }
    showProfileMenu.value = true
}

const closeOnEscape = (e) => {
    if (e.key === 'Escape') showProfileMenu.value = false
}
</script>

<template>
    <header class="flex justify-between items-center mb-6 pt-4 animate-fade-in-up">
        <!-- Greeting + nama user -->
        <div>
            <p class="text-2xs text-purple-500 font-black uppercase tracking-[0.3em] mb-0.5 opacity-80">
                Hello
            </p>
            <h1 class="text-2xl font-black text-white tracking-tight leading-none">
                {{ user?.name }}
            </h1>
            <div class="flex items-center gap-2 mt-1">
                <p class="text-sm text-gray-400 font-bold uppercase tracking-widest">
                    {{ greeting.text }}
                </p>
                <span class="text-sm" aria-hidden="true">{{ greeting.emoji }}</span>
            </div>
        </div>

        <!-- Avatar + profile menu trigger -->
        <div class="relative">
            <button
                type="button"
                @click="toggleProfileMenu"
                :aria-expanded="showProfileMenu"
                aria-haspopup="menu"
                aria-label="Buka menu akun"
                class="relative block active:scale-90 transition-transform focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-800 rounded-full"
            >
                <Avatar
                    :src="avatarSrc"
                    :name="user?.name ?? 'U'"
                    size="lg"
                    :ring="true"
                />
            </button>
        </div>
    </header>

    <!-- Profile dropdown — Teleport ke body agar tidak terjebak stacking context -->
    <Teleport to="body">
        <div
            v-if="showProfileMenu"
            class="fixed inset-0 z-[9999]"
            @click.self="showProfileMenu = false"
            @keydown="closeOnEscape"
        >
            <div
                role="menu"
                :style="{ top: `${profileMenuPosition.top}px`, right: `${profileMenuPosition.right}px` }"
                class="absolute w-[calc(100vw-2rem)] max-w-72 overflow-hidden rounded-xl border border-white/10 bg-gradient-to-br from-gray-900 to-gray-800 p-1.5 shadow-2xl shadow-black/70 animate-pop-in sm:w-64"
            >
                <!-- User info -->
                <div class="px-3 py-2.5 border-b border-white/10 mb-1">
                    <p class="text-sm font-bold text-white truncate">{{ user?.name }}</p>
                    <p class="text-2xs text-gray-500 truncate">{{ user?.email }}</p>
                </div>

                <!-- Menu items -->
                <Link
                    :href="route('settings.account.profile')"
                    role="menuitem"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white focus:outline-none focus-visible:ring-1 focus-visible:ring-purple-400"
                    @click="showProfileMenu = false"
                >
                    <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A9.967 9.967 0 0112 15c2.21 0 4.252.716 5.879 1.929M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Profil Saya
                </Link>

                <Link
                    :href="route('settings.index')"
                    role="menuitem"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white focus:outline-none focus-visible:ring-1 focus-visible:ring-purple-400"
                    @click="showProfileMenu = false"
                >
                    <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Pengaturan
                </Link>
            </div>
        </div>
    </Teleport>
</template>
