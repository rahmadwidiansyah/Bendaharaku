<script setup>
/**
 * HeaderActions.vue
 *
 * Kumpulan tombol aksi di bagian kanan GlobalHeader.
 * Urutan: NotificationButton → AIChatShortcut → ProfileAvatar
 *
 * Props:
 *   user            — Object user
 *   notifCount      — Jumlah notifikasi belum dibaca
 *   hasUnreadChat   — Ada pesan AI Chat belum dibaca
 *   isProfileOpen   — Apakah profile menu terbuka
 *
 * Emits:
 *   openNotif     — Klik notifikasi
 *   toggleProfile — Klik avatar (forward rect)
 */

import NotificationButton from '@/Components/Header/NotificationButton.vue'
import AIChatShortcut     from '@/Components/Header/AIChatShortcut.vue'
import ProfileAvatar      from '@/Components/Header/ProfileAvatar.vue'

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
    notifCount: {
        type: Number,
        default: 0,
    },
    hasUnreadChat: {
        type: Boolean,
        default: false,
    },
    isProfileOpen: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['openNotif', 'toggleProfile'])
</script>

<template>
    <div class="flex items-center gap-1.5 shrink-0" role="toolbar" :aria-label="$t('header.actions')">
        <!-- 🔔 Notifikasi -->
        <NotificationButton
            :count="notifCount"
            @click="emit('openNotif', $event)"
        />

        <!-- 💬 AI Chat shortcut -->
        <AIChatShortcut :has-unread="hasUnreadChat" />

        <!-- 👤 Avatar / Profile menu trigger -->
        <ProfileAvatar
            :user="user"
            :is-open="isProfileOpen"
            @toggle="emit('toggleProfile', $event)"
        />
    </div>
</template>
