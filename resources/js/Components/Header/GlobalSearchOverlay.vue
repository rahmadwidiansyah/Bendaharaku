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
import axios from 'axios'

const { t } = useI18n()

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['close'])

const goToSearchPage = () => {
    const q = query.value.trim()
    close()
    if (q) router.visit(route('search.page', { q }))
    else router.visit(route('search.page'))
}

const searchInput = ref(null)
const query = ref('')
const selectedIndex = ref(-1)
const searchResults = ref([])
const loading = ref(false)

// ── Static quick links ────────────────────────────────────────────
const quickLinks = [
    { label: 'Dashboard', route: 'dashboard', icon: 'home', description: 'Halaman utama' },
    { label: 'Dompet', route: 'wallets.index', icon: 'wallet', description: 'Kelola dompet' },
    { label: 'Transaksi', route: 'transactions.index', icon: 'receipt', description: 'Riwayat transaksi' },
    { label: 'Analytics', route: 'analytics.index', icon: 'chart', description: 'Laporan keuangan' },
    { label: 'AI Chat', route: 'chat.index', icon: 'chat', description: 'Chat dengan AI' },
    { label: 'Pengaturan', route: 'settings.index', icon: 'settings', description: 'Semua pengaturan' },
]

const suggestions = computed(() => {
    return query.value.trim() ? (searchResults.value.length ? searchResults.value : []) : quickLinks
})

// ── Debounced API search ───────────────────────────────────────────
let searchTimer = null

