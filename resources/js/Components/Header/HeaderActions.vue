<script setup>
/**
 * HeaderActions.vue
 *
 * Kumpulan tombol aksi di bagian kanan GlobalHeader.
 * Urutan: [Notifikasi] [AI Chat] [Avatar]
 *
 * Semua icon button: w-9 h-9 (36px), rounded-2xl, gap-1.5.
 * Vertikal center dijamin oleh parent flex items-center.
 *
 * Props:
 *   user             — Object user
 *   notifCount       — Jumlah notif belum dibaca
 *   hasUnreadChat    — Ada pesan baru di AI Chat
 *   isProfileOpen    — State menu profil (untuk ring aktif di avatar)
 *   showNotification — Tampilkan tombol notifikasi
 *   showChat         — Tampilkan shortcut AI Chat
 *   showProfile      — Tampilkan avatar/profile button
 *
 * Emits:
 *   openNotif       — Klik notifikasi
 *   toggleProfile   — Klik avatar, forward DOMRect
 */

import NotificationButton from '@/Components/Header/NotificationButton.vue'
import AIChatShortcut     from '@/Components/Header/AIChatShortcut.vue'
import ProfileAvatar      from '@/Components/Header/ProfileAvatar.vue'

const props = defineProps({
    user:             { type: Object,  default: null  },
    notifCount:       { type: Number,  default: 0     },
    hasUnreadChat:    { type: Boolean, default: false  },
    isProfileOpen:    { type: Boolean, default: false  },
    showNotification: { type: Boolean, default: true   },
    showChat:         { type: Boolean, default: true   },
    showProfile:      { type: Boolean, default: true   },
})

const emit = defineEmits(['openNotif', 'toggleProfile'])
</script>

<template>
    <!--
        items-center: vertikal center semua tombol sejajar.
        gap-0: touch target 44×44px sudah menyediakan cukup spacing visual.
        Tidak perlu gap tambahan — visual container tetap 36px, hanya padding yang extend.
    -->
    <div
        class="flex items-center shrink-0"
        role="toolbar"
        :aria-label="$t('header.actions')"
    >
        <!-- 🔔 Notifikasi -->
        <NotificationButton
            v-if="showNotification"
            :count="notifCount"
            @click="emit('openNotif', $event)"
        />

        <!-- 💬 AI Chat shortcut -->
        <AIChatShortcut
            v-if="showChat"
            :has-unread="hasUnreadChat"
        />

        <!-- 👤 Avatar / profile menu trigger -->
        <ProfileAvatar
            v-if="showProfile"
            :user="user"
            :is-open="isProfileOpen"
            @toggle="emit('toggleProfile', $event)"
        />
    </div>
</template>
