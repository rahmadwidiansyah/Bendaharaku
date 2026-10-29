<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const page = usePage()
const sidebarOpen = ref(true)

const user = computed(() => page.props.auth.user)

const menuItems = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard'), icon: 'dashboard' },
    { label: 'Users', href: route('admin.users.index'), icon: 'users' },
    { label: 'Support', href: route('admin.support.index'), icon: 'support' },
    { label: 'AI Logs', href: route('admin.ai_logs.index'), icon: 'logs' },
    { label: 'Audit Logs', href: route('admin.audit_logs.index'), icon: 'history' },
])

const isActive = (href) => {
    return route().current()?.startsWith(href.split('/').pop())
}

const getIcon = (name) => {
    const icons = {
        dashboard: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 16l4-4m0 0l4 4m-4-4v4"></path></svg>',
        users: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 8.048 4 4 0 010-8.048M3 20.487a6 6 0 0111.933 0M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        support: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.172l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>',
        logs: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
        history: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
    }
    return icons[name] || icons.dashboard
}

const logout = () => {
    router.post(route('logout'))
}
</script>

<template>
    <div class="flex h-screen bg-gray-50">
        <!-- Sidebar -->
        <div
            :class="[
                'fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-200 transform transition-transform duration-300 lg:relative lg:translate-x-0 z-40',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Logo -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">Admin Panel</h2>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-4 space-y-1">
                <Link
                    v-for="item in menuItems"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        'flex items-center px-4 py-2 rounded-md text-sm font-medium transition-colors',
                        isActive(item.href)
                            ? 'bg-blue-50 text-blue-600'
                            : 'text-gray-700 hover:bg-gray-100',
                    ]"
                >
                    <span class="mr-3 flex-shrink-0" v-html="getIcon(item.icon)"></span>
                    {{ item.label }}
                </Link>
            </nav>

            <!-- User & Logout -->
            <div class="px-4 py-4 border-t border-gray-200 space-y-3">
                <div class="text-xs text-gray-500">
                    <p class="font-semibold text-gray-900">{{ user?.name }}</p>
                    <p>{{ user?.email }}</p>
                </div>
                <button
                    @click="logout"
                    class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors"
                >
                    Logout
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <div class="bg-white border-b border-gray-200 px-4 lg:px-6 py-4 flex items-center justify-between">
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    class="lg:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-md transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div class="flex-1 px-4">
                    <h1 class="text-xl font-semibold text-gray-900">Administration Panel</h1>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-auto">
                <slot />
            </div>
        </div>

        <!-- Mobile overlay -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 bg-black/50 lg:hidden z-30"
        />
    </div>
</template>