watch(query, (val) => {
    selectedIndex.value = -1
    if (!val.trim()) {
        searchResults.value = []
        loading.value = false
        return
    }
    loading.value = true
    clearTimeout(searchTimer)
    searchTimer = setTimeout(async () => {
        try {
            const res = await axios.get(route('search.global'), { params: { q: val.trim() } })
            searchResults.value = res.data.results || []
        } catch {
            searchResults.value = []
        } finally {
            loading.value = false
        }
    }, 250)
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
    close()
    // For transactions, go to search page instead of edit
    if (item.type === 'Transaksi' && query.value.trim()) {
        router.visit(route('search.page', { q: query.value.trim() }))
        return
    }
    try {
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
    folder: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
}

const getIconPath = (icon) => iconMap[icon] || iconMap.search

const typeColors = {
    Wallet: 'text-blue-400 bg-blue-500/10',
    Kategori: 'text-emerald-400 bg-emerald-500/10',
    Transaksi: 'text-purple-400 bg-purple-500/10',
}
const typeBadgeColors = {
    Wallet: 'text-blue-500',
    Kategori: 'text-emerald-500',
    Transaksi: 'text-purple-500',
}
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
                    class="absolute inset-0 bg-black/60 backdrop-blur-md"
                    @click="close"
                />

                <!-- Search panel -->
                <div class="relative z-10 w-full max-w-xl sm:max-w-2xl lg:max-w-3xl xl:max-w-4xl mx-auto mt-12 sm:mt-16 lg:mt-24 px-3 sm:px-4">
                    <!-- Input bar -->
                    <div
                        class="flex items-center gap-3 bg-[var(--color-surface-overlay)] border border-[var(--color-border-default)] rounded-2xl px-3 sm:px-4 py-2.5 sm:py-3 shadow-modal"
                    >
                        <!-- Search icon -->
                        <svg
                            class="w-5 h-5 text-[var(--color-text-muted)] shrink-0"
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
                            class="flex-1 bg-transparent text-[var(--color-text-primary)] text-sm sm:text-base placeholder:text-[var(--color-text-muted)] outline-none caret-[var(--color-brand)]"
                            autocomplete="off"
                            autocorrect="off"
                            spellcheck="false"
                        />

                        <!-- Clear button -->
                        <button
                            v-if="query"
                            type="button"
                            class="shrink-0 w-6 h-6 rounded-full bg-[var(--color-surface-muted)] flex items-center justify-center hover:bg-[var(--color-surface-subtle)] transition-colors"
                            :aria-label="t('search.clear')"
                            @click="query = ''"
                        >
                            <svg class="w-3 h-3 text-[var(--color-text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- ESC hint -->
                        <kbd class="hidden sm:flex items-center gap-1 shrink-0 px-2 py-0.5 rounded-lg bg-[var(--color-surface-muted)] text-[var(--color-text-muted)] text-2xs font-mono border border-[var(--color-border-default)]">
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
                            v-if="suggestions.length || query.trim()"
                            class="mt-2 bg-[var(--color-surface-overlay)] border border-[var(--color-border-default)] rounded-2xl overflow-hidden shadow-modal"
                        >
                            <!-- Section label -->
                            <div class="px-4 pt-3 pb-1">
                                <p class="text-2xs font-bold text-[var(--color-text-muted)] uppercase tracking-widest">
                                    {{ query.trim() ? (loading ? 'Mencari...' : searchResults.length + ' hasil') : t('search.shortcuts') }}
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
                                            ? 'bg-[var(--color-brand-subtle)]'
                                            : 'hover:bg-[var(--color-surface-muted)]/50'"
                                        @click="navigate(item)"
                                        @mouseenter="selectedIndex = idx"
                                    >
                                        <!-- Icon -->
                                        <span
                                            class="shrink-0 w-8 h-8 rounded-xl flex items-center justify-center transition-colors"
                                            :class="selectedIndex === idx ? 'bg-[var(--color-brand-subtle)]' : (typeColors[item.type] || 'bg-[var(--color-surface-muted)]')"
                                        >
                                            <svg
                                                class="w-4 h-4"
                                                :class="selectedIndex === idx ? 'text-[var(--color-brand)]' : (item.type ? (typeColors[item.type]?.split(' ')[0] || 'text-[var(--color-text-muted)]') : 'text-[var(--color-text-muted)]')"
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
                                            <p class="text-sm font-semibold text-[var(--color-text-primary)] truncate group-hover:text-[var(--color-text-primary)] transition-colors"
                                               :class="selectedIndex === idx ? 'text-[var(--color-text-primary)]' : ''">
                                                {{ item.label }}
                                            </p>
                                            <p v-if="item.description" class="text-2xs text-[var(--color-text-muted)] truncate mt-0.5">
                                                {{ item.description }}
                                            </p>
                                        </div>

                                        <!-- Type badge (only when searching) -->
                                        <span
                                            v-if="query.trim() && item.type"
                                            class="shrink-0 text-2xs font-semibold"
                                            :class="typeBadgeColors[item.type] || 'text-gray-600'"
                                        >
                                            {{ item.type }}
                                        </span>

                                        <!-- Arrow -->
                                        <svg
                                            class="shrink-0 w-4 h-4 text-[var(--color-text-muted)] group-hover:text-[var(--color-text-secondary)] transition-colors"
                                            :class="selectedIndex === idx ? 'text-[var(--color-brand)]' : ''"
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

                            <!-- Loading -->
                            <div v-if="query.trim() && loading" class="px-4 py-6 text-center">
                                <svg class="w-5 h-5 text-[var(--color-brand)] animate-spin mx-auto" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            </div>

                            <!-- No results -->
                            <div v-if="query.trim() && !loading && searchResults.length === 0" class="px-4 py-6 text-center">
                                <p class="text-sm text-[var(--color-text-muted)]">{{ t('search.noResults') }} "<span class="text-[var(--color-text-primary)]">{{ query }}</span>"</p>
                            </div>

                            <!-- Footer -->
                            <div v-if="query.trim()" class="px-4 py-3 border-t border-[var(--color-border-subtle)] flex justify-center">
                                <button @click="goToSearchPage"
                                    class="flex items-center gap-1.5 text-2xs text-[var(--color-brand)] hover:text-[var(--color-brand-hover)] font-bold uppercase tracking-widest transition-colors">
                                    Lihat semua hasil
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
