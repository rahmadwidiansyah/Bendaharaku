<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import {
    Activity,
    AlertCircle,
    CheckCircle2,
    FileClock,
    Headphones,
    LayoutDashboard,
    LogOut,
    Menu,
    Moon,
    Search,
    Sun,
    Users,
    X,
} from 'lucide-vue-next'
import GlobalSearch from '@/Components/Admin/GlobalSearch.vue'
import { initTheme, setTheme } from '@/Composables/useTheme'

const page = usePage()
const sidebarOpen = ref(false)
const searchOpen = ref(false)
const lightMode = ref(false)

const user = computed(() => page.props.auth?.user)
const permissions = computed(() => page.props.auth?.permissions ?? [])
const roles = computed(() => page.props.auth?.roles ?? [])

const can = (permission) => !permission || permissions.value.includes(permission)

const menuItems = computed(() =>
    [
        { label: 'Dashboard', href: route('admin.dashboard'), icon: LayoutDashboard, permission: 'admin.dashboard.view', match: ['admin.dashboard'] },
        { label: 'Users', href: route('admin.users.index'), icon: Users, permission: 'users.view', match: ['admin.users.*'] },
        { label: 'Support', href: route('admin.support.index'), icon: Headphones, permission: 'support.manage', match: ['admin.support.*'] },
        { label: 'AI Logs', href: route('admin.ai_logs.index'), icon: Activity, permission: 'ai.logs.view', match: ['admin.ai_logs.*'] },
        { label: 'Audit Logs', href: route('admin.audit_logs.index'), icon: FileClock, permission: 'audit_logs.view', match: ['admin.audit_logs.*'] },
    ].filter((item) => can(item.permission)),
)

const pageTitle = computed(() => {
    const current = route().current() || ''

    if (current.startsWith('admin.users.show')) return 'Detail User'
    if (current.startsWith('admin.users')) return 'User Management'
    if (current.startsWith('admin.support')) return 'Support Tools'
    if (current.startsWith('admin.ai_logs.show')) return 'Detail AI Log'
    if (current.startsWith('admin.ai_logs')) return 'AI Logs'
    if (current.startsWith('admin.audit_logs.show')) return 'Detail Audit Log'
    if (current.startsWith('admin.audit_logs')) return 'Audit Logs'

    return 'Dashboard'
})

const isActive = (item) => item.match.some((pattern) => route().current(pattern))

const toast = ref(null)
let toastTimer = null

const showToast = (type, message) => {
    if (!message) return
    toast.value = { type, message }
    clearTimeout(toastTimer)
    toastTimer = setTimeout(() => {
        toast.value = null
    }, 4500)
}

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) showToast('success', flash.success)
        if (flash?.error) showToast('error', flash.error)
        if (flash?.message && !flash.success && !flash.error) showToast('success', flash.message)
    },
    { immediate: true, deep: true },
)

const toggleTheme = () => {
    const previous = lightMode.value ? 'light' : 'dark'
    const next = lightMode.value ? 'dark' : 'light'

    lightMode.value = next === 'light'
    setTheme(next)

    router.post(
        route('admin.preferences.theme'),
        { theme: next },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                lightMode.value = previous === 'light'
                setTheme(previous)
            },
        },
    )
}

const logout = () => router.post(route('logout'))

const onKeydown = (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault()
        searchOpen.value = true
    }
}

onMounted(() => {
    initTheme(user.value?.theme || 'system')
    lightMode.value = document.documentElement.classList.contains('light')
    document.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown)
    clearTimeout(toastTimer)
})
</script>

