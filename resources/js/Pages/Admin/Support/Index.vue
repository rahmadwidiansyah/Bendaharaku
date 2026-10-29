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
                <h1 class="text-3xl font-bold">Support Tools</h1>
                <p class="text-gray-600">Search for users and access support information</p>
            </div>

            <!-- Search Card -->
            <div class="bg-white rounded-lg p-6 border border-gray-200 space-y-4">
                <h2 class="text-lg font-semibold">Find User</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search by Name, Email, Phone, or Telegram ID</label>
                        <div class="flex gap-2">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Enter name, email, phone, or telegram ID..."
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                @keyup.enter="handleSearch"
                            />
                            <button
                                @click="handleSearch"
                                :disabled="isSearching"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                            >
                                {{ isSearching ? 'Searching...' : 'Search' }}
                            </button>
                        </div>
                    </div>

                    <!-- Search Results -->
                    <div v-if="searchResults.length > 0" class="mt-4">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Results ({{ searchResults.length }})</h3>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            <button
                                v-for="user in searchResults"
                                :key="user.id"
                                @click="selectUser(user)"
                                :class="[
                                    'w-full text-left px-4 py-3 border rounded-lg transition-colors',
                                    selectedUser?.id === user.id
                                        ? 'bg-blue-50 border-blue-300'
                                        : 'border-gray-200 hover:bg-gray-50'
                                ]"
                            >
                                <div class="font-medium text-gray-900">{{ user.name }}</div>
                                <div class="text-sm text-gray-600">{{ user.email }}</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    Joined: {{ new Date(user.created_at).toLocaleDateString() }}
                                    <span v-if="user.email_verified_at" class="ml-2 text-green-600">✓ Verified</span>
                                    <span v-else class="ml-2 text-yellow-600">⊘ Unverified</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- No Results -->
                    <div v-if="searchQuery && searchResults.length === 0 && !isSearching" class="text-center py-6 text-gray-500">
                        No users found matching your search
                    </div>
                </div>
            </div>

            <!-- Access Reason Card -->
            <div v-if="selectedUser" class="bg-white rounded-lg p-6 border border-gray-200 space-y-4">
                <h2 class="text-lg font-semibold">Access Reason</h2>
                
                <div class="bg-blue-50 rounded p-4 border border-blue-200 mb-4">
                    <p class="text-sm text-gray-700">
                        Selected user: <strong>{{ selectedUser.name }}</strong> ({{ selectedUser.email }})
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Why are you accessing this user's information?</label>
                    <textarea
                        v-model="accessReason"
                        placeholder="e.g., User reported issue with wallet, Help with transaction verification, etc."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        rows="4"
                    ></textarea>
                    <p class="text-xs text-gray-500 mt-2">This reason will be logged in the audit trail for compliance.</p>
                </div>

                <div class="flex gap-2">
                    <button
                        @click="logAndViewAccess"
                        :disabled="isLoggingAccess || !accessReason"
                        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                    >
                        {{ isLoggingAccess ? 'Logging...' : 'View User & Log Access' }}
                    </button>
                    <button
                        @click="clearSearch"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-yellow-50 rounded-lg p-6 border border-yellow-200">
                <h3 class="font-semibold text-gray-900 mb-2">Important</h3>
                <ul class="text-sm text-gray-700 space-y-1 list-disc list-inside">
                    <li>All access is logged and audited for security and compliance</li>
                    <li>Search results show limited user data to protect privacy</li>
                    <li>Always provide a legitimate business reason for accessing user information</li>
                    <li>Do not share or misuse user data accessed through this tool</li>
                </ul>
            </div>
        </div>
    </AdminLayout>
</template>
