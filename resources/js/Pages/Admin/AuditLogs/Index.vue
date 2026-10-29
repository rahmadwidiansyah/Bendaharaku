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
        'user_status_changed': 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]',
        'support_search': 'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]',
        'support_view_user': 'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]',
        'support_access_logged': 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]',
        'ai_provider_updated': 'bg-[rgb(var(--m3-color-secondary-container-rgb))] text-[rgb(var(--m3-color-on-secondary-container-rgb))]',
        'default': 'bg-[rgb(var(--m3-color-surface-container-high-rgb))] text-[rgb(var(--m3-color-on-surface-variant-rgb))]',
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
        ? 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]'
        : 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]'
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
                <h1 class="text-3xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">Audit Logs</h1>
                <p class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Track all administrative actions and changes</p>
            </div>

            <!-- Filters -->
            <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))] space-y-4">
                <h2 class="text-lg font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">Filters</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Action Filter -->
                    <div>
                        <label class="block text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))] mb-2">Action</label>
                        <select
                            v-model="action"
                            class="w-full px-4 py-2 bg-[rgb(var(--m3-color-surface-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))] focus:border-transparent"
                        >
                            <option value="">All Actions</option>
                            <option v-for="act in actions" :key="act" :value="act">
                                {{ formatAction(act) }}
                            </option>
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-4 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                    <div class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Total Logs</div>
                    <div class="text-2xl font-bold mt-1 text-[rgb(var(--m3-color-on-surface-rgb))]">{{ logs.total }}</div>
                </div>
                <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-4 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                    <div class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Page</div>
                    <div class="text-2xl font-bold mt-1 text-[rgb(var(--m3-color-on-surface-rgb))]">{{ logs.current_page }} of {{ logs.last_page }}</div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg border border-[rgb(var(--m3-color-outline-variant-rgb))] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-[rgb(var(--m3-color-surface-container-high-rgb))] border-b border-[rgb(var(--m3-color-outline-variant-rgb))]">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Admin</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Target</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] uppercase tracking-wider">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[rgb(var(--m3-color-outline-variant-rgb))]">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">
                                    {{ log.admin?.name || 'Unknown' }}
                                    <div class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ log.admin?.email }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span :class="['px-3 py-1 rounded-full text-xs font-medium', getActionBadge(log.action)]">
                                        {{ formatAction(log.action) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.target_type }}</div>
                                    <div class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">#{{ log.target_id }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span :class="['px-3 py-1 rounded-full text-xs font-medium', getSuccessBadge(log.success)]">
                                        {{ log.success ? '✓ Success' : '✗ Failed' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                                    <div>{{ new Date(log.created_at).toLocaleDateString() }}</div>
                                    <div class="text-xs">{{ new Date(log.created_at).toLocaleTimeString() }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <button
                                        @click="$inertia.get(route('admin.audit_logs.show', log.id))"
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
                    <p class="text-[rgb(var(--m3-color-on-surface-variant-rgb))] text-sm">No audit logs found</p>
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
        </div>
    </AdminLayout>
</template>
