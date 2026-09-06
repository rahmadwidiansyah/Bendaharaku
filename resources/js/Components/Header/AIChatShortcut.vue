<script setup>
/**
 * AIChatShortcut.vue
 *
 * Tombol shortcut AI Chat di Global Header.
 * Menjadi SATU-SATUNYA akses cepat ke AI Chat dari seluruh aplikasi.
 *
 * ── Touch target ────────────────────────────────────────────────
 *   44×44px outer (standar HIG & Material Design)
 *   Visual container: 36px (w-9 h-9)
 *
 * Props:
 *   hasUnread — Apakah ada pesan/respons baru yang belum dilihat user
 *
 * Behavior:
 *   - Klik → navigasi ke route('chat.index') via Inertia Link
 *   - Jika hasUnread → tampilkan pulsing dot badge
 */

import { computed, ref } from 'vue'
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

// Ripple
const isRippling = ref(false)
let rippleTimer = null

const handleClick = () => {
    isRippling.value = false
    clearTimeout(rippleTimer)
    requestAnimationFrame(() => {
        isRippling.value = true
        rippleTimer = setTimeout(() => { isRippling.value = false }, 400)
    })
}
</script>

<template>
    <!-- Touch target wrapper: 44×44px -->
    <Link
        :href="route('chat.index')"
        class="relative flex items-center justify-center shrink-0 rounded-2xl
               active:scale-90 transition-transform duration-150
               focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-brand)]
               focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--color-surface-base)]"
        style="width: 44px; height: 44px;"
        :aria-label="hasUnread ? 'AI Chat – ada pesan baru' : 'AI Chat'"
        :aria-current="isActive ? 'page' : undefined"
        @click="handleClick"
    >
        <!-- Visual container: 36px -->
        <span
            class="relative w-9 h-9 rounded-2xl flex items-center justify-center overflow-hidden transition-colors duration-150"
            :class="[
                isActive
                    ? 'bg-purple-600/30 text-purple-300'
                    : 'bg-white/5 hover:bg-white/10 text-gray-300 hover:text-white'
            ]"
        >
            <!-- Chat bubble icon -->
            <svg
                class="w-[18px] h-[18px] relative z-10"
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

            <!-- Ripple -->
            <span
                v-if="isRippling"
                class="absolute inset-0 rounded-2xl bg-white/15 animate-ripple pointer-events-none"
                aria-hidden="true"
            />
        </span>

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
                class="absolute top-1 right-1 w-3 h-3 rounded-full bg-purple-500 border-2 border-gray-900 z-20"
                aria-hidden="true"
            >
                <span class="absolute inset-0 rounded-full bg-purple-400 animate-ping opacity-75" />
            </span>
        </Transition>
    </Link>
</template>

<style scoped>
@keyframes ripple {
    0%   { transform: scale(0);   opacity: 0.5; }
    60%  { transform: scale(1.8); opacity: 0.2; }
    100% { transform: scale(2.5); opacity: 0;   }
}

.animate-ripple {
    animation: ripple 0.4s ease-out forwards;
    transform-origin: center;
}
</style>
