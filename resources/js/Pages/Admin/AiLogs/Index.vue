<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()

defineProps({
    logs: Object,
    filters: Object,
})

const status = ref(page.props.filters?.status || '')
const from_date = ref(page.props.filters?.from_date || '')
const to_date = ref(page.props.filters?.to_date || '')
const per_page = ref(page.props.filters?.per_page || 20)
const loading = ref(false)

const handleFilter = () => {
    loading.value = true
    router.get(route('admin.ai_logs.index'), {
        status: status.value,
        from_date: from_date.value,
        to_date: to_date.value,
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

const getStatusBadge = (status) => {
    const colors = {
        'success': 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]',
        'error': 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]',
        'retry': 'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]',
        'pending': 'bg-[rgb(var(--m3-color-secondary-container-rgb))] text-[rgb(var(--m3-color-on-secondary-container-rgb))]',
        'default': 'bg-[rgb(var(--m3-color-surface-container-high-rgb))] text-[rgb(var(--m3-color-on-surface-variant-rgb))]',
    }
    return colors[status] || colors['default']
}

const clearFilters = () => {
    status.value = ''
    from_date.value = ''
    to_date.value = ''
    per_page.value = 20
    handleFilter()
}
</script>

<template>
    <AdminLayout>
        <Head title="AI Logs" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">AI Operations Logs</h1>
                <p class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Monitor AI provider calls, errors, and retry attempts</p>
            </div>

            <!-- Filters -->
            <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))] space-y-4">
                <h2 class="text-lg font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">Filters</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))] mb-2">Status</label>
                        <select
                            v-model="status"
                            class="w-full px-4 py-2 bg-[rgb(var(--m3-color-surface-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))] focus:border-transparent"
                        >
                            <option value="">All Status</option>
                            <option value="success">Success</option>
                            <option value="error">Error</option>
                            <option value="retry">Retry</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div>
                        <label class="block text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))] mb-2">From Date</label>
                        <input
                            v-model="from_date"
                            type="date"
                            class="w-full px-4 py-2 bg-[rgb(var(--m3-color-surface-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))] focus:border-transparent"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))] mb-2">To Date</label>
                        <input
                            v-model="to_date"
                            type="date"
                            class="w-full px-4 py-2 bg-[rgb(var(--m3-color-surface-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))] focus:border-transparent"
                        />
                    </div>

                    <!-- Per Page -->
                    <div>
                        <label class="block text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))] mb-2">Per Page</label>
                        <select
                            v-model.number="per_page"
                            class="w-full px-4 py-2 bg-[rgb(var(--m3-color-surface-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))] focus:border-transparent"
                        >
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-end gap-2">
                        <button
                            @click="handleFilter"
                            :disabled="loading"
                            class="flex-1 px-4 py-2 bg-[rgb(var(--m3-color-primary-rgb))] text-[rgb(var(--m3-color-on-primary-rgb))] rounded-lg hover:bg-[rgb(var(--m3-color-primary-container-rgb))] hover:text-[rgb(var(--m3-color-on-primary-container-rgb))] disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                        >
                            {{ loading ? 'Loading...' : 'Filter' }}
                        </button>
                        <button
                            @click="clearFilters"
                            class="px-3 py-2 border border-[rgb(var(--m3-color-outline-variant-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] rounded-lg hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] transition-colors"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-4 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                    <div class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Total Logs</div>
                    <div class="text-2xl font-bold mt-1 text-[rgb(var(--m3-color-on-surface-rgb))]">{{ logs.total }}</div>
                </div>
                <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-4 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                    <div class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Current Page</div>
                    <div class="text-2xl font-bold mt-1 text-[rgb(var(--m3-color-on-surface-rgb))]">{{ logs.current_page }} of {{ logs.last_page }}</div>
                </div>
                <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-4 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                    <div class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Per Page</div>
                    <div class="text-2xl font-bold mt-1 text-[rgb(var(--m3-color-on-surface-rgb))]">{{ logs.per_page }}</div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg border border-[rgb(var(--m3-color-outline-variant-rgb))] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-[rgb(var(--m3-color-surface-container-high-rgb))] border-b border-[rgb(var(--m3-color-outline-variant-rgb))]">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[rgb(var(--m3-color-outline-variant-rgb))]">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">
                                    {{ log.subject || 'AI Call' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-[rgb(var(--m3-color-on-surface-rgb))]">
                                    <div class="max-w-xs truncate">{{ log.description || 'No description' }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span :class="['px-3 py-1 rounded-full text-xs font-medium', getStatusBadge(log.status)]">
                                        {{ log.status || 'pending' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                                    <div>{{ new Date(log.created_at).toLocaleDateString() }}</div>
                                    <div class="text-xs">{{ new Date(log.created_at).toLocaleTimeString() }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <button
                                        @click="$inertia.get(route('admin.ai_logs.show', log.id))"
                                        class="text-[rgb(var(--m3-color-primary-rgb))] hover:text-[rgb(var(--m3-color-primary-container-rgb))] font-medium"
                                    >
                                        View
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="logs.data.length === 0" class="px-6 py-12 text-center">
                    <p class="text-[rgb(var(--m3-color-on-surface-variant-rgb))] text-sm">No AI logs found</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="logs.last_page > 1" class="flex justify-center items-center space-x-2">
                <!-- Previous -->
                <button
                    v-if="logs.prev_page_url"
                    @click="handlePaginate(logs.prev_page_url)"
                    class="px-4 py-2 border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] text-sm font-medium transition-colors"
                >
                    ← Previous
                </button>

                <!-- Page Numbers -->
                <div class="flex space-x-1">
                    <button
                        v-for="page in logs.last_page"
                        :key="page"
                        :class="[
                            'px-3 py-2 rounded text-sm font-medium transition-colors',
                            page === logs.current_page
                                ? 'bg-[rgb(var(--m3-color-primary-rgb))] text-[rgb(var(--m3-color-on-primary-rgb))]'
                                : 'border border-[rgb(var(--m3-color-outline-variant-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]',
                        ]"
                        @click="handlePaginate(logs.path + '?page=' + page)"
                    >
                        {{ page }}
                    </button>
                </div>

                <!-- Next -->
                <button
                    v-if="logs.next_page_url"
                    @click="handlePaginate(logs.next_page_url)"
                    class="px-4 py-2 border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] text-sm font-medium transition-colors"
                >
                    Next →
                </button>
            </div>

            <!-- Info Box -->
            <div class="bg-[rgb(var(--m3-color-secondary-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-secondary-rgb))]">
                <h3 class="font-semibold text-[rgb(var(--m3-color-on-secondary-container-rgb))] mb-2">About AI Logs</h3>
                <ul class="text-sm text-[rgb(var(--m3-color-on-secondary-container-rgb))] space-y-1 list-disc list-inside">
                    <li>Logs track all AI provider API calls and responses</li>
                    <li>Status indicates success, errors, or retry attempts</li>
                    <li>Sanitized logs do not expose user data or API secrets</li>
                    <li>Use these logs to debug AI routing and fallback behavior</li>
                </ul>
            </div>
        </div>
    </AdminLayout>
</template>
