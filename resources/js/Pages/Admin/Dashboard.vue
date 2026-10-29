<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Users, UserCheck, ArrowLeftRight, Calendar } from 'lucide-vue-next'

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
                <h1 class="text-3xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">Admin Dashboard</h1>
                <p class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">System overview and statistics</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Total Users</div>
                            <div class="text-3xl font-bold mt-2 text-[rgb(var(--m3-color-on-surface-rgb))]">{{ stats?.total_users ?? 0 }}</div>
                        </div>
                        <Users class="w-12 h-12 text-[rgb(var(--m3-color-primary-rgb))] opacity-40" />
                    </div>
                </div>
                
                <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Active Users</div>
                            <div class="text-3xl font-bold mt-2 text-[rgb(var(--m3-color-on-surface-rgb))]">{{ stats?.active_users ?? 0 }}</div>
                        </div>
                        <UserCheck class="w-12 h-12 text-[rgb(var(--m3-color-tertiary-rgb))] opacity-40" />
                    </div>
                </div>
                
                <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Total Transactions</div>
                            <div class="text-3xl font-bold mt-2 text-[rgb(var(--m3-color-on-surface-rgb))]">{{ stats?.total_transactions ?? 0 }}</div>
                        </div>
                        <ArrowLeftRight class="w-12 h-12 text-[rgb(var(--m3-color-secondary-rgb))] opacity-40" />
                    </div>
                </div>
                
                <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Last 7 Days</div>
                            <div class="text-3xl font-bold mt-2 text-[rgb(var(--m3-color-on-surface-rgb))]">{{ stats?.transactions_7d ?? 0 }}</div>
                        </div>
                        <Calendar class="w-12 h-12 text-[rgb(var(--m3-color-primary-rgb))] opacity-40" />
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="bg-[rgb(var(--m3-color-primary-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                <p class="text-sm text-[rgb(var(--m3-color-on-primary-container-rgb))]">
                    Logged in as <strong>{{ admin_user?.name }}</strong> ({{ admin_user?.email }})
                </p>
            </div>
        </div>
    </AdminLayout>
</template>
