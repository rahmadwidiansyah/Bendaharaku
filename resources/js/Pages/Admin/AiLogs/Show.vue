<script setup>
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    log: Object,
})

const goBack = () => {
    router.get(route('admin.ai_logs.index'))
}

const getStatusColor = (status) => {
    const colors = {
        'success': 'bg-green-100 text-green-800',
        'error': 'bg-red-100 text-red-800',
        'retry': 'bg-yellow-100 text-yellow-800',
        'pending': 'bg-blue-100 text-blue-800',
        'default': 'bg-gray-100 text-gray-800',
    }
    return colors[status] || colors['default']
}
</script>

<template>
    <AdminLayout>
        <Head title="AI Log Details" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold">{{ log.subject || 'AI Log' }}</h1>
                    <p class="text-gray-600">{{ new Date(log.created_at).toLocaleString() }}</p>
                </div>
                <button
                    @click="goBack"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 font-medium transition-colors"
                >
                    ← Back to Logs
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Summary -->
                    <div class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Log Summary</h2>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Status</dt>
                                <dd class="mt-1">
                                    <span :class="['px-3 py-1 rounded-full text-xs font-medium', getStatusColor(log.status)]">
                                        {{ log.status || 'pending' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Subject</dt>
                                <dd class="text-gray-900 mt-1">{{ log.subject || 'AI Call' }}</dd>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-sm font-medium text-gray-600">Description</dt>
                                <dd class="text-gray-900 mt-1">{{ log.description || 'No description provided' }}</dd>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-sm font-medium text-gray-600">Timestamp</dt>
                                <dd class="text-gray-900 mt-1">{{ new Date(log.created_at).toLocaleString() }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Properties -->
                    <div v-if="log.properties" class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Properties</h2>
                        <pre class="bg-gray-50 border border-gray-200 rounded p-4 text-sm overflow-x-auto max-h-64 overflow-y-auto">{{ JSON.stringify(log.properties, null, 2) }}</pre>
                    </div>

                    <!-- Changes -->
                    <div v-if="log.changes" class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Changes</h2>
                        <pre class="bg-gray-50 border border-gray-200 rounded p-4 text-sm overflow-x-auto max-h-64 overflow-y-auto">{{ JSON.stringify(log.changes, null, 2) }}</pre>
                    </div>

                    <!-- Causer Info -->
                    <div v-if="log.causer_id" class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Causer</h2>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Causer ID</dt>
                                <dd class="text-gray-900 mt-1">#{{ log.causer_id }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Causer Type</dt>
                                <dd class="text-gray-900 mt-1">{{ log.causer_type || 'System' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Info -->
                    <div class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Quick Info</h2>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-xs font-medium text-gray-600 uppercase">Log ID</dt>
                                <dd class="text-gray-900 text-sm mt-1 font-mono">#{{ log.id }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-600 uppercase">Status</dt>
                                <dd class="text-sm mt-1">
                                    <span :class="['px-2 py-1 rounded text-xs font-medium', getStatusColor(log.status)]">
                                        {{ log.status || 'pending' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-600 uppercase">Date</dt>
                                <dd class="text-gray-900 text-sm mt-1">{{ new Date(log.created_at).toLocaleDateString() }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-600 uppercase">Time</dt>
                                <dd class="text-gray-900 text-sm mt-1">{{ new Date(log.created_at).toLocaleTimeString() }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Model Info -->
                    <div v-if="log.subject_type" class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Related Model</h2>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-xs font-medium text-gray-600 uppercase">Type</dt>
                                <dd class="text-gray-900 text-sm mt-1">{{ log.subject_type }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-600 uppercase">ID</dt>
                                <dd class="text-gray-900 text-sm mt-1">#{{ log.subject_id }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Help -->
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">About This Log</h3>
                        <p class="text-xs text-gray-700">This log tracks AI operations. No user data or secrets are exposed here for security and privacy.</p>
                    </div>
                </div>
            </div>

            <!-- Full JSON -->
            <div class="bg-white rounded-lg p-6 border border-gray-200">
                <h2 class="text-lg font-semibold mb-4">Raw JSON</h2>
                <pre class="bg-gray-50 border border-gray-200 rounded p-4 text-xs overflow-x-auto max-h-96 overflow-y-auto">{{ JSON.stringify(log, null, 2) }}</pre>
            </div>
        </div>
    </AdminLayout>
</template>
