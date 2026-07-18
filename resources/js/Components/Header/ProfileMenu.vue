<script setup>
/**
 * ProfileMenu.vue
 *
 * Dropdown menu yang muncul saat avatar ditekan.
 * Di-teleport ke body untuk menghindari clipping stacking context.
 *
 * Props:
 *   show     — Boolean toggle untuk menampilkan menu
 *   user     — Object user (name, email)
 *   position — { top, right } posisi absolut menu
 *
 * Emits:
 *   close    — Sinyal menutup menu
 */

import { onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    user: {
        type: Object,
        default: null,
    },
    position: {
        type: Object,
        default: () => ({ top: 72, right: 16 }),
    },
})

const emit = defineEmits(['close'])

const close = () => emit('close')

const handleKeydown = (e) => {
    if (e.key === 'Escape') close()
}

onMounted(() => document.addEventListener('keydown', handleKeydown))
onUnmounted(() => document.removeEventListener('keydown', handleKeydown))

const logout = () => {
    close()
    router.post(route('logout'))
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-[9999]"
                @click.self="close"
            >
                <nav
                    role="menu"
                    :aria-label="`Menu akun ${user?.name ?? ''}`"
                    :style="{
                        top:   `${position.top}px`,
                        right: `${position.right}px`,
                    }"
                    class="absolute w-[calc(100vw-2rem)] max-w-[280px] overflow-hidden rounded-2xl border border-white/10 bg-surface-raised shadow-modal"
                >
                    <!-- User info header -->
                    <div class="px-4 py-3 border-b border-white/8 bg-white/3">
                        <p class="text-sm font-bold text-white truncate">{{ user?.name ?? '—' }}</p>
                        <p class="text-2xs text-gray-500 truncate mt-0.5">{{ user?.email ?? '—' }}</p>
                    </div>

                    <!-- Menu items -->
                    <div class="p-1.5 space-y-0.5" role="none">

                        <!-- Profil Saya -->
                        <Link
                            :href="route('profile.edit')"
                            role="menuitem"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-gray-300 transition-colors hover:bg-white/6 hover:text-white focus:outline-none focus-visible:ring-1 focus-visible:ring-purple-400 w-full"
                            @click="close"
                        >
                            <span class="w-8 h-8 rounded-xl bg-purple-600/20 flex items-center justify-center shrink-0" aria-hidden="true">
                                <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A9.967 9.967 0 0112 15c2.21 0 4.252.716 5.879 1.929M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                            <span>Edit Profil</span>
                        </Link>

                        <!-- Pengaturan -->
                        <Link
                            :href="route('settings.index')"
                            role="menuitem"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-gray-300 transition-colors hover:bg-white/6 hover:text-white focus:outline-none focus-visible:ring-1 focus-visible:ring-purple-400 w-full"
                            @click="close"
                        >
                            <span class="w-8 h-8 rounded-xl bg-blue-600/20 flex items-center justify-center shrink-0" aria-hidden="true">
                                <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                            <span>Pengaturan</span>
                        </Link>

                        <!-- AI Chat Settings -->
                        <Link
                            :href="route('settings.ai')"
                            role="menuitem"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-gray-300 transition-colors hover:bg-white/6 hover:text-white focus:outline-none focus-visible:ring-1 focus-visible:ring-purple-400 w-full"
                            @click="close"
                        >
                            <span class="w-8 h-8 rounded-xl bg-emerald-600/20 flex items-center justify-center shrink-0" aria-hidden="true">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                            </span>
                            <span>Pengaturan AI</span>
                        </Link>

                        <!-- Divider -->
                        <div class="h-px bg-white/8 my-1" role="separator" />

                        <!-- Keluar -->
                        <button
                            type="button"
                            role="menuitem"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-red-400 transition-colors hover:bg-red-500/10 hover:text-red-300 focus:outline-none focus-visible:ring-1 focus-visible:ring-red-400 w-full"
                            @click="logout"
                        >
                            <span class="w-8 h-8 rounded-xl bg-red-600/10 flex items-center justify-center shrink-0" aria-hidden="true">
                                <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </span>
                            <span>Keluar</span>
                        </button>
                    </div>
                </nav>
            </div>
        </Transition>
    </Teleport>
</template>
