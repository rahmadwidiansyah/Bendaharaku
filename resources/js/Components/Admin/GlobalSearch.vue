<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import {
    Activity,
    FileClock,
    Headphones,
    LayoutDashboard,
    Loader2,
    Search,
    UserRound,
    Users,
} from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
})

const emit = defineEmits(['update:open'])

const page = usePage()
const query = ref('')
const results = ref({})
const loading = ref(false)
const input = ref(null)
let timer = null
let requestId = 0

const permissions = computed(() => page.props.auth?.permissions ?? [])

const quickLinks = computed(() =>
    [
        { label: 'Dashboard', href: route('admin.dashboard'), icon: LayoutDashboard, permission: 'admin.dashboard.view' },
        { label: 'Users', href: route('admin.users.index'), icon: Users, permission: 'users.view' },
        { label: 'Support', href: route('admin.support.index'), icon: Headphones, permission: 'support.manage' },
        { label: 'AI Logs', href: route('admin.ai_logs.index'), icon: Activity, permission: 'ai.logs.view' },
        { label: 'Audit Logs', href: route('admin.audit_logs.index'), icon: FileClock, permission: 'audit_logs.view' },
    ].filter((item) => !item.permission || permissions.value.includes(item.permission)),
)

const flatResults = computed(() => {
    const flat = []

    for (const item of quickLinks.value) {
        const match = !query.value || item.label.toLowerCase().includes(query.value.toLowerCase())
        if (match) {
            flat.push({ type: 'page', label: item.label, meta: 'Halaman', href: item.href, icon: item.icon })
        }
    }

    for (const user of results.value.users ?? []) {
        flat.push({ type: 'user', label: user.name, meta: user.email, href: user.href, icon: UserRound })
    }

    for (const log of results.value.logs ?? []) {
        flat.push({ type: 'log', label: log.label, meta: log.meta, href: log.href, icon: FileClock })
    }

    return flat
})

const hasSearchResults = computed(
    () => (results.value.users?.length ?? 0) > 0 || (results.value.logs?.length ?? 0) > 0,
)

const close = () => emit('update:open', false)

const go = (href) => {
    close()
    router.visit(href)
}

const runSearch = (term) => {
    if (term.trim().length < 2) {
        results.value = {}
        loading.value = false
        return
    }

    const currentRequest = ++requestId
    loading.value = true

    axios
        .get(route('admin.search'), { params: { q: term.trim() } })
        .then((response) => {
            if (currentRequest === requestId) {
                results.value = response.data.groups ?? {}
            }
        })
        .catch(() => {
            if (currentRequest === requestId) {
                results.value = {}
            }
        })
        .finally(() => {
            if (currentRequest === requestId) {
                loading.value = false
            }
        })
}

watch(query, (value) => {
    clearTimeout(timer)
    timer = setTimeout(() => runSearch(value), 250)
})

watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen) {
            query.value = ''
            results.value = {}
            await nextTick()
            input.value?.focus()
        }
    },
)

const onKeydown = (event) => {
    if (event.key === 'Escape') close()
    if (event.key === 'Enter' && flatResults.value.length > 0) {
        event.preventDefault()
        go(flatResults.value[0].href)
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-[110] flex items-start justify-center bg-black/60 p-4 pt-[10vh] backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Pencarian global" @click.self="close">
                <div class="w-full max-w-xl overflow-hidden rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-high-rgb))] shadow-2xl" @keydown="onKeydown">
                    <div class="flex items-center gap-3 border-b border-[rgb(var(--m3-color-outline-variant-rgb))] px-4 py-3">
                        <Search class="h-5 w-5 shrink-0 text-[rgb(var(--m3-color-on-surface-variant-rgb))]" />
                        <input
                            ref="input"
                            v-model="query"
                            type="search"
                            placeholder="Cari user, log, atau halaman…"
                            class="w-full bg-transparent text-sm text-[rgb(var(--m3-color-on-surface-rgb))] placeholder:text-[rgb(var(--m3-color-on-surface-variant-rgb))] focus:outline-none"
                        />
                        <Loader2 v-if="loading" class="h-4 w-4 animate-spin text-[rgb(var(--m3-color-primary-rgb))]" />
                        <kbd class="hidden rounded border border-[rgb(var(--m3-color-outline-variant-rgb))] px-1.5 py-0.5 text-[10px] text-[rgb(var(--m3-color-on-surface-variant-rgb))] sm:inline">Esc</kbd>
                    </div>

                    <div class="max-h-[60vh] overflow-y-auto p-2">
                        <template v-if="flatResults.length > 0">
                            <button
                                v-for="item in flatResults"
                                :key="`${item.type}-${item.href}`"
                                type="button"
                                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition-colors hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]"
                                @click="go(item.href)"
                            >
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[rgb(var(--m3-color-surface-container-rgb))] text-[rgb(var(--m3-color-primary-rgb))]">
                                    <component :is="item.icon" class="h-4 w-4" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ item.label }}</span>
                                    <span class="block truncate text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ item.meta }}</span>
                                </span>
                            </button>
                        </template>

                        <div v-else class="px-4 py-10 text-center">
                            <p class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">
                                {{ query.length >= 2 && !loading ? 'Tidak ada hasil' : 'Mulai ketik untuk mencari' }}
                            </p>
                            <p class="mt-1 text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                                Minimal 2 karakter. Tekan Enter untuk membuka hasil pertama.
                            </p>
                        </div>
                    </div>

                    <div v-if="hasSearchResults && query.length >= 2" class="border-t border-[rgb(var(--m3-color-outline-variant-rgb))] px-4 py-2 text-[10px] uppercase tracking-wider text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                        {{ results.users?.length ?? 0 }} user · {{ results.logs?.length ?? 0 }} log
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
