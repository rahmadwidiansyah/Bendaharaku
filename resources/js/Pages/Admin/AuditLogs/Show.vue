<script setup>
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    log: Object,
})

const goBack = () => {
    router.get(route('admin.audit_logs.index'))
}

const formatAction = (action) => {
    return action
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
}
</script>

<template>
    <AdminLayout>
        <Head title="Audit Log Details" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold">Audit Log Details</h1>
                    <p class="text-gray-600">{{ formatAction(log.action) }} — {{ log.target_type }} #{{ log.target_id }}</p>
                </div>
                <button
                    @click="goBack"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 font-medium transition-colors"
                >
                    ← Back
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Event Summary -->
                    <div class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Event Summary</h2>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Action</dt>
                                <dd class="text-gray-900 mt-1 font-medium">{{ formatAction(log.action) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Status</dt>
                                <dd class="mt-1">
                                    <span :class="[
                                        'px-3 py-1 rounded-full text-xs font-medium',
                                        log.success ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                    ]">
                                        {{ log.success ? '✓ Success' : '✗ Failed' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Target Type</dt>
                                <dd class="text-gray-900 mt-1">{{ log.target_type }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Target ID</dt>
                                <dd class="text-gray-900 mt-1">#{{ log.target_id }}</dd>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-sm font-medium text-gray-600">Timestamp</dt>
                                <dd class="text-gray-900 mt-1">
                                    {{ new Date(log.created_at).toLocaleString() }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Actor Information -->
                    <div class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Performed By</h2>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Name</dt>
                                <dd class="text-gray-900 mt-1">{{ log.admin?.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Email</dt>
                                <dd class="text-gray-900 mt-1 break-all">{{ log.admin?.email }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Admin ID</dt>
                                <dd class="text-gray-900 mt-1">#{{ log.admin_id }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Changes Made -->
                    <div v-if="log.old_values || log.new_values" class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Changes</h2>
                        
                        <div class="space-y-4">
                            <!-- Old Values -->
                            <div v-if="log.old_values && Object.keys(log.old_values).length > 0">
                                <h3 class="text-sm font-medium text-gray-700 mb-2">Before</h3>
                                <pre class="bg-red-50 border border-red-200 rounded p-4 text-sm overflow-x-auto text-gray-800">{{ JSON.stringify(log.old_values, null, 2) }}</pre>
                            </div>

                            <!-- New Values -->
                            <div v-if="log.new_values && Object.keys(log.new_values).length > 0">
                                <h3 class="text-sm font-medium text-gray-700 mb-2">After</h3>
                                <pre class="bg-green-50 border border-green-200 rounded p-4 text-sm overflow-x-auto text-gray-800">{{ JSON.stringify(log.new_values, null, 2) }}</pre>
                            </div>
                        </div>
                    </div>

                    <!-- Metadata -->
                    <div v-if="log.metadata && Object.keys(log.metadata).length > 0" class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Request Details</h2>
                        <dl class="space-y-4">
                            <div v-if="log.metadata.ip">
                                <dt class="text-sm font-medium text-gray-600">IP Address</dt>
                                <dd class="text-gray-900 mt-1 font-mono">{{ log.metadata.ip }}</dd>
                            </div>
                            <div v-if="log.metadata.reason">
                                <dt class="text-sm font-medium text-gray-600">Reason</dt>
                                <dd class="text-gray-900 mt-1">{{ log.metadata.reason }}</dd>
                            </div>
                            <div v-if="log.metadata.user_agent">
                                <dt class="text-sm font-medium text-gray-600">User Agent</dt>
                                <dd class="text-gray-700 mt-1 text-xs bg-gray-50 p-3 rounded border border-gray-200 break-all">{{ log.metadata.user_agent }}</dd>
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
                                <dt class="text-xs font-medium text-gray-600 uppercase">Action Type</dt>
                                <dd class="text-gray-900 text-sm mt-1">{{ formatAction(log.action) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-600 uppercase">Outcome</dt>
                                <dd class="text-sm mt-1">
                                    <span :class="[
                                        'px-2 py-1 rounded text-xs font-medium',
                                        log.success ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                    ]">
                                        {{ log.success ? 'Success' : 'Failed' }}
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

                    <!-- Raw JSON -->
                    <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Raw Data</h2>
                        <pre class="text-xs bg-white border border-gray-200 rounded p-3 overflow-x-auto max-h-64 overflow-y-auto">{{ JSON.stringify(log, null, 2) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
