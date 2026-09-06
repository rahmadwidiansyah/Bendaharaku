<script setup>
/**
 * HeaderActions.vue
 *
 * Kumpulan tombol aksi di bagian kanan GlobalHeader.
 * Urutan: [Search] [AI Chat] [Avatar]
 *
 * Search menggantikan Notifikasi.
 * Klik Search → membuka GlobalSearchOverlay.
 *
 * Props:
 *   user             — Object user
 *   hasUnreadChat    — Ada pesan baru di AI Chat
 *   isProfileOpen    — State menu profil
 *   showNotification — (reserved, unused) backward-compat
 *   showChat         — Tampilkan shortcut AI Chat
 *   showProfile      — Tampilkan avatar/profile button
 *
 * Emits:
 *   toggleProfile    — Klik avatar, forward DOMRect
 */

import { ref } from 'vue'
import AIChatShortcut      from '@/Components/Header/AIChatShortcut.vue'
import ProfileAvatar       from '@/Components/Header/ProfileAvatar.vue'
import GlobalSearchOverlay from '@/Components/Header/GlobalSearchOverlay.vue'

const props = defineProps({
    user:             { type: Object,  default: null  },
    notifCount:       { type: Number,  default: 0     },   // reserved
    hasUnreadChat:    { type: Boolean, default: false  },
    isProfileOpen:    { type: Boolean, default: false  },
    showNotification: { type: Boolean, default: true   },  // reserved
    showChat:         { type: Boolean, default: true   },
    showProfile:      { type: Boolean, default: true   },
})

const emit = defineEmits(['openNotif', 'toggleProfile'])

// ── Search overlay state ──────────────────────────────────────────
const showSearch = ref(false)
const openSearch = () => { showSearch.value = true }
const closeSearch = () => { showSearch.value = false }

// ── Keyboard shortcut: Ctrl+K / Cmd+K ────────────────────────────
import { onMounted, onUnmounted } from 'vue'

const handleGlobalKey = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault()
        showSearch.value = !showSearch.value
    }
}

onMounted(() => document.addEventListener('keydown', handleGlobalKey))
onUnmounted(() => document.removeEventListener('keydown', handleGlobalKey))
</script>

<template>
    <div
        class="flex items-center shrink-0"
        role="toolbar"
        :aria-label="$t('header.actions')"
    >
        <!-- 🔍 Search button (menggantikan notifikasi) -->
        <button
            type="button"
            class="relative flex items-center justify-center shrink-0 rounded-2xl
                   active:scale-90 transition-transform duration-150
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-brand)]
                   focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--color-surface-base)]"
            style="width: 44px; height: 44px;"
            aria-label="Cari (Ctrl+K)"
            @click="openSearch"
        >
            <span
                class="relative w-9 h-9 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center overflow-hidden transition-colors duration-150"
                :class="showSearch ? 'bg-purple-600/20' : ''"
            >
                <svg
                    class="w-[18px] h-[18px] relative z-10 transition-colors"
                    :class="showSearch ? 'text-purple-300' : 'text-gray-300'"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true"
                >
                    <circle cx="11" cy="11" r="8" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35" />
                </svg>
            </span>
        </button>

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

    <!-- 🔍 Global Search Overlay (Teleport ke body) -->
    <GlobalSearchOverlay
        :show="showSearch"
        @close="closeSearch"
    />
</template>
