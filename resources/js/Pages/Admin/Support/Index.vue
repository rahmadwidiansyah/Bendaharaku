<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import {
    ArrowDown,
    ArrowUp,
    CheckCircle2,
    ChevronsUpDown,
    Headphones,
    Search,
    ShieldAlert,
    UserRound,
    XCircle,
} from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'

defineProps({
    support_user: Object,
})

const searchQuery = ref('')
const searchResults = ref([])
const isSearching = ref(false)
const searchError = ref('')
const selectedUser = ref(null)
const accessReason = ref('')
const accessError = ref('')
const isLoggingAccess = ref(false)
const sortKey = ref('joined')
const sortDirection = ref('desc')

const handleSearch = async () => {
    searchError.value = ''

    if (!searchQuery.value || searchQuery.value.trim().length < 3) {
        searchError.value = 'Kata kunci minimal 3 karakter.'
        return
    }

    isSearching.value = true
    try {
        const response = await axios.post(route('admin.support.search'), {
            query: searchQuery.value.trim(),
            sort: sortKey.value,
            direction: sortDirection.value,
        })
        searchResults.value = response.data.results
    } catch (error) {
        searchError.value = 'Pencarian gagal: ' + (error.response?.data?.message || error.message)
    } finally {
        isSearching.value = false
    }
}

const setSort = (key) => {
    if (sortKey.value !== key) {
        sortKey.value = key
        sortDirection.value = 'asc'
    } else if (sortDirection.value === 'asc') {
        sortDirection.value = 'desc'
    } else {
        sortKey.value = 'joined'
        sortDirection.value = 'desc'
    }

    if (searchResults.value.length > 0) {
        handleSearch()
    }
}

const sortIcon = (key) => {
    if (sortKey.value !== key) return ChevronsUpDown
    return sortDirection.value === 'asc' ? ArrowUp : ArrowDown
}

const selectUser = (user) => {
    selectedUser.value = user
    accessReason.value = ''
    accessError.value = ''
}

const logAndViewAccess = async () => {
    accessError.value = ''

    if (!accessReason.value.trim()) {
        accessError.value = 'Alasan akses wajib diisi dan akan dicatat di audit log.'
        return
    }

    isLoggingAccess.value = true
    try {
        await axios.post(route('admin.support.log_access', selectedUser.value.id), {
            reason: accessReason.value.trim(),
        })
        router.get(route('admin.support.user_detail', selectedUser.value.id))
    } catch (error) {
        accessError.value = 'Gagal mencatat akses: ' + (error.response?.data?.message || error.message)
    } finally {
        isLoggingAccess.value = false
    }
}

const clearSearch = () => {
    searchQuery.value = ''
    searchResults.value = []
    selectedUser.value = null
    accessReason.value = ''
    searchError.value = ''
    accessError.value = ''
}

const formatDate = (value) => (value ? new Date(value).toLocaleDateString('id-ID') : '—')
const formatRelative = (value) => {
    if (!value) return 'Belum pernah'
    const days = Math.floor((Date.now() - new Date(value).getTime()) / 86400000)
    if (days <= 0) return 'Hari ini'
    if (days === 1) return 'Kemarin'
    return `${days} hari lalu`
}
</script>

