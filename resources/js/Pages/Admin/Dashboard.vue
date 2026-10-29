<script setup>
import { Head } from '@inertiajs/vue3'
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
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600">Total Users</div>
                            <div class="text-3xl font-bold mt-2">{{ stats?.total_users ?? 0 }}</div>
                        </div>
                        <svg class="w-12 h-12 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 8.048 4 4 0 010-8.048M3 20.487a6 6 0 0111.933 0M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg p-6 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600">Active Users</div>
                            <div class="text-3xl font-bold mt-2">{{ stats?.active_users ?? 0 }}</div>
                        </div>
                        <svg class="w-12 h-12 text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg p-6 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600">Total Transactions</div>
                            <div class="text-3xl font-bold mt-2">{{ stats?.total_transactions ?? 0 }}</div>
                        </div>
                        <svg class="w-12 h-12 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg p-6 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600">Last 7 Days</div>
                            <div class="text-3xl font-bold mt-2">{{ stats?.transactions_7d ?? 0 }}</div>
                        </div>
                        <svg class="w-12 h-12 text-orange-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
                <p class="text-sm text-gray-700">
                    Logged in as <strong>{{ admin_user?.name }}</strong> ({{ admin_user?.email }})
                </p>
            </div>
        </div>
    </AdminLayout>
</template>
