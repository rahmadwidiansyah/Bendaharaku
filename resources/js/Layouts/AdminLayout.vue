<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { LayoutDashboard, Users, Headphones, FileText, History, Menu } from 'lucide-vue-next'

const page = usePage()
const sidebarOpen = ref(true)

const user = computed(() => page.props.auth.user)

const menuItems = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard'), component: LayoutDashboard },
    { label: 'Users', href: route('admin.users.index'), component: Users },
    { label: 'Support', href: route('admin.support.index'), component: Headphones },
    { label: 'AI Logs', href: route('admin.ai_logs.index'), component: FileText },
    { label: 'Audit Logs', href: route('admin.audit_logs.index'), component: History },
])

const isActive = (href) => {
    return route().current()?.startsWith(href.split('/').pop())
}

const logout = () => {
    router.post(route('logout'))
}
</script>

<template>
    <div class="flex h-screen bg-[rgb(var(--m3-color-surface-rgb))]">
        <!-- Sidebar -->
        <div
            :class="[
                'fixed inset-y-0 left-0 w-64 bg-[rgb(var(--m3-color-surface-container-rgb))] border-r border-[rgb(var(--m3-color-outline-variant-rgb))] transform transition-transform duration-300 lg:relative lg:translate-x-0 z-40',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Logo -->
            <div class="px-6 py-4 border-b border-[rgb(var(--m3-color-outline-variant-rgb))]">
                <h2 class="text-lg font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">Admin Panel</h2>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-4 space-y-1">
                <Link
                    v-for="item in menuItems"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        'flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                        isActive(item.href)
                            ? 'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]'
                            : 'text-[rgb(var(--m3-color-on-surface-variant-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]',
                    ]"
                >
                    <component :is="item.component" class="w-5 h-5 mr-3 flex-shrink-0" />
                    {{ item.label }}
                </Link>
            </nav>

            <!-- User & Logout -->
            <div class="px-4 py-4 border-t border-[rgb(var(--m3-color-outline-variant-rgb))] space-y-3">
                <div class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                    <p class="font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user?.name }}</p>
                    <p>{{ user?.email }}</p>
                </div>
                <button
                    @click="logout"
                    class="w-full px-4 py-2 text-sm font-medium text-[rgb(var(--m3-color-on-secondary-container-rgb))] bg-[rgb(var(--m3-color-secondary-container-rgb))] rounded-lg hover:bg-[rgb(var(--m3-color-secondary-rgb))] hover:text-[rgb(var(--m3-color-on-secondary-rgb))] transition-colors"
                >
                    Logout
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] border-b border-[rgb(var(--m3-color-outline-variant-rgb))] px-4 lg:px-6 py-4 flex items-center justify-between">
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    class="lg:hidden p-2 text-[rgb(var(--m3-color-on-surface-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] rounded-lg transition-colors"
                >
                    <Menu class="w-6 h-6" />
                </button>
                <div class="flex-1 px-4">
                    <h1 class="text-xl font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">Administration Panel</h1>
                </div>
            </div>

            <!-- Content -->
            <main class="flex-1 overflow-auto bg-[rgb(var(--m3-color-surface-rgb))]">
                <slot />
            </main>
        </div>

        <!-- Mobile overlay -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 bg-black/50 lg:hidden z-30"
        />
    </div>
</template>
