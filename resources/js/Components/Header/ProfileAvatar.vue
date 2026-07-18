<script setup>
/**
 * ProfileAvatar.vue
 *
 * Tombol avatar profil di pojok kanan header.
 * Ukuran 40px, foto profil asli dengan border tipis brand.
 * Fallback ke inisial nama jika tidak ada foto.
 *
 * Props:
 *   user     — Object user (name, avatar_url)
 *   isOpen   — Apakah menu sedang terbuka (untuk aria-expanded)
 *
 * Emits:
 *   toggle(rect) — Emit koordinat button untuk positioning menu
 */

import { computed } from 'vue'
import Avatar from '@/Components/Avatar.vue'

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
    isOpen: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['toggle'])

const avatarSrc = computed(() => {
    const avatar = props.user?.avatar_url ?? props.user?.avatar
    if (!avatar) return null
    if (avatar.startsWith('http://') || avatar.startsWith('https://')) return avatar
    return `/storage/${avatar}`
})

const handleClick = (event) => {
    const rect = event.currentTarget.getBoundingClientRect()
    emit('toggle', rect)
}
</script>

<template>
    <button
        type="button"
        :aria-expanded="isOpen"
        aria-haspopup="menu"
        :aria-label="`Menu akun ${user?.name ?? ''}`"
        class="relative shrink-0 rounded-full active:scale-90 transition-all duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900"
        :class="isOpen ? 'ring-2 ring-purple-400 ring-offset-2 ring-offset-gray-900' : ''"
        @click="handleClick"
    >
        <!-- Avatar 40px = Tailwind w-10 h-10 -->
        <Avatar
            v-if="user"
            :src="avatarSrc"
            :name="user.name ?? 'U'"
            size="sm"
            :ring="true"
        />

        <!-- Ghost placeholder saat user null (saat loading) -->
        <div
            v-else
            class="w-9 h-9 rounded-full bg-gray-800 border-2 border-purple-500/50 animate-pulse"
            aria-hidden="true"
        />
    </button>
</template>
