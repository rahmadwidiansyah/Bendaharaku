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
                    <h1 class="text-3xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user.name }}</h1>
                    <p class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Support Information</p>
                </div>
                <button
                    @click="goBack"
                    class="px-4 py-2 border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] font-medium transition-colors"
                >
                    ← Back to Search
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Contact Information -->
                    <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                        <h2 class="text-lg font-semibold text-[rgb(var(--m3-color-on-surface-rgb))] mb-4">Contact Information</h2>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Email</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1 break-all">{{ user.email }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Telegram ID</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1">{{ user.telegram_id || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">WhatsApp</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1">{{ user.whatsapp_number || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Email Verified</dt>
                                <dd class="mt-1">
                                    <span :class="[
                                        'px-3 py-1 rounded-full text-xs font-medium',
                                        user.email_verified_at ? 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]' : 'bg-[rgb(var(--m3-color-surface-container-high-rgb))] text-[rgb(var(--m3-color-on-surface-variant-rgb))]'
                                    ]">
                                        {{ user.email_verified_at ? '✓ Verified' : '⊘ Unverified' }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Account Preferences -->
                    <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                        <h2 class="text-lg font-semibold text-[rgb(var(--m3-color-on-surface-rgb))] mb-4">Account Preferences</h2>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Bot Name</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1">{{ user.bot_name || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Date Format</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1">{{ user.date_format || '—' }}</dd>
                            </div>
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

                    <!-- Financial Summary -->
                    <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                        <h2 class="text-lg font-semibold text-[rgb(var(--m3-color-on-surface-rgb))] mb-4">Financial Summary</h2>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Wallets</dt>
                                <dd class="text-2xl font-bold text-[rgb(var(--m3-color-primary-rgb))] mt-1">{{ user.wallets?.length || 0 }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Categories</dt>
                                <dd class="text-2xl font-bold text-[rgb(var(--m3-color-secondary-rgb))] mt-1">{{ user.categories?.length || 0 }}</dd>
                            </div>
                        </dl>

                        <div v-if="user.wallets?.length > 0" class="mt-6 space-y-3">
                            <h3 class="text-sm font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">Wallets</h3>
                            <div class="space-y-2">
                                <div
                                    v-for="wallet in user.wallets"
                                    :key="wallet.id"
                                    class="flex justify-between items-center p-3 bg-[rgb(var(--m3-color-surface-container-high-rgb))] rounded border border-[rgb(var(--m3-color-outline-variant-rgb))]"
                                >
                                    <div>
                                        <p class="font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ wallet.name }}</p>
                                        <p class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Created: {{ new Date(wallet.created_at).toLocaleDateString() }}</p>
                                    </div>
                                    <div class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ wallet.currency }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Account Status -->
                    <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                        <h2 class="text-lg font-semibold text-[rgb(var(--m3-color-on-surface-rgb))] mb-4">Account Status</h2>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Member Since</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1">{{ new Date(user.created_at).toLocaleDateString() }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Last Updated</dt>
                                <dd class="text-[rgb(var(--m3-color-on-surface-rgb))] mt-1">{{ new Date(user.updated_at).toLocaleDateString() }}</dd>
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
                        </dl>
                    </div>

                    <!-- Quick Links -->
                    <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))]">
                        <h2 class="text-lg font-semibold text-[rgb(var(--m3-color-on-surface-rgb))] mb-4">Quick Links</h2>
                        <div class="space-y-2">
                            <button
                                @click="$inertia.get(route('admin.users.show', user.id))"
                                class="w-full px-4 py-2 bg-[rgb(var(--m3-color-primary-rgb))] text-[rgb(var(--m3-color-on-primary-rgb))] rounded-lg hover:bg-[rgb(var(--m3-color-primary-container-rgb))] hover:text-[rgb(var(--m3-color-on-primary-container-rgb))] font-medium transition-colors text-sm"
                            >
                                View Full Profile
                            </button>
                        </div>
                    </div>

                    <!-- Privacy Notice -->
                    <div class="bg-[rgb(var(--m3-color-secondary-container-rgb))] rounded-lg p-4 border border-[rgb(var(--m3-color-secondary-rgb))]">
                        <h3 class="text-sm font-semibold text-[rgb(var(--m3-color-on-secondary-container-rgb))] mb-2">Privacy Notice</h3>
                        <p class="text-xs text-[rgb(var(--m3-color-on-secondary-container-rgb))]">This access has been logged. Use only for legitimate support purposes.</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
