<script setup>
/**
 * ProfileMenu.vue
 *
 * Bottom-sheet style menu yang muncul saat avatar ditekan.
 * Di-teleport ke body untuk menghindari clipping stacking context.
 *
 * Pada mobile: muncul sebagai card dari atas-kanan (dropdown).
 * Posisi dihitung dari rect button avatar yang dikirim via prop.
 *
 * Props:
 *   show     — Boolean toggle untuk menampilkan menu
 *   user     — Object user (name, email)
 *   position — { top, right } posisi absolut menu (dari rect avatar)
 *
 * Emits:
 *   close    — Sinyal menutup menu
 */

import { onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

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
            enter-from-class="opacity-0 scale-95 -translate-y-2"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-2"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-[9999]"
                @click.self="close"
                role="presentation"
            >
                <!--
                    Menu card — `transform-origin: top right` agar animasi scale
                    muncul dari sudut avatar (pojok kanan atas).
                -->
                <nav
                    role="menu"
                    :aria-label="`${t('nav.profile')} ${user?.name ?? ''}`"
                    :style="{
                        top:             `${position.top}px`,
                        right:           `${position.right}px`,
                        transformOrigin: 'top right',
                    }"
                    class="absolute w-[calc(100vw-2rem)] max-w-[280px] overflow-hidden rounded-2xl border border-[var(--color-border-default)] bg-[var(--color-surface-overlay)] shadow-modal"
                >
                    <!-- User info header -->
                    <div class="px-4 py-3 sm:py-3.5 border-b border-[var(--color-border-subtle)] bg-[var(--color-surface-muted)]/30">
                        <p class="text-sm font-bold text-[var(--color-text-primary)] truncate leading-tight">
                            {{ user?.name ?? '—' }}
                        </p>
                        <p class="text-2xs text-[var(--color-text-muted)] truncate mt-0.5">
                            {{ user?.email ?? '—' }}
                        </p>
                    </div>

                    <!-- Menu items -->
                    <div class="p-1.5 space-y-0.5" role="none">

                        <!-- Profile -->
                        <Link
                            :href="route('settings.account.profile')"
                            role="menuitem"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-[var(--color-text-secondary)] transition-colors hover:bg-[var(--color-surface-muted)] hover:text-[var(--color-text-primary)] focus:outline-none focus-visible:ring-1 focus-visible:ring-[var(--color-brand)] w-full"
                            @click="close"
                        >
                            <span class="w-8 h-8 rounded-xl bg-[var(--color-brand-subtle)] flex items-center justify-center shrink-0" aria-hidden="true">
                                <svg class="w-4 h-4 text-[var(--color-brand)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A9.967 9.967 0 0112 15c2.21 0 4.252.716 5.879 1.929M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                            <span>{{ t('settings.account.profile.title') }}</span>
                        </Link>

                        <!-- Settings -->
                        <Link
                            :href="route('settings.index')"
                            role="menuitem"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-[var(--color-text-secondary)] transition-colors hover:bg-[var(--color-surface-muted)] hover:text-[var(--color-text-primary)] focus:outline-none focus-visible:ring-1 focus-visible:ring-[var(--color-brand)] w-full"
                            @click="close"
                        >
                            <span class="w-8 h-8 rounded-xl bg-[var(--color-transfer-bg)] flex items-center justify-center shrink-0" aria-hidden="true">
                                <svg class="w-4 h-4 text-[var(--color-transfer-text)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                            <span>{{ t('nav.settings') }}</span>
                        </Link>

                        <!-- Help -->
                        <Link
                            href="/help"
                            role="menuitem"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-gray-300 transition-colors hover:bg-white/6 hover:text-white focus:outline-none focus-visible:ring-1 focus-visible:ring-[var(--color-brand)] w-full"
                            @click="close"
                        >
                            <span class="w-8 h-8 rounded-xl bg-[var(--color-income-bg)] flex items-center justify-center shrink-0" aria-hidden="true">
                                <svg class="w-4 h-4 text-[var(--color-income-text)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <span>{{ t('nav.help') }}</span>
                        </Link>

                        <!-- Divider -->
                        <div class="h-px bg-[var(--color-border-subtle)] my-1" role="separator" aria-hidden="true" />

                        <!-- Logout -->
                        <button
                            type="button"
                            role="menuitem"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-[var(--color-expense-text)] transition-colors hover:bg-[var(--color-expense-bg)] hover:text-[var(--color-expense-text)] focus:outline-none focus-visible:ring-1 focus-visible:ring-[var(--color-expense-text)] w-full"
                            @click="logout"
                        >
                            <span class="w-8 h-8 rounded-xl bg-[var(--color-expense-bg)] flex items-center justify-center shrink-0" aria-hidden="true">
                                <svg class="w-4 h-4 text-[var(--color-expense-text)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </span>
                            <span>{{ t('profile.logout') }}</span>
                        </button>
                    </div>
                </nav>
            </div>
        </Transition>
    </Teleport>
</template>
