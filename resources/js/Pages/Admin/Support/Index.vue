<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'

defineProps({
    support_user: Object,
})

const searchQuery = ref('')
const searchResults = ref([])
const isSearching = ref(false)
const selectedUser = ref(null)
const accessReason = ref('')
const isLoggingAccess = ref(false)

const handleSearch = async () => {
    if (!searchQuery.value || searchQuery.value.length < 3) {
        alert('Search query must be at least 3 characters')
        return
    }

    isSearching.value = true
    try {
        const response = await axios.post(route('admin.support.search'), {
            query: searchQuery.value,
        })
        searchResults.value = response.data.results
    } catch (error) {
        alert('Search failed: ' + (error.response?.data?.message || error.message))
    } finally {
        isSearching.value = false
    }
}

const selectUser = (user) => {
    selectedUser.value = user
    accessReason.value = ''
}

const logAndViewAccess = async () => {
    if (!accessReason.value) {
        alert('Please provide a reason for accessing this user')
        return
    }

    isLoggingAccess.value = true
    try {
        await axios.post(route('admin.support.log_access', selectedUser.value.id), {
            reason: accessReason.value,
        })
        // Navigate to user detail
        router.get(route('admin.support.user_detail', selectedUser.value.id))
    } catch (error) {
        alert('Failed to log access: ' + (error.response?.data?.message || error.message))
    } finally {
        isLoggingAccess.value = false
    }
}

const clearSearch = () => {
    searchQuery.value = ''
    searchResults.value = []
    selectedUser.value = null
    accessReason.value = ''
}
</script>

<template>
    <AdminLayout>
        <Head title="Support Tools" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">Support Tools</h1>
                <p class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Search for users and access support information</p>
            </div>

            <!-- Search Card -->
            <div class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))] space-y-4">
                <h2 class="text-lg font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">Find User</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))] mb-2">Search by Name, Email, Phone, or Telegram ID</label>
                        <div class="flex gap-2">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Enter name, email, phone, or telegram ID..."
                                class="flex-1 px-4 py-2 bg-[rgb(var(--m3-color-surface-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))] focus:border-transparent"
                                @keyup.enter="handleSearch"
                            />
                            <button
                                @click="handleSearch"
                                :disabled="isSearching"
                                class="px-6 py-2 bg-[rgb(var(--m3-color-primary-rgb))] text-[rgb(var(--m3-color-on-primary-rgb))] rounded-lg hover:bg-[rgb(var(--m3-color-primary-container-rgb))] hover:text-[rgb(var(--m3-color-on-primary-container-rgb))] disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                            >
                                {{ isSearching ? 'Searching...' : 'Search' }}
                            </button>
                        </div>
                    </div>

                    <!-- Search Results -->
                    <div v-if="searchResults.length > 0" class="mt-4">
                        <h3 class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] mb-3">Results ({{ searchResults.length }})</h3>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            <button
                                v-for="user in searchResults"
                                :key="user.id"
                                @click="selectUser(user)"
                                :class="[
                                    'w-full text-left px-4 py-3 border rounded-lg transition-colors',
                                    selectedUser?.id === user.id
                                        ? 'bg-[rgb(var(--m3-color-primary-container-rgb))] border-[rgb(var(--m3-color-primary-rgb))]'
                                        : 'border-[rgb(var(--m3-color-outline-variant-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]'
                                ]"
                            >
                                <div class="font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user.name }}</div>
                                <div class="text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ user.email }}</div>
                                <div class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))] mt-1">
                                    Joined: {{ new Date(user.created_at).toLocaleDateString() }}
                                    <span v-if="user.email_verified_at" class="ml-2 text-[rgb(var(--m3-color-tertiary-rgb))]">✓ Verified</span>
                                    <span v-else class="ml-2 text-[rgb(var(--m3-color-error-rgb))]">⊘ Unverified</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- No Results -->
                    <div v-if="searchQuery && searchResults.length === 0 && !isSearching" class="text-center py-6 text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                        No users found matching your search
                    </div>
                </div>
            </div>

            <!-- Access Reason Card -->
            <div v-if="selectedUser" class="bg-[rgb(var(--m3-color-surface-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-outline-variant-rgb))] space-y-4">
                <h2 class="text-lg font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">Access Reason</h2>
                
                <div class="bg-[rgb(var(--m3-color-primary-container-rgb))] rounded p-4 border border-[rgb(var(--m3-color-primary-rgb))] mb-4">
                    <p class="text-sm text-[rgb(var(--m3-color-on-primary-container-rgb))]">
                        Selected user: <strong>{{ selectedUser.name }}</strong> ({{ selectedUser.email }})
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))] mb-2">Why are you accessing this user's information?</label>
                    <textarea
                        v-model="accessReason"
                        placeholder="e.g., User reported issue with wallet, Help with transaction verification, etc."
                        class="w-full px-4 py-2 bg-[rgb(var(--m3-color-surface-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] border border-[rgb(var(--m3-color-outline-variant-rgb))] rounded-lg focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))] focus:border-transparent"
                        rows="4"
                    ></textarea>
                    <p class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))] mt-2">This reason will be logged in the audit trail for compliance.</p>
                </div>

                <div class="flex gap-2">
                    <button
                        @click="logAndViewAccess"
                        :disabled="isLoggingAccess || !accessReason"
                        class="flex-1 px-4 py-2 bg-[rgb(var(--m3-color-tertiary-rgb))] text-[rgb(var(--m3-color-on-tertiary-rgb))] rounded-lg hover:bg-[rgb(var(--m3-color-tertiary-container-rgb))] hover:text-[rgb(var(--m3-color-on-tertiary-container-rgb))] disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                    >
                        {{ isLoggingAccess ? 'Logging...' : 'View User & Log Access' }}
                    </button>
                    <button
                        @click="clearSearch"
                        class="px-4 py-2 border border-[rgb(var(--m3-color-outline-variant-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))] rounded-lg hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] transition-colors font-medium"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-[rgb(var(--m3-color-secondary-container-rgb))] rounded-lg p-6 border border-[rgb(var(--m3-color-secondary-rgb))]">
                <h3 class="font-semibold text-[rgb(var(--m3-color-on-secondary-container-rgb))] mb-2">Important</h3>
                <ul class="text-sm text-[rgb(var(--m3-color-on-secondary-container-rgb))] space-y-1 list-disc list-inside">
                    <li>All access is logged and audited for security and compliance</li>
                    <li>Search results show limited user data to protect privacy</li>
                    <li>Always provide a legitimate business reason for accessing user information</li>
                    <li>Do not share or misuse user data accessed through this tool</li>
                </ul>
            </div>
        </div>
    </AdminLayout>
</template>
