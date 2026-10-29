<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()

defineProps({
    logs: Object,
    actions: Array,
    filters: Object,
})

const action = ref(page.props.filters?.action || '')
const admin_id = ref(page.props.filters?.admin_id || '')
const from_date = ref(page.props.filters?.from_date || '')
const to_date = ref(page.props.filters?.to_date || '')
const per_page = ref(page.props.filters?.per_page || 20)
const loading = ref(false)

const handleFilter = () => {
    loading.value = true
    router.get(route('admin.audit_logs.index'), {
        action: action.value,
        admin_id: admin_id.value,
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

const getActionBadge = (action) => {
    const colors = {
        'user_status_changed': 'bg-yellow-100 text-yellow-800',
        'support_search': 'bg-blue-100 text-blue-800',
        'support_view_user': 'bg-blue-100 text-blue-800',
        'support_access_logged': 'bg-green-100 text-green-800',
        'ai_provider_updated': 'bg-purple-100 text-purple-800',
        'default': 'bg-gray-100 text-gray-800',
    }
    return colors[action] || colors['default']
}

const formatAction = (action) => {
    return action
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
}

const getSuccessBadge = (success) => {
    return success 
        ? 'bg-green-100 text-green-800' 
        : 'bg-red-100 text-red-800'
}

const clearFilters = () => {
    action.value = ''
    admin_id.value = ''
    from_date.value = ''
    to_date.value = ''
    per_page.value = 20
    handleFilter()
}
</script>

<template>
    <AdminLayout>
        <Head title="Audit Logs" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold">Audit Logs</h1>
                <p class="text-gray-600">Track all administrative actions and changes</p>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg p-6 border border-gray-200 space-y-4">
                <h2 class="text-lg font-semibold">Filters</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Action Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                        <select
                            v-model="action"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">All Actions</option>
                            <option v-for="act in actions" :key="act" :value="act">
                                {{ formatAction(act) }}
                            </option>
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <div class="text-sm font-medium text-gray-600">Total Logs</div>
                    <div class="text-2xl font-bold mt-1">{{ logs.total }}</div>
                </div>
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <div class="text-sm font-medium text-gray-600">Page</div>
                    <div class="text-2xl font-bold mt-1">{{ logs.current_page }} of {{ logs.last_page }}</div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Admin</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Target</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ log.admin?.name || 'Unknown' }}
                                    <div class="text-xs text-gray-500">{{ log.admin?.email }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span :class="['px-3 py-1 rounded-full text-xs font-medium', getActionBadge(log.action)]">
                                        {{ formatAction(log.action) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="text-gray-900">{{ log.target_type }}</div>
                                    <div class="text-xs text-gray-600">#{{ log.target_id }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span :class="['px-3 py-1 rounded-full text-xs font-medium', getSuccessBadge(log.success)]">
                                        {{ log.success ? '✓ Success' : '✗ Failed' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <div>{{ new Date(log.created_at).toLocaleDateString() }}</div>
                                    <div class="text-xs">{{ new Date(log.created_at).toLocaleTimeString() }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <button
                                        @click="$inertia.get(route('admin.audit_logs.show', log.id))"
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
                    <p class="text-gray-500 text-sm">No audit logs found</p>
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
        </div>
    </AdminLayout>
</template>