<template>
    <AdminLayout>
        <Head title="Support Tools" />

        <div class="space-y-6 p-4 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[rgb(var(--m3-color-primary-rgb))]">Support</p>
                    <h1 class="mt-1 text-2xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))] sm:text-3xl">Support Tools</h1>
                    <p class="mt-1 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Cari user dan akses informasi dukungan dengan alasan yang tercatat.</p>
                </div>
                <div class="flex items-center gap-2 rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] px-4 py-3">
                    <Headphones class="h-5 w-5 text-[rgb(var(--m3-color-primary-rgb))]" />
                    <div>
                        <p class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Support</p>
                        <p class="text-sm font-semibold leading-none text-[rgb(var(--m3-color-on-surface-rgb))]">{{ support_user?.name }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-4 sm:p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Cari user</h2>
                <form class="mt-3 flex flex-col gap-2 sm:flex-row" @submit.prevent="handleSearch">
                    <div class="relative flex-1">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[rgb(var(--m3-color-on-surface-variant-rgb))]" />
                        <input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Nama, email, nomor HP, atau Telegram ID…"
                            class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] py-2.5 pl-9 pr-3 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] placeholder:text-[rgb(var(--m3-color-on-surface-variant-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]"
                        />
                    </div>
                    <button type="submit" :disabled="isSearching" class="rounded-xl bg-[rgb(var(--m3-color-primary-rgb))] px-6 py-2.5 text-sm font-semibold text-[rgb(var(--m3-color-on-primary-rgb))] transition-colors hover:bg-[rgb(var(--m3-color-primary-container-rgb))] hover:text-[rgb(var(--m3-color-on-primary-container-rgb))] disabled:opacity-50">
                        {{ isSearching ? 'Mencari…' : 'Cari' }}
                    </button>
                    <button type="button" class="rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] px-4 py-2.5 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]" @click="clearSearch">Reset</button>
                </form>
                <p v-if="searchError" class="mt-2 text-xs font-medium text-[rgb(var(--m3-color-error-rgb))]">{{ searchError }}</p>

                <div v-if="searchResults.length > 0" class="mt-5">
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Hasil ({{ searchResults.length }})</p>
                        <div class="flex items-center gap-1 text-xs">
                            <button
                                v-for="sort in [{ key: 'name', label: 'Nama' }, { key: 'last_active', label: 'Aktif' }, { key: 'joined', label: 'Bergabung' }]"
                                :key="sort.key"
                                type="button"
                                class="inline-flex items-center gap-1 rounded-lg border border-[rgb(var(--m3-color-outline-variant-rgb))] px-2 py-1 font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]"
                                :class="{ 'text-[rgb(var(--m3-color-primary-rgb))]': sortKey === sort.key }"
                                @click="setSort(sort.key)"
                            >
                                {{ sort.label }}
                                <component :is="sortIcon(sort.key)" class="h-3 w-3" />
                            </button>
                        </div>
                    </div>

                    <div class="max-h-80 space-y-2 overflow-y-auto pr-1">
                        <button
                            v-for="user in searchResults"
                            :key="user.id"
                            type="button"
                            :class="[
                                'w-full rounded-xl border p-3 text-left transition-colors',
                                selectedUser?.id === user.id
                                    ? 'border-[rgb(var(--m3-color-primary-rgb))] bg-[rgb(var(--m3-color-primary-container-rgb))]'
                                    : 'border-[rgb(var(--m3-color-outline-variant-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]',
                            ]"
                            @click="selectUser(user)"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <UserRound class="h-4 w-4 text-[rgb(var(--m3-color-primary-rgb))]" />
                                <span class="font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user.name }}</span>
                                <span :class="['rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize', user.status === 'suspended' ? 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]' : 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]']">{{ user.status }}</span>
                                <span class="inline-flex items-center gap-1 text-[10px] text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                                    <CheckCircle2 v-if="user.email_verified_at" class="h-3 w-3" />
                                    <XCircle v-else class="h-3 w-3" />
                                    {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ user.email }}</p>
                            <p class="mt-1 text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                                Bergabung {{ formatDate(user.created_at) }} · Aktif {{ formatRelative(user.last_active_at) }}
                            </p>
                        </button>
                    </div>
                </div>

                <div v-else-if="searchQuery && !isSearching" class="mt-6 rounded-xl border border-dashed border-[rgb(var(--m3-color-outline-variant-rgb))] px-4 py-8 text-center text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                    Tidak ada user yang cocok. Coba kata kunci lain.
                </div>
            </div>

            <div v-if="selectedUser" class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-4 sm:p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Alasan akses</h2>
                <div class="mt-3 rounded-xl bg-[rgb(var(--m3-color-primary-container-rgb))] p-3 text-sm text-[rgb(var(--m3-color-on-primary-container-rgb))]">
                    User terpilih: <strong>{{ selectedUser.name }}</strong> ({{ selectedUser.email }})
                </div>

                <label class="mt-4 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Mengapa Anda mengakses data user ini?</label>
                <textarea
                    v-model="accessReason"
                    rows="3"
                    maxlength="255"
                    placeholder="Contoh: user melaporkan transaksi tidak muncul, verifikasi kendala login, dsb."
                    class="mt-1.5 w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-3 py-2 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] placeholder:text-[rgb(var(--m3-color-on-surface-variant-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]"
                />
                <p class="mt-1 text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Alasan ini dicatat di audit trail untuk kepatuhan.</p>
                <p v-if="accessError" class="mt-1 text-xs font-medium text-[rgb(var(--m3-color-error-rgb))]">{{ accessError }}</p>

                <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <button type="button" :disabled="isLoggingAccess" class="rounded-xl bg-[rgb(var(--m3-color-tertiary-rgb))] px-4 py-2.5 text-sm font-semibold text-[rgb(var(--m3-color-on-tertiary-rgb))] transition-colors hover:bg-[rgb(var(--m3-color-tertiary-container-rgb))] hover:text-[rgb(var(--m3-color-on-tertiary-container-rgb))] disabled:opacity-50" @click="logAndViewAccess">
                        {{ isLoggingAccess ? 'Mencatat…' : 'Lihat user & catat akses' }}
                    </button>
                    <button type="button" class="rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] px-4 py-2.5 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]" @click="selectedUser = null">Batal</button>
                </div>
            </div>

            <div class="rounded-2xl border border-[rgb(var(--m3-color-secondary-container-rgb))] bg-[rgb(var(--m3-color-secondary-container-rgb))] p-4 text-sm text-[rgb(var(--m3-color-on-secondary-container-rgb))]">
                <div class="flex items-start gap-2">
                    <ShieldAlert class="mt-0.5 h-4 w-4 shrink-0" />
                    <div>
                        <p class="font-semibold">Penting</p>
                        <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs">
                            <li>Semua akses dicatat dan diaudit.</li>
                            <li>Hasil pencarian hanya menampilkan data minimal untuk privasi.</li>
                            <li>Selalu isi alasan bisnis yang sah sebelum mengakses data user.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
