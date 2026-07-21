<script setup>
/**
 * GlobalSearchOverlay.vue
 *
 * Full-screen search overlay yang muncul saat icon search di-klik.
 * Menampilkan suggestion seperti YouTube: recent + filtered menu items.
 *
 * Props:
 *   show — Boolean toggle
 *
 * Emits:
 *   close — Sinyal menutup overlay
 */

import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import settingsMenuTree from '@/Pages/Settings/Config/settingsMenu'

const { t } = useI18n()

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['close'])

const searchInput = ref(null)
const query = ref('')
const selectedIndex = ref(-1)

// ── Flatten settings menu ─────────────────────────────────────────
const flatMenu = computed(() => {
    const out = []
    const walk = (node, catId = null) => {
        if (!node) return
        if (node.route) {
            out.push({
                id: node.id,
                catId: catId,
                label: node.label,
                description: node.description || '',
                route: node.route,
                icon: node.icon || null,
            })
        }
        if (node.submenu?.length) node.submenu.forEach((c) => walk(c, catId || node.id))
    }
    settingsMenuTree.forEach((c) => walk(c, c.id))
    return out
})

// ── Static quick links ────────────────────────────────────────────
const quickLinks = [
    { label: 'Dashboard', route: 'dashboard', icon: 'home', description: 'Halaman utama' },
    { label: 'Dompet', route: 'wallets.index', icon: 'wallet', description: 'Kelola dompet' },
    { label: 'Transaksi', route: 'transactions.index', icon: 'receipt', description: 'Riwayat transaksi' },
    { label: 'Analytics', route: 'analytics.index', icon: 'chart', description: 'Laporan keuangan' },
    { label: 'AI Chat', route: 'chat.index', icon: 'chat', description: 'Chat dengan AI' },
    { label: 'Pengaturan', route: 'settings.index', icon: 'settings', description: 'Semua pengaturan' },
]

const getTransLabel = (item) => {
    if (item.category === 'Navigasi') {
        const map = {
            'dashboard': 'nav.home',
            'wallets.index': 'nav.asset',
            'transactions.index': 'nav.record',
            'analytics.index': 'nav.analytics',
            'chat.index': 'nav.telegram',
            'settings.index': 'nav.settings',
        }
        return map[item.route] ? t(map[item.route]) : item.label
    } else {
        const key = `settings.${item.catId}.${item.id}.title`
        const val = t(key)
        return val !== key ? val : item.label
    }
}

const getTransDesc = (item) => {
    if (item.category === 'Navigasi') {
        const map = {
            'dashboard': 'nav.homeDesc',
            'wallets.index': 'nav.assetDesc',
            'transactions.index': 'nav.recordDesc',
            'analytics.index': 'nav.analyticsDesc',
            'chat.index': 'nav.chatDesc',
            'settings.index': 'nav.settingsDesc',
        }
        return map[item.route] && t(map[item.route]) !== map[item.route] ? t(map[item.route]) : item.description
    } else {
        const key = `settings.${item.catId}.${item.id}.description`
        const val = t(key)
        return val !== key ? val : item.description
    }
}

// ── Search results ────────────────────────────────────────────────
const searchResults = computed(() => {
    const q = query.value.trim().toLowerCase()
    if (!q) return []

    const allItems = [
        ...quickLinks.map((l) => ({ ...l, category: 'Navigasi' })),
        ...flatMenu.value.map((m) => ({ ...m, category: 'Pengaturan' })),
    ]

    return allItems.filter(
        (item) =>
            getTransLabel(item).toLowerCase().includes(q) ||
            getTransDesc(item).toLowerCase().includes(q)
    ).slice(0, 10)
})

// Suggestions when no query
const suggestions = computed(() => {
    return query.value.trim() ? searchResults.value : quickLinks
})

// ── Keyboard navigation ───────────────────────────────────────────
const handleKeydown = (e) => {
    if (e.key === 'Escape') {
        close()
        return
    }
    if (e.key === 'ArrowDown') {
        e.preventDefault()
        selectedIndex.value = Math.min(selectedIndex.value + 1, suggestions.value.length - 1)
    } else if (e.key === 'ArrowUp') {
        e.preventDefault()
        selectedIndex.value = Math.max(selectedIndex.value - 1, -1)
    } else if (e.key === 'Enter') {
        if (selectedIndex.value >= 0 && suggestions.value[selectedIndex.value]) {
            navigate(suggestions.value[selectedIndex.value])
        } else if (searchResults.value.length > 0) {
            navigate(searchResults.value[0])
        }
    }
}

// ── Navigate ──────────────────────────────────────────────────────
const navigate = (item) => {
    if (!item?.route) return
    try {
        close()
        router.visit(route(item.route))
    } catch {
        window.location.href = '/dashboard'
    }
}

// ── Open/close ────────────────────────────────────────────────────
const close = () => {
    query.value = ''
    selectedIndex.value = -1
    emit('close')
}

watch(() => props.show, async (val) => {
    if (val) {
        query.value = ''
        selectedIndex.value = -1
        await nextTick()
        searchInput.value?.focus()
    }
})

watch(query, () => {
    selectedIndex.value = -1
})

// ── Global keydown (Escape) ───────────────────────────────────────
onMounted(() => document.addEventListener('keydown', handleKeydown))
onUnmounted(() => document.removeEventListener('keydown', handleKeydown))

// ── Icon helper ───────────────────────────────────────────────────
const iconMap = {
    home: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    wallet: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    receipt: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    chart: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    chat: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    settings: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    search: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0',
}

