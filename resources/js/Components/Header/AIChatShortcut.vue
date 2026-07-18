<script setup>
/**
 * AIChatShortcut.vue
 *
 * Tombol shortcut AI Chat di Global Header.
 * Menjadi SATU-SATUNYA akses cepat ke AI Chat dari seluruh aplikasi.
 *
 * Props:
 *   hasUnread — Apakah ada pesan/respons baru yang belum dilihat user
 *   isActive  — Apakah user sedang berada di halaman Chat (untuk visual state)
 *
 * Behavior:
 *   - Klik → navigasi ke route('chat.index') via Inertia Link
 *   - Jika hasUnread → tampilkan pulsing dot badge
 */

import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps({
    hasUnread: {
        type: Boolean,
        default: false,
    },
})

const page = usePage()

const isActive = computed(() => {
    try {
        return route().current('chat.*') || route().current('chat.index')
    } catch {
        return false
    }
})
</script>

<template>
    <Link
        :href="route('chat.index')"
        class="relative flex items-center justify-center w-9 h-9 rounded-2xl transition-all duration-150 active:scale-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900"
        :class="[
            isActive
                ? 'bg-purple-600/30 text-purple-300'
                : 'bg-white/5 hover:bg-white/10 text-gray-300 hover:text-white'
        ]"
        :aria-label="hasUnread ? 'AI Chat – ada pesan baru' : 'AI Chat'"
        :aria-current="isActive ? 'page' : undefined"
    >
        <!-- Chat bubble icon dengan sparkle efek -->
        <svg
            class="w-[18px] h-[18px]"
            :class="isActive ? 'text-purple-300' : 'text-gray-300'"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z"
            />
        </svg>

        <!-- Pulsing dot badge saat ada pesan baru -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 scale-50"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-50"
        >
            <span
                v-if="hasUnread && !isActive"
                class="absolute -top-0.5 -right-0.5 w-3 h-3 rounded-full bg-purple-500 border-2 border-gray-900"
                aria-hidden="true"
            >
                <!-- Pulsing ring -->
                <span class="absolute inset-0 rounded-full bg-purple-400 animate-ping opacity-75" />
            </span>
        </Transition>
    </Link>
</template>
