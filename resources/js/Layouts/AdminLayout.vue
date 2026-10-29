<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const page = usePage()
const sidebarOpen = ref(true)

const user = computed(() => page.props.auth.user)

const menuItems = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard'), icon: '📊' },
    { label: 'Users', href: route('admin.users.index'), icon: '👥' },
    { label: 'Support', href: route('admin.support.index'), icon: '🆘' },
    { label: 'AI Logs', href: route('admin.ai_logs.index'), icon: '🤖' },
    { label: 'Audit Logs', href: route('admin.audit_logs.index'), icon: '📋' },
])

const isActive = (href) => {
    return route().current()?.startsWith(href.split('/').pop())
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
                <h2 class="text-lg font-bold text-gray-900">Bendaharaku Admin</h2>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-4 space-y-2">
                <div
                    v-for="item in menuItems"
                    :key="item.href"
                    class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 py-2 mt-4 first:mt-0"
                >
                    {{ item.label }}
                </div>
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
                    <span class="mr-3">{{ item.icon }}</span>
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
                    class="lg:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-md"
                >
                    ☰
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
