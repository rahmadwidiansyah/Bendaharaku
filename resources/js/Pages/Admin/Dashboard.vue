<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    stats: Object,
    admin_user: Object,
})
</script>

<template>
    <AdminLayout>
        <Head title="Admin Dashboard" />
        
        <div class="space-y-6 p-6">
            <div class="mb-8">
                <h1 class="text-3xl font-bold">Admin Dashboard</h1>
                <p class="text-gray-600">System overview and statistics</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg p-6 border border-gray-200">
                    <div class="text-sm font-medium text-gray-600">Total Users</div>
                    <div class="text-3xl font-bold mt-2">{{ stats?.total_users ?? 0 }}</div>
                </div>
                
                <div class="bg-white rounded-lg p-6 border border-gray-200">
                    <div class="text-sm font-medium text-gray-600">Active Users</div>
                    <div class="text-3xl font-bold mt-2">{{ stats?.active_users ?? 0 }}</div>
                </div>
                
                <div class="bg-white rounded-lg p-6 border border-gray-200">
                    <div class="text-sm font-medium text-gray-600">Total Transactions</div>
                    <div class="text-3xl font-bold mt-2">{{ stats?.total_transactions ?? 0 }}</div>
                </div>
                
                <div class="bg-white rounded-lg p-6 border border-gray-200">
                    <div class="text-sm font-medium text-gray-600">Last 7 Days</div>
                    <div class="text-3xl font-bold mt-2">{{ stats?.transactions_7d ?? 0 }}</div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
                <p class="text-sm text-gray-600">
                    Logged in as <strong>{{ admin_user?.name }}</strong> ({{ admin_user?.email }})
                </p>
            </div>
        </div>
    </AdminLayout>
</template>
