<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()

defineProps({
    users: Object,
    filters: Object,
})

const search = ref(page.props.filters?.search || '')
const status = ref(page.props.filters?.status || '')
const per_page = ref(page.props.filters?.per_page || 15)
const loading = ref(false)

const handleSearch = () => {
    loading.value = true
    router.get(route('admin.users.index'), {
        search: search.value,
        status: status.value,
        per_page: per_page.value,
    }, {
        onFinish: () => {
            loading.value = false
        },
    })
}

const handlePaginate = (url) => {
    loading.value = true
    router.get(url, {}, {
        onFinish: () => {
            loading.value = false
        },
    })
}

const handleStatusChange = (user, newStatus) => {
    if (!confirm(`Change ${user.name} status to ${newStatus}?`)) return

    router.post(route('admin.users.update_status', user.id), {
        status: newStatus,
        reason: prompt('Reason (optional):'),
    })
}

const statusBadge = (status) => {
    return status === 'suspended' ? 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]' : 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]'
}

const verificationBadge = (verified_at) => {
    return verified_at ? 'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]' : 'bg-[rgb(var(--m3-color-surface-container-high-rgb))] text-[rgb(var(--m3-color-on-surface-variant-rgb))]'
}
</script>

<template>
    <AdminLayout>
        <Head title="User Management" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">User Management</h1>
                <p class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Manage users, view details, and change status</p>
            </div>

            <!-- Filters -->
            <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))] space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))] mb-2">Search</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Name or email..."
                            class="w-full px-4 py-2 bg-[rgb(var(--m3-color-surface-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))] focus:border-transparent"
                            @keyup.enter="handleSearch"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))] mb-2">Status</label>
                        <select
                            v-model="status"
                            class="w-full px-4 py-2 bg-[rgb(var(--m3-color-surface-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))] focus:border-transparent"
                        >
                            <option value="">All</option>
                            <option value="verified">Verified</option>
                            <option value="unverified">Unverified</option>
                        </select>
                    </div>

                    <!-- Items Per Page -->
                    <div>
                        <label class="block text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))] mb-2">Per Page</label>
                        <select
                            v-model.number="per_page"
                            class="w-full px-4 py-2 bg-[rgb(var(--m3-color-surface-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))] focus:border-transparent"
                        >
                            <option :value="10">10</option>
                            <option :value="15">15</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                    </div>
                </div>

                <button
                    @click="handleSearch"
                    :disabled="loading"
                    class="w-full px-4 py-2 bg-[rgb(var(--m3-color-primary-rgb))] text-[rgb(var(--m3-color-on-primary-rgb))] rounded-lg font-medium hover:bg-[rgb(var(--m3-color-primary-container-rgb))] hover:text-[rgb(var(--m3-color-on-primary-container-rgb))] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    {{ loading ? 'Loading...' : 'Search' }}
                </button>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-4 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                    <div class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Total Results</div>
                    <div class="text-2xl font-bold mt-1 text-[rgb(var(--m3-color-on-surface-rgb))]">{{ users.total }}</div>
                </div>
                <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-4 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                    <div class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Page</div>
                    <div class="text-2xl font-bold mt-1 text-[rgb(var(--m3-color-on-surface-rgb))]">{{ users.current_page }} of {{ users.last_page }}</div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg border border-[rgb(var(--m3-color-outline-variant-rgb))] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-[rgb(var(--m3-color-surface-container-high-rgb))] border-b border-[rgb(var(--m3-color-outline-variant-rgb))]">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Verification</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Joined</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[rgb(var(--m3-color-outline-variant-rgb))]">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user.name }}</td>
                                <td class="px-6 py-4 text-sm text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user.email }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span :class="['px-3 py-1 rounded-full text-xs font-medium', verificationBadge(user.email_verified_at)]">
                                        {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span :class="['px-3 py-1 rounded-full text-xs font-medium', statusBadge(user.status)]">
                                        {{ user.status || 'active' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                                    {{ new Date(user.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 text-sm space-x-2">
                                    <button
                                        @click="$inertia.get(route('admin.users.show', user.id))"
                                        class="px-3 py-1 bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))] rounded hover:bg-[rgb(var(--m3-color-primary-rgb))] hover:text-[rgb(var(--m3-color-on-primary-rgb))] transition-colors"
                                    >
                                        View
                                    </button>
                                    <div class="inline-block relative group">
                                        <button class="px-3 py-1 bg-[rgb(var(--m3-color-secondary-container-rgb))] text-[rgb(var(--m3-color-on-secondary-container-rgb))] rounded hover:bg-[rgb(var(--m3-color-secondary-rgb))] hover:text-[rgb(var(--m3-color-on-secondary-rgb))] transition-colors">
                                            Status ▼
                                        </button>
                                        <div class="absolute right-0 mt-1 w-32 bg-[rgb(var(--m3-color-surface-container-rgb))] border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded shadow-lg hidden group-hover:block z-10">
                                            <button
                                                @click="handleStatusChange(user, 'active')"
                                                class="block w-full text-left px-4 py-2 hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] text-sm text-[rgb(var(--m3-color-on-surface-rgb))]"
                                            >
                                                Active
                                            </button>
                                            <button
                                                @click="handleStatusChange(user, 'suspended')"
                                                class="block w-full text-left px-4 py-2 hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] text-sm text-[rgb(var(--m3-color-error-rgb))]"
                                            >
                                                Suspend
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="users.data.length === 0" class="px-6 py-12 text-center">
                    <p class="text-[rgb(var(--m3-color-on-surface-variant-rgb))] text-sm">No users found</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="users.last_page > 1" class="flex justify-center items-center space-x-2">
                <!-- Previous -->
                <button
                    v-if="users.prev_page_url"
                    @click="handlePaginate(users.prev_page_url)"
                    class="px-4 py-2 border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] text-sm font-medium transition-colors"
                >
                    ← Previous
                </button>

                <!-- Page Numbers -->
                <div class="flex space-x-1">
                    <button
                        v-for="page in users.last_page"
                        :key="page"
                        :class="[
                            'px-3 py-2 rounded text-sm font-medium transition-colors',
                            page === users.current_page
                                ? 'bg-[rgb(var(--m3-color-primary-rgb))] text-[rgb(var(--m3-color-on-primary-rgb))]'
                                : 'border border-[rgb(var(--m3-color-outline-variant-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]',
                        ]"
                        @click="handlePaginate(users.path + '?page=' + page)"
                    >
                        {{ page }}
                    </button>
                </div>

                <!-- Next -->
                <button
                    v-if="users.next_page_url"
                    @click="handlePaginate(users.next_page_url)"
                    class="px-4 py-2 border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] text-sm font-medium transition-colors"
                >
                    Next →
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
