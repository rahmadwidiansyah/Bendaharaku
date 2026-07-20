<script setup>
/**
 * ProfileAvatar.vue
 *
 * Tombol avatar di pojok kanan header.
 *
 * ── Area sentuh ─────────────────────────────────────────────────
 *   Touch target: 44×44px (standar HIG & Material Design)
 *   Visual avatar: 32px (w-8 h-8) agar ada breathing room
 *   Trik: padding extends touch area tanpa mengubah visual size
 *
 * ── Ripple effect ───────────────────────────────────────────────
 *   CSS ripple murni via ::after pseudo-element + animate-ripple.
 *   Tidak membutuhkan JS posisi — cukup expand dari center.
 *
 * ── Klik ────────────────────────────────────────────────────────
 *   Emit 'toggle' dengan DOMRect → parent buka ProfileMenu.
 *   ProfileMenu berisi: Edit Profil, Pengaturan, AI Settings, Keluar.
 *
 * Props:
 *   user   — Object user { name, avatar_url }
 *   isOpen — Menu sedang terbuka (tampilkan ring aktif)
 *
 * Emits:
 *   toggle(DOMRect) — Koordinat button untuk menu positioning
 */

import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Avatar from '@/Components/Avatar.vue'

const props = defineProps({
    user:   { type: Object,  default: null  },
    isOpen: { type: Boolean, default: false },
})

const emit = defineEmits(['toggle'])

const avatarSrc = computed(() => {
    const src = props.user?.avatar_url ?? props.user?.avatar
    if (!src) return null
    if (src.startsWith('http://') || src.startsWith('https://')) return src
    return `/storage/${src}`
})

// ── Ripple ────────────────────────────────────────────────────────
const isRippling = ref(false)
let rippleTimer = null

const handleClick = (event) => {
    // Trigger ripple
    isRippling.value = false
    clearTimeout(rippleTimer)
    // Micro-delay agar Vue re-render class sebelum re-add
    requestAnimationFrame(() => {
        isRippling.value = true
        rippleTimer = setTimeout(() => { isRippling.value = false }, 400)
    })

    // Redirect ke settings
    router.visit(route('settings.index'))
}
</script>

<template>
    <!--
        Wrapper: 44×44px touch target (standar iOS HIG & Material)
        Menggunakan padding trick: visual tetap 36px, touch area 44px
        `shrink-0` mencegah kompresi oleh flex parent.
    -->
    <button
        type="button"
        :aria-expanded="isOpen"
        aria-haspopup="menu"
        :aria-label="`Menu akun${user?.name ? ': ' + user.name : ''}`"
        class="relative shrink-0 flex items-center justify-center rounded-full
               focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400
               focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900
               transition-transform duration-150 active:scale-90"
        style="width: 44px; height: 44px;"
        @click="handleClick"
    >
        <!--
            Visual container: 36px, di-center di dalam touch target 44px.
            overflow-hidden mengurung ripple agar tidak keluar avatar.
            Ring aktif saat menu terbuka.
        -->
        <span
            class="relative w-9 h-9 rounded-full flex items-center justify-center overflow-hidden transition-all duration-150"
            :class="[
                isOpen
                    ? 'ring-2 ring-purple-400 ring-offset-2 ring-offset-gray-900'
                    : 'hover:ring-2 hover:ring-white/20 hover:ring-offset-1 hover:ring-offset-gray-900'
            ]"
        >
            <!-- Avatar -->
            <Avatar
                v-if="user"
                :src="avatarSrc"
                :name="user.name ?? 'U'"
                size="sm"
                :ring="false"
                class="w-8 h-8 relative z-10"
            />

            <!-- Placeholder saat user null (loading) -->
            <span
                v-else
                class="w-8 h-8 rounded-full bg-gray-800 border-2 border-purple-500/50 animate-pulse relative z-10"
                aria-hidden="true"
            />

            <!-- Ripple effect: expand dari center, fade out -->
            <span
                v-if="isRippling"
                class="absolute inset-0 rounded-full bg-white/20 animate-ripple pointer-events-none"
                aria-hidden="true"
            />
        </span>
    </button>
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
