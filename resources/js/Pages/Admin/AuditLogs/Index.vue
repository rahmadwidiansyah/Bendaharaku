<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
    CheckCircle2,
    Eye,
    Filter,
    History,
    RotateCcw,
    XCircle,
} from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SortableTh from '@/Components/Admin/SortableTh.vue'
import AdminPagination from '@/Components/Admin/AdminPagination.vue'
import { useAdminTable } from '@/Composables/useAdminTable'

const props = defineProps({
    logs: { type: Object, required: true },
    actions: { type: Array, default: () => [] },
    admins: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const { query, navigate, sortBy, reset } = useAdminTable()

const action = ref(props.filters.action || '')
const adminId = ref(props.filters.admin_id || '')
const success = ref(props.filters.success ?? '')
const fromDate = ref(props.filters.from_date || '')
const toDate = ref(props.filters.to_date || '')
const perPage = ref(Number(props.filters.per_page || 20))

const currentSort = computed(() => query.value.sort)
const currentDirection = computed(() => query.value.direction || 'desc')

const activeFilterCount = computed(
    () => [action.value, adminId.value, success.value, fromDate.value, toDate.value].filter((value) => value !== '').length,
)

const applyFilters = () => {
    navigate({
        action: action.value,
        admin_id: adminId.value,
        success: success.value,
        from_date: fromDate.value,
        to_date: toDate.value,
        per_page: perPage.value,
    })
}

const changePerPage = () => navigate({ per_page: perPage.value })

const clearFilters = () => {
    action.value = ''
    adminId.value = ''
    success.value = ''
    fromDate.value = ''
    toDate.value = ''
    perPage.value = 20
    reset()
}

const formatAction = (value) =>
    String(value || '')
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')

const actionBadge = (value) => {
    const palette = {
        user_status_changed: 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]',
        user_viewed: 'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]',
        support_search: 'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]',
        support_view_user: 'bg-[rgb(var(--m3-color-secondary-container-rgb))] text-[rgb(var(--m3-color-on-secondary-container-rgb))]',
        support_access_logged: 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]',
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
        <Head title="Audit Logs" />

        <div class="space-y-6 p-4 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[rgb(var(--m3-color-primary-rgb))]">Administration</p>
                    <h1 class="mt-1 text-2xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))] sm:text-3xl">Audit Logs</h1>
                    <p class="mt-1 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Jejak semua aksi administratif dan perubahan data sensitif.</p>
                </div>
                <div class="flex items-center gap-2 rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] px-4 py-3">
                    <History class="h-5 w-5 text-[rgb(var(--m3-color-primary-rgb))]" />
                    <div>
                        <p class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Total log</p>
                        <p class="text-lg font-bold leading-none text-[rgb(var(--m3-color-on-surface-rgb))]">{{ logs.total }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-4 sm:p-5">
                <form class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12" @submit.prevent="applyFilters">
                    <div class="xl:col-span-3">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Aksi</label>
                        <select v-model="action" class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-3 py-2.5 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]">
                            <option value="">Semua aksi</option>
                            <option v-for="item in actions" :key="item" :value="item">{{ formatAction(item) }}</option>
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Admin</label>
                        <select v-model="adminId" class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-3 py-2.5 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]">
                            <option value="">Semua admin</option>
                            <option v-for="item in admins" :key="item.id" :value="item.id">{{ item.name }}</option>
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Hasil</label>
                        <select v-model="success" class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-3 py-2.5 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]">
                            <option value="">Semua</option>
                            <option value="1">Berhasil</option>
                            <option value="0">Gagal</option>
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Dari</label>
                        <input v-model="fromDate" type="date" class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-3 py-2.5 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]" />
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Sampai</label>
                        <input v-model="toDate" type="date" class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-3 py-2.5 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]" />
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
                                <SortableTh column="admin" label="Admin" :sort="currentSort" :direction="currentDirection" @sort="sortBy" />
                                <SortableTh column="action" label="Aksi" :sort="currentSort" :direction="currentDirection" @sort="sortBy" />
                                <SortableTh column="target_type" label="Target" :sort="currentSort" :direction="currentDirection" @sort="sortBy" />
                                <SortableTh column="success" label="Hasil" :sort="currentSort" :direction="currentDirection" @sort="sortBy" />
                                <SortableTh column="created_at" label="Waktu" :sort="currentSort" :direction="currentDirection" @sort="sortBy" />
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[rgb(var(--m3-color-outline-variant-rgb))]">
                            <tr v-for="log in logs.data" :key="log.id" class="transition-colors hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.admin?.name || log.admin_name || 'Unknown' }}</p>
                                    <p class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ log.admin?.email }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-block rounded-full px-2.5 py-1 text-xs font-medium', actionBadge(log.action)]">{{ formatAction(log.action) }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <p class="text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.target_type || '—' }}</p>
                                    <p class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">#{{ log.target_id ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium', log.success ? 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]' : 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]']">
                                        <CheckCircle2 v-if="log.success" class="h-3 w-3" />
                                        <XCircle v-else class="h-3 w-3" />
                                        {{ log.success ? 'Berhasil' : 'Gagal' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ formatDateTime(log.created_at) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('admin.audit_logs.show', log.id)" class="inline-flex rounded-lg p-2 text-[rgb(var(--m3-color-on-surface-variant-rgb))] transition-colors hover:bg-[rgb(var(--m3-color-primary-container-rgb))] hover:text-[rgb(var(--m3-color-on-primary-container-rgb))]" title="Lihat detail">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="logs.data.length === 0" class="px-6 py-16 text-center">
                    <History class="mx-auto h-10 w-10 text-[rgb(var(--m3-color-on-surface-variant-rgb))] opacity-40" />
                    <p class="mt-3 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">Tidak ada audit log</p>
                    <p class="mt-1 text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Coba longgarkan filter atau reset.</p>
                </div>

                <AdminPagination :paginator="logs" />
            </div>
        </div>
    </AdminLayout>
</template>
