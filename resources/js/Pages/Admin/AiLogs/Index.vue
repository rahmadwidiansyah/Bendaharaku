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
        'success': 'bg-green-100 text-green-800',
        'error': 'bg-red-100 text-red-800',
        'retry': 'bg-yellow-100 text-yellow-800',
        'pending': 'bg-blue-100 text-blue-800',
        'default': 'bg-gray-100 text-gray-800',
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
                <h1 class="text-3xl font-bold">AI Operations Logs</h1>
                <p class="text-gray-600">Monitor AI provider calls, errors, and retry attempts</p>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg p-6 border border-gray-200 space-y-4">
                <h2 class="text-lg font-semibold">Filters</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select
                            v-model="status"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                        <input
                            v-model="from_date"
                            type="date"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                        <input
                            v-model="to_date"
                            type="date"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>

                    <!-- Per Page -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Per Page</label>
                        <select
                            v-model.number="per_page"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                        >
                            {{ loading ? 'Loading...' : 'Filter' }}
                        </button>
                        <button
                            @click="clearFilters"
                            class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <div class="text-sm font-medium text-gray-600">Total Logs</div>
                    <div class="text-2xl font-bold mt-1">{{ logs.total }}</div>
                </div>
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <div class="text-sm font-medium text-gray-600">Current Page</div>
                    <div class="text-2xl font-bold mt-1">{{ logs.current_page }} of {{ logs.last_page }}</div>
                </div>
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <div class="text-sm font-medium text-gray-600">Per Page</div>
                    <div class="text-2xl font-bold mt-1">{{ logs.per_page }}</div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ log.subject || 'AI Call' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <div class="max-w-xs truncate">{{ log.description || 'No description' }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span :class="['px-3 py-1 rounded-full text-xs font-medium', getStatusBadge(log.status)]">
                                        {{ log.status || 'pending' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <div>{{ new Date(log.created_at).toLocaleDateString() }}</div>
                                    <div class="text-xs">{{ new Date(log.created_at).toLocaleTimeString() }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <button
                                        @click="$inertia.get(route('admin.ai_logs.show', log.id))"
                                        class="text-blue-600 hover:text-blue-800 font-medium"
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
                    <p class="text-gray-500 text-sm">No AI logs found</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="logs.last_page > 1" class="flex justify-center items-center space-x-2">
                <!-- Previous -->
                <button
                    v-if="logs.prev_page_url"
                    @click="handlePaginate(logs.prev_page_url)"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 text-sm font-medium"
                >
                    ← Previous
                </button>

                <!-- Page Numbers -->
                <div class="flex space-x-1">
                    <button
                        v-for="page in logs.last_page"
                        :key="page"
                        :class="[
                            'px-3 py-2 rounded text-sm font-medium',
                            page === logs.current_page
                                ? 'bg-blue-600 text-white'
                                : 'border border-gray-300 hover:bg-gray-100',
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
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 text-sm font-medium"
                >
                    Next →
                </button>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
                <h3 class="font-semibold text-gray-900 mb-2">About AI Logs</h3>
                <ul class="text-sm text-gray-700 space-y-1 list-disc list-inside">
                    <li>Logs track all AI provider API calls and responses</li>
                    <li>Status indicates success, errors, or retry attempts</li>
                    <li>Sanitized logs do not expose user data or API secrets</li>
                    <li>Use these logs to debug AI routing and fallback behavior</li>
                </ul>
            </div>
        </div>
    </AdminLayout>
</template>
