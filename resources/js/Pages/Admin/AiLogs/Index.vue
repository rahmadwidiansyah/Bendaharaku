<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
    Activity,
    Eye,
    Filter,
    RotateCcw,
    Search,
} from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SortableTh from '@/Components/Admin/SortableTh.vue'
import AdminPagination from '@/Components/Admin/AdminPagination.vue'
import { useAdminTable } from '@/Composables/useAdminTable'

const props = defineProps({
    logs: { type: Object, required: true },
    logNames: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const { query, navigate, sortBy, reset } = useAdminTable()

const search = ref(props.filters.search || '')
const status = ref(props.filters.status || '')
const logName = ref(props.filters.log_name || '')
const fromDate = ref(props.filters.from_date || '')
const toDate = ref(props.filters.to_date || '')
const perPage = ref(Number(props.filters.per_page || 20))

const currentSort = computed(() => query.value.sort)
const currentDirection = computed(() => query.value.direction || 'desc')

const activeFilterCount = computed(
    () => [search.value, status.value, logName.value, fromDate.value, toDate.value].filter(Boolean).length,
)

const applyFilters = () => {
    navigate({
        search: search.value,
        status: status.value,
        log_name: logName.value,
        from_date: fromDate.value,
        to_date: toDate.value,
        per_page: perPage.value,
    })
}

const changePerPage = () => navigate({ per_page: perPage.value })

const clearFilters = () => {
    search.value = ''
    status.value = ''
    logName.value = ''
    fromDate.value = ''
    toDate.value = ''
    perPage.value = 20
    reset()
}

const statusBadge = (value) => {
    const palette = {
        success: 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]',
        error: 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]',
        retry: 'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]',
        pending: 'bg-[rgb(var(--m3-color-secondary-container-rgb))] text-[rgb(var(--m3-color-on-secondary-container-rgb))]',
    }
    return palette[value] || 'bg-[rgb(var(--m3-color-surface-container-high-rgb))] text-[rgb(var(--m3-color-on-surface-variant-rgb))]'
}

const formatDateTime = (value) =>
    value
        ? new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
        : '—'
</script>

<template>
    <AdminLayout>
        <Head title="AI Logs" />

        <div class="space-y-6 p-4 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[rgb(var(--m3-color-primary-rgb))]">Operations</p>
                    <h1 class="mt-1 text-2xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))] sm:text-3xl">AI Operations Logs</h1>
                    <p class="mt-1 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Pantau panggilan provider, error, dan retry AI.</p>
                </div>
                <div class="flex items-center gap-2 rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] px-4 py-3">
                    <Activity class="h-5 w-5 text-[rgb(var(--m3-color-primary-rgb))]" />
                    <div>
                        <p class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Total log</p>
                        <p class="text-lg font-bold leading-none text-[rgb(var(--m3-color-on-surface-rgb))]">{{ logs.total }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-4 sm:p-5">
                <form class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12" @submit.prevent="applyFilters">
                    <div class="xl:col-span-4">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Cari</label>
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[rgb(var(--m3-color-on-surface-variant-rgb))]" />
                            <input v-model="search" type="search" placeholder="Deskripsi atau log name…" class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] py-2.5 pl-9 pr-3 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] placeholder:text-[rgb(var(--m3-color-on-surface-variant-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]" />
                        </div>
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Status</label>
                        <select v-model="status" class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-3 py-2.5 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]">
                            <option value="">Semua</option>
                            <option value="success">Success</option>
                            <option value="error">Error</option>
                            <option value="retry">Retry</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Log name</label>
                        <select v-model="logName" class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-3 py-2.5 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]">
                            <option value="">Semua</option>
                            <option v-for="name in logNames" :key="name" :value="name">{{ name }}</option>
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Dari</label>
                        <input v-model="fromDate" type="date" class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-3 py-2.5 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]" />
                    </div>

                    <div class="xl:col-span-1">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Sampai</label>
                        <input v-model="toDate" type="date" class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-2 py-2.5 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]" />
                    </div>

                    <div class="flex items-end gap-2 xl:col-span-1">
                        <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-xl bg-[rgb(var(--m3-color-primary-rgb))] px-3 py-2.5 text-sm font-semibold text-[rgb(var(--m3-color-on-primary-rgb))]" title="Terapkan filter">
                            <Filter class="h-4 w-4" />
                        </button>
                        <button type="button" class="rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] p-2.5 text-[rgb(var(--m3-color-on-surface-variant-rgb))]" title="Reset filter" @click="clearFilters">
                            <RotateCcw class="h-4 w-4" />
                        </button>
                    </div>
                </form>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                    <span v-if="activeFilterCount > 0">{{ activeFilterCount }} filter aktif</span>
                    <span v-else>Menampilkan semua log.</span>
                    <label class="flex items-center gap-2">
                        <span>Baris</span>
                        <select v-model.number="perPage" class="rounded-lg border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-2 py-1 text-xs text-[rgb(var(--m3-color-on-surface-rgb))]" @change="changePerPage">
                            <option v-for="size in [10, 20, 50, 100]" :key="size" :value="size">{{ size }}</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))]">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px]">
                        <thead class="border-b border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-high-rgb))]">
                            <tr>
                                <SortableTh column="log_name" label="Log" :sort="currentSort" :direction="currentDirection" @sort="sortBy" />
                                <SortableTh column="description" label="Deskripsi" :sort="currentSort" :direction="currentDirection" @sort="sortBy" />
                                <SortableTh column="status" label="Status" :sort="currentSort" :direction="currentDirection" @sort="sortBy" />
                                <SortableTh column="created_at" label="Waktu" :sort="currentSort" :direction="currentDirection" @sort="sortBy" />
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[rgb(var(--m3-color-outline-variant-rgb))]">
                            <tr v-for="log in logs.data" :key="log.id" class="transition-colors hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]">
                                <td class="px-4 py-3 text-xs font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.log_name || '—' }}</td>
                                <td class="px-4 py-3 text-sm text-[rgb(var(--m3-color-on-surface-rgb))]">
                                    <p class="max-w-xs truncate lg:max-w-sm" :title="log.description">{{ log.description || 'Tanpa deskripsi' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['rounded-full px-2.5 py-1 text-xs font-medium capitalize', statusBadge(log.status)]">{{ log.status || 'pending' }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ formatDateTime(log.created_at) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('admin.ai_logs.show', log.id)" class="inline-flex rounded-lg p-2 text-[rgb(var(--m3-color-on-surface-variant-rgb))] transition-colors hover:bg-[rgb(var(--m3-color-primary-container-rgb))] hover:text-[rgb(var(--m3-color-on-primary-container-rgb))]" title="Lihat detail">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="logs.data.length === 0" class="px-6 py-16 text-center">
                    <Activity class="mx-auto h-10 w-10 text-[rgb(var(--m3-color-on-surface-variant-rgb))] opacity-40" />
                    <p class="mt-3 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">Tidak ada log AI</p>
                    <p class="mt-1 text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Coba ubah filter atau rentang tanggal.</p>
                </div>

                <AdminPagination :paginator="logs" />
            </div>
        </div>
    </AdminLayout>
</template>