<template>
    <div class="flex h-screen bg-[rgb(var(--m3-color-surface-rgb))]">
        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] transition-transform duration-300 lg:relative lg:translate-x-0',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <div class="flex items-center justify-between border-b border-[rgb(var(--m3-color-outline-variant-rgb))] px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[rgb(var(--m3-color-primary-rgb))] text-sm font-black text-[rgb(var(--m3-color-on-primary-rgb))]">B</span>
                    <div>
                        <p class="text-sm font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">Bendaharaku</p>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Admin Panel</p>
                    </div>
                </div>
                <button type="button" class="rounded-lg p-1.5 text-[rgb(var(--m3-color-on-surface-variant-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] lg:hidden" aria-label="Tutup menu" @click="sidebarOpen = false">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.18em] text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Menu</p>
                <Link
                    v-for="item in menuItems"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors',
                        isActive(item)
                            ? 'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]'
                            : 'text-[rgb(var(--m3-color-on-surface-variant-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] hover:text-[rgb(var(--m3-color-on-surface-rgb))]',
                    ]"
                    @click="sidebarOpen = false"
                >
                    <component :is="item.icon" class="h-5 w-5 shrink-0" />
                    {{ item.label }}
                </Link>
            </nav>

            <div class="space-y-3 border-t border-[rgb(var(--m3-color-outline-variant-rgb))] px-4 py-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[rgb(var(--m3-color-secondary-container-rgb))] text-sm font-bold text-[rgb(var(--m3-color-on-secondary-container-rgb))]">
                        {{ user?.name?.charAt(0)?.toUpperCase() }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user?.name }}</p>
                        <p class="truncate text-xs capitalize text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ roles[0] || 'admin' }}</p>
                    </div>
                </div>
                <button type="button" class="flex w-full items-center justify-center gap-2 rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] px-3 py-2 text-xs font-semibold text-[rgb(var(--m3-color-on-surface-rgb))] transition-colors hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]" @click="logout">
                    <LogOut class="h-4 w-4" /> Keluar
                </button>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
            <header class="flex items-center gap-2 border-b border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] px-3 py-3 sm:gap-3 sm:px-5">
                <button type="button" class="rounded-lg p-2 text-[rgb(var(--m3-color-on-surface-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] lg:hidden" aria-label="Buka menu" @click="sidebarOpen = true">
                    <Menu class="h-5 w-5" />
                </button>

                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-sm font-semibold text-[rgb(var(--m3-color-on-surface-rgb))] sm:text-base">{{ pageTitle }}</h1>
                </div>

                <button
                    type="button"
                    class="hidden items-center gap-2 rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-3 py-2 text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))] transition-colors hover:border-[rgb(var(--m3-color-primary-rgb))] sm:flex"
                    @click="searchOpen = true"
                >
                    <Search class="h-4 w-4" />
                    <span>Cari…</span>
                    <kbd class="rounded border border-[rgb(var(--m3-color-outline-variant-rgb))] px-1.5 py-0.5 text-[10px]">Ctrl K</kbd>
                </button>

                <button type="button" class="rounded-lg p-2 text-[rgb(var(--m3-color-on-surface-variant-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] sm:hidden" aria-label="Cari" @click="searchOpen = true">
                    <Search class="h-5 w-5" />
                </button>

                <button type="button" class="rounded-lg p-2 text-[rgb(var(--m3-color-on-surface-variant-rgb))] transition-colors hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]" :aria-label="lightMode ? 'Aktifkan mode gelap' : 'Aktifkan mode terang'" @click="toggleTheme">
                    <Sun v-if="lightMode" class="h-5 w-5" />
                    <Moon v-else class="h-5 w-5" />
                </button>
            </header>

            <main class="flex-1 overflow-auto bg-[rgb(var(--m3-color-surface-rgb))]">
                <slot />
            </main>
        </div>

        <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="sidebarOpen = false" />

        <GlobalSearch v-model:open="searchOpen" />

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="toast" :class="['fixed bottom-4 right-4 z-[120] flex max-w-sm items-start gap-3 rounded-xl border px-4 py-3 shadow-xl', toast.type === 'success' ? 'border-[rgb(var(--m3-color-tertiary-container-rgb))] bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]' : 'border-[rgb(var(--m3-color-error-container-rgb))] bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]']" role="status">
                <CheckCircle2 v-if="toast.type === 'success'" class="mt-0.5 h-5 w-5 shrink-0" />
                <AlertCircle v-else class="mt-0.5 h-5 w-5 shrink-0" />
                <p class="text-sm font-medium">{{ toast.message }}</p>
                <button type="button" class="ml-auto rounded p-0.5 opacity-70 hover:opacity-100" aria-label="Tutup notifikasi" @click="toast = null">
                    <X class="h-4 w-4" />
                </button>
            </div>
        </Transition>
    </div>
</template>