const getIconPath = (icon) => iconMap[icon] || iconMap.search
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-[9998] flex flex-col"
                role="dialog"
                aria-modal="true"
                aria-label="Pencarian global"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-gray-950/80 backdrop-blur-md"
                    @click="close"
                />

                <!-- Search panel -->
                <div class="relative z-10 w-full max-w-2xl mx-auto mt-16 sm:mt-24 px-4">
                    <!-- Input bar -->
                    <div
                        class="flex items-center gap-3 bg-gray-900 border border-white/15 rounded-2xl px-4 py-3 shadow-2xl shadow-black/50"
                    >
                        <!-- Search icon -->
                        <svg
                            class="w-5 h-5 text-gray-400 shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <path d="M21 21l-4.35-4.35" />
                        </svg>

                        <input
                            ref="searchInput"
                            v-model="query"
                            type="search"
                            :placeholder="t('search.placeholder')"
                            class="flex-1 bg-transparent text-white text-base placeholder-gray-500 outline-none caret-purple-400"
                            autocomplete="off"
                            autocorrect="off"
                            spellcheck="false"
                        />

                        <!-- Clear button -->
                        <button
                            v-if="query"
                            type="button"
                            class="shrink-0 w-6 h-6 rounded-full bg-gray-700 flex items-center justify-center hover:bg-gray-600 transition-colors"
                            :aria-label="t('search.clear')"
                            @click="query = ''"
                        >
                            <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- ESC hint -->
                        <kbd class="hidden sm:flex items-center gap-1 shrink-0 px-2 py-0.5 rounded-lg bg-gray-800 text-gray-500 text-2xs font-mono border border-white/10">
                            ESC
                        </kbd>
                    </div>

                    <!-- Results / Suggestions -->
                    <Transition
                        enter-active-class="transition-all duration-150 ease-out"
                        enter-from-class="opacity-0 translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                    >
                        <div
                            v-if="suggestions.length"
                            class="mt-2 bg-gray-900 border border-white/10 rounded-2xl overflow-hidden shadow-2xl shadow-black/60"
                        >
                            <!-- Section label -->
                            <div class="px-4 pt-3 pb-1">
                                <p class="text-2xs font-bold text-gray-500 uppercase tracking-widest">
                                    {{ query.trim() ? t('search.results') : t('search.shortcuts') }}
                                </p>
                            </div>

                            <ul role="listbox" class="py-1 pb-2">
                                <li
                                    v-for="(item, idx) in suggestions"
                                    :key="item.id ?? item.route ?? idx"
                                    role="option"
                                    :aria-selected="selectedIndex === idx"
                                >
                                    <button
                                        type="button"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-left transition-colors group"
                                        :class="selectedIndex === idx
                                            ? 'bg-purple-600/20'
                                            : 'hover:bg-white/5'"
                                        @click="navigate(item)"
                                        @mouseenter="selectedIndex = idx"
                                    >
                                        <!-- Icon -->
                                        <span
                                            class="shrink-0 w-8 h-8 rounded-xl flex items-center justify-center transition-colors"
                                            :class="selectedIndex === idx ? 'bg-purple-600/30' : 'bg-white/5'"
                                        >
                                            <svg
                                                class="w-4 h-4 text-gray-400"
                                                :class="selectedIndex === idx ? 'text-purple-400' : ''"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                                aria-hidden="true"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" :d="getIconPath(item.icon)" />
                                            </svg>
                                        </span>

                                        <!-- Text -->
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-200 truncate group-hover:text-white transition-colors"
                                               :class="selectedIndex === idx ? 'text-white' : ''">
                                                {{ getTransLabel(item) }}
                                            </p>
                                            <p v-if="item.description" class="text-2xs text-gray-500 truncate mt-0.5">
                                                {{ getTransDesc(item) }}
                                            </p>
                                        </div>

                                        <!-- Category badge (only when searching) -->
                                        <span
                                            v-if="query.trim() && item.category"
                                            class="shrink-0 text-2xs text-gray-600 font-semibold"
                                        >
                                            {{ item.category === 'Navigasi' ? t('search.navigation') : t('search.settings') }}
                                        </span>

                                        <!-- Arrow -->
                                        <svg
                                            class="shrink-0 w-4 h-4 text-gray-600 group-hover:text-gray-400 transition-colors"
                                            :class="selectedIndex === idx ? 'text-purple-400' : ''"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            aria-hidden="true"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </li>
                            </ul>

                            <!-- No results -->
                            <div v-if="query.trim() && searchResults.length === 0" class="px-4 py-6 text-center">
                                <p class="text-sm text-gray-500">{{ t('search.noResults') }} "<span class="text-gray-300">{{ query }}</span>"</p>
                            </div>

                            <!-- Footer hint -->
                            <div class="px-4 py-2 border-t border-white/5 flex items-center gap-4">
                                <div class="flex items-center gap-1.5 text-gray-600 text-2xs">
                                    <kbd class="px-1.5 py-0.5 rounded bg-gray-800 border border-white/10 font-mono">↑↓</kbd>
                                    <span>{{ t('search.hints.navigate') }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-gray-600 text-2xs">
                                    <kbd class="px-1.5 py-0.5 rounded bg-gray-800 border border-white/10 font-mono">↵</kbd>
                                    <span>{{ t('search.hints.select') }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-gray-600 text-2xs ml-auto">
                                    <kbd class="px-1.5 py-0.5 rounded bg-gray-800 border border-white/10 font-mono">ESC</kbd>
                                    <span>{{ t('search.hints.close') }}</span>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
