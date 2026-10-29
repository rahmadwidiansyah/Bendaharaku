<script setup>
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    user: Object,
})

const goBack = () => {
    router.get(route('admin.users.index'))
}
</script>

<template>
    <AdminLayout>
        <Head :title="`User: ${user.name}`" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold">{{ user.name }}</h1>
                    <p class="text-gray-600">User ID: {{ user.id }}</p>
                </div>
                <button
                    @click="goBack"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 font-medium transition-colors"
                >
                    ← Back
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Account Info Card -->
                    <div class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Account Information</h2>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Email</dt>
                                <dd class="text-gray-900 mt-1">{{ user.email }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Status</dt>
                                <dd class="mt-1">
                                    <span :class="[
                                        'px-3 py-1 rounded-full text-xs font-medium',
                                        user.status === 'suspended' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
                                    ]">
                                        {{ user.status || 'active' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Email Verified</dt>
                                <dd class="mt-1">
                                    <span :class="[
                                        'px-3 py-1 rounded-full text-xs font-medium',
                                        user.email_verified_at ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'
                                    ]">
                                        {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Joined</dt>
                                <dd class="text-gray-900 mt-1">{{ new Date(user.created_at).toLocaleDateString() }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Contact Info Card -->
                    <div class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Contact Information</h2>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Telegram ID</dt>
                                <dd class="text-gray-900 mt-1">{{ user.telegram_id || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">WhatsApp Number</dt>
                                <dd class="text-gray-900 mt-1">{{ user.whatsapp_number || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Google Account</dt>
                                <dd class="text-gray-900 mt-1">{{ user.google_id ? '✓ Connected' : '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Preferences Card -->
                    <div class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Preferences</h2>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Locale</dt>
                                <dd class="text-gray-900 mt-1">{{ user.locale || 'auto' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Timezone</dt>
                                <dd class="text-gray-900 mt-1">{{ user.timezone || 'auto' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">UI Theme</dt>
                                <dd class="text-gray-900 mt-1">{{ user.ui_theme || 'default' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Allow Negative Balance</dt>
                                <dd class="text-gray-900 mt-1">{{ user.allow_negative_balance ? 'Yes' : 'No' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Stats -->
                    <div class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Statistics</h2>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Wallets</dt>
                                <dd class="text-2xl font-bold text-gray-900 mt-1">{{ user.wallets?.length || 0 }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Categories</dt>
                                <dd class="text-2xl font-bold text-gray-900 mt-1">{{ user.categories?.length || 0 }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Transactions</dt>
                                <dd class="text-2xl font-bold text-gray-900 mt-1">{{ user.transactions?.length || 0 }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Actions Card -->
                    <div class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Actions</h2>
                        <div class="space-y-2">
                            <button
                                v-if="user.status !== 'suspended'"
                                @click="$inertia.post(route('admin.users.update_status', user.id), { status: 'suspended' })"
                                class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium transition-colors"
                            >
                                Suspend User
                            </button>
                            <button
                                v-if="user.status === 'suspended'"
                                @click="$inertia.post(route('admin.users.update_status', user.id), { status: 'active' })"
                                class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors"
                            >
                                Activate User
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
