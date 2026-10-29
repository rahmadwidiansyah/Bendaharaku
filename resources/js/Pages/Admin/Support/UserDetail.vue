<script setup>
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    user: Object,
})

const goBack = () => {
    router.get(route('admin.support.index'))
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Support: ${user.name}`" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold">{{ user.name }}</h1>
                    <p class="text-gray-600">Support Information</p>
                </div>
                <button
                    @click="goBack"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 font-medium transition-colors"
                >
                    ← Back to Search
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Contact Information -->
                    <div class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Contact Information</h2>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Email</dt>
                                <dd class="text-gray-900 mt-1 break-all">{{ user.email }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Telegram ID</dt>
                                <dd class="text-gray-900 mt-1">{{ user.telegram_id || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">WhatsApp</dt>
                                <dd class="text-gray-900 mt-1">{{ user.whatsapp_number || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Email Verified</dt>
                                <dd class="mt-1">
                                    <span :class="[
                                        'px-3 py-1 rounded-full text-xs font-medium',
                                        user.email_verified_at ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                    ]">
                                        {{ user.email_verified_at ? '✓ Verified' : '⊘ Unverified' }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Account Preferences -->
                    <div class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Account Preferences</h2>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Bot Name</dt>
                                <dd class="text-gray-900 mt-1">{{ user.bot_name || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Date Format</dt>
                                <dd class="text-gray-900 mt-1">{{ user.date_format || '—' }}</dd>
                            </div>
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

                    <!-- Financial Summary -->
                    <div class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Financial Summary</h2>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Wallets</dt>
                                <dd class="text-2xl font-bold text-gray-900 mt-1">{{ user.wallets?.length || 0 }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Categories</dt>
                                <dd class="text-2xl font-bold text-gray-900 mt-1">{{ user.categories?.length || 0 }}</dd>
                            </div>
                        </dl>

                        <div v-if="user.wallets?.length > 0" class="mt-6 space-y-3">
                            <h3 class="text-sm font-semibold text-gray-700">Wallets</h3>
                            <div class="space-y-2">
                                <div
                                    v-for="wallet in user.wallets"
                                    :key="wallet.id"
                                    class="flex justify-between items-center p-3 bg-gray-50 rounded border border-gray-200"
                                >
                                    <div>
                                        <p class="font-medium text-gray-900">{{ wallet.name }}</p>
                                        <p class="text-xs text-gray-600">Created: {{ new Date(wallet.created_at).toLocaleDateString() }}</p>
                                    </div>
                                    <div class="text-sm font-medium text-gray-700">{{ wallet.currency }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Account Status -->
                    <div class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Account Status</h2>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Member Since</dt>
                                <dd class="text-gray-900 mt-1">{{ new Date(user.created_at).toLocaleDateString() }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Last Updated</dt>
                                <dd class="text-gray-900 mt-1">{{ new Date(user.updated_at).toLocaleDateString() }}</dd>
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
                        </dl>
                    </div>

                    <!-- Quick Links -->
                    <div class="bg-white rounded-lg p-6 border border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Quick Links</h2>
                        <div class="space-y-2">
                            <button
                                @click="$inertia.get(route('admin.users.show', user.id))"
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors text-sm"
                            >
                                View Full Profile
                            </button>
                        </div>
                    </div>

                    <!-- Privacy Notice -->
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Privacy Notice</h3>
                        <p class="text-xs text-gray-700">This access has been logged. Use only for legitimate support purposes.</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
