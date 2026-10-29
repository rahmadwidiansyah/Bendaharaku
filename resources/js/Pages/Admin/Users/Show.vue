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
                    <h1 class="text-3xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user.name }}</h1>
                    <p class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">User ID: {{ user.id }}</p>
                </div>
                <button
                    @click="goBack"
                    class="px-4 py-2 border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] font-medium transition-colors"
                >
                    ← Back
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Account Info Card -->
                    <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                        <h2 class="text-lg font-semibold text-[rgb(var(--m3-color-on-surface-rgb))] mb-4">Account Information</h2>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Email</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1">{{ user.email }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Status</dt>
                                <dd class="mt-1">
                                    <span :class="[
                                        'px-3 py-1 rounded-full text-xs font-medium',
                                        user.status === 'suspended' ? 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]' : 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]'
                                    ]">
                                        {{ user.status || 'active' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Email Verified</dt>
                                <dd class="mt-1">
                                    <span :class="[
                                        'px-3 py-1 rounded-full text-xs font-medium',
                                        user.email_verified_at ? 'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]' : 'bg-[rgb(var(--m3-color-surface-container-high-rgb))] text-[rgb(var(--m3-color-on-surface-variant-rgb))]'
                                    ]">
                                        {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Joined</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1">{{ new Date(user.created_at).toLocaleDateString() }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Contact Info Card -->
                    <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                        <h2 class="text-lg font-semibold text-[rgb(var(--m3-color-on-surface-rgb))] mb-4">Contact Information</h2>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Telegram ID</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1">{{ user.telegram_id || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">WhatsApp Number</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1">{{ user.whatsapp_number || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Google Account</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1">{{ user.google_id ? '✓ Connected' : '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Preferences Card -->
                    <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                        <h2 class="text-lg font-semibold text-[rgb(var(--m3-color-on-surface-rgb))] mb-4">Preferences</h2>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Locale</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1">{{ user.locale || 'auto' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Timezone</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1">{{ user.timezone || 'auto' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">UI Theme</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1">{{ user.ui_theme || 'default' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Allow Negative Balance</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1">{{ user.allow_negative_balance ? 'Yes' : 'No' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Stats -->
                    <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                        <h2 class="text-lg font-semibold text-[rgb(var(--m3-color-on-surface-rgb))] mb-4">Statistics</h2>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Wallets</dt>
                                <dd class="text-2xl font-bold text-[rgb(var(--m3-color-primary-rgb))] mt-1">{{ user.wallets?.length || 0 }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Categories</dt>
                                <dd class="text-2xl font-bold text-[rgb(var(--m3-color-secondary-rgb))] mt-1">{{ user.categories?.length || 0 }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Transactions</dt>
                                <dd class="text-2xl font-bold text-[rgb(var(--m3-color-tertiary-rgb))] mt-1">{{ user.transactions?.length || 0 }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Actions Card -->
                    <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                        <h2 class="text-lg font-semibold text-[rgb(var(--m3-color-on-surface-rgb))] mb-4">Actions</h2>
                        <div class="space-y-2">
                            <button
                                v-if="user.status !== 'suspended'"
                                @click="$inertia.post(route('admin.users.update_status', user.id), { status: 'suspended' })"
                                class="w-full px-4 py-2 bg-[rgb(var(--m3-color-error-rgb))] text-[rgb(var(--m3-color-on-error-rgb))] rounded-lg hover:bg-[rgb(var(--m3-color-error-container-rgb))] hover:text-[rgb(var(--m3-color-on-error-container-rgb))] font-medium transition-colors"
                            >
                                Suspend User
                            </button>
                            <button
                                v-if="user.status === 'suspended'"
                                @click="$inertia.post(route('admin.users.update_status', user.id), { status: 'active' })"
                                class="w-full px-4 py-2 bg-[rgb(var(--m3-color-tertiary-rgb))] text-[rgb(var(--m3-color-on-tertiary-rgb))] rounded-lg hover:bg-[rgb(var(--m3-color-tertiary-container-rgb))] hover:text-[rgb(var(--m3-color-on-tertiary-container-rgb))] font-medium transition-colors"
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
