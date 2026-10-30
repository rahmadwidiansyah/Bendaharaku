<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import {
    Ban,
    CheckCircle2,
    ChevronDown,
    Columns3,
    Eye,
    Filter,
    RotateCcw,
    Search,
    ShieldCheck,
    Users as UsersIcon,
} from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SortableTh from '@/Components/Admin/SortableTh.vue'
import AdminPagination from '@/Components/Admin/AdminPagination.vue'
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue'
import { useAdminTable } from '@/Composables/useAdminTable'

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    roles: { type: Array, default: () => [] },
})

const page = usePage()
const actor = computed(() => page.props.auth?.user)
const actorRoles = computed(() => page.props.auth?.roles ?? [])

const { query, navigate, sortBy, reset } = useAdminTable()

const search = ref(props.filters.search || '')
const status = ref(props.filters.status || '')
const activity = ref(props.filters.activity || '')
const role = ref(props.filters.role || '')
const perPage = ref(Number(props.filters.per_page || 15))

const currentSort = computed(() => query.value.sort)
const currentDirection = computed(() => query.value.direction || 'desc')

const columns = [
    { key: 'name', label: 'User', locked: true },
    { key: 'role', label: 'Role' },
    { key: 'status', label: 'Status' },
    { key: 'verified', label: 'Verifikasi' },
    { key: 'last_active', label: 'Terakhir aktif' },
    { key: 'joined', label: 'Bergabung' },
    { key: 'transactions', label: 'Transaksi' },
]

const visibleColumns = ref(Object.fromEntries(columns.map((column) => [column.key, true])))
const columnsOpen = ref(false)

onMounted(() => {
    try {
        const saved = JSON.parse(localStorage.getItem('admin.users.columns') || 'null')
        if (saved && typeof saved === 'object') {
            visibleColumns.value = { ...visibleColumns.value, ...saved, name: true }
        }
    } catch {
        /* abaikan localStorage rusak */
    }
})

watch(
    visibleColumns,
    (value) => {
        try {
            localStorage.setItem('admin.users.columns', JSON.stringify(value))
        } catch {
            /* storage penuh / disabled */
        }
    },
    { deep: true },
)

const isVisible = (key) => visibleColumns.value[key] !== false

const activeFilterCount = computed(
    () => [search.value, status.value, activity.value, role.value].filter(Boolean).length,
)

const applyFilters = () => {
    selected.value = []
    navigate({
        search: search.value,
        status: status.value,
        activity: activity.value,
        role: role.value,
        per_page: perPage.value,
    })
}

const changePerPage = () => {
    navigate({ per_page: perPage.value })
}

const clearFilters = () => {
    search.value = ''
    status.value = ''
    activity.value = ''
    role.value = ''
    perPage.value = 15
    selected.value = []
    reset()
}

const selected = ref([])
const allSelected = computed(
    () => props.users.data.length > 0 && selected.value.length === props.users.data.length,
)

const toggleAll = () => {
    selected.value = allSelected.value ? [] : props.users.data.map((user) => user.id)
}

const toggleOne = (id) => {
    selected.value = selected.value.includes(id)
        ? selected.value.filter((item) => item !== id)
        : [...selected.value, id]
}

const modal = ref({
    open: false,
    mode: 'single',
    status: 'suspended',
    user: null,
})
const reason = ref('')
const reasonError = ref('')

const form = useForm({
    ids: [],
    status: 'suspended',
    reason: '',
})

const askStatus = (user, nextStatus) => {
    reason.value = ''
    reasonError.value = ''
    form.clearErrors()
    modal.value = { open: true, mode: 'single', status: nextStatus, user }
}

const askBulk = (nextStatus) => {
    reason.value = ''
    reasonError.value = ''
    form.clearErrors()
    modal.value = { open: true, mode: 'bulk', status: nextStatus, user: null }
}

const modalTitle = computed(() => {
    if (modal.value.mode === 'bulk') {
        return modal.value.status === 'suspended'
            ? `Suspend ${selected.value.length} user?`
            : `Aktifkan ${selected.value.length} user?`
    }

    return modal.value.status === 'suspended'
        ? `Suspend ${modal.value.user?.name}?`
        : `Aktifkan ${modal.value.user?.name}?`
})

const confirmStatus = () => {
    reasonError.value = ''

    if (modal.value.status === 'suspended' && !reason.value.trim()) {
        reasonError.value = 'Alasan suspend wajib diisi untuk audit log.'
        return
    }

    form.status = modal.value.status
    form.reason = reason.value.trim()
    form.ids = modal.value.mode === 'bulk' ? selected.value : [modal.value.user.id]

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            modal.value.open = false
            selected.value = []
        },
    }

    if (modal.value.mode === 'bulk') {
        form.post(route('admin.users.bulk_status'), options)
    } else {
        form.post(route('admin.users.update_status', modal.value.user.id), options)
    }
}

const statusBadge = (value) =>
    value === 'suspended'
        ? 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]'
        : 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]'

const verificationBadge = (verifiedAt) =>
    verifiedAt
        ? 'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]'
        : 'bg-[rgb(var(--m3-color-surface-container-high-rgb))] text-[rgb(var(--m3-color-on-surface-variant-rgb))]'

const roleBadge = (name) => {
    const palette = {
        owner: 'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]',
        admin: 'bg-[rgb(var(--m3-color-secondary-container-rgb))] text-[rgb(var(--m3-color-on-secondary-container-rgb))]',
        support: 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]',
    }
    return palette[name] || 'bg-[rgb(var(--m3-color-surface-container-high-rgb))] text-[rgb(var(--m3-color-on-surface-variant-rgb))]'
}

const userRole = (user) => user.roles?.[0]?.name || 'user'

const canModify = (user) => {
    if (!user || user.id === actor.value?.id) return false
    if (actorRoles.value.includes('owner')) return true
    if (userRole(user) === 'owner') return false
    if (actorRoles.value.includes('admin')) return true
    return userRole(user) === 'user'
}

const formatDate = (value) => (value ? new Date(value).toLocaleDateString('id-ID') : '—')

const relativeTime = (value) => {
    if (!value) return 'Belum pernah'
    const diff = Date.now() - new Date(value).getTime()
    const minutes = Math.round(diff / 60000)
    if (minutes < 1) return 'Baru saja'
    if (minutes < 60) return `${minutes} menit lalu`
    const hours = Math.round(minutes / 60)
    if (hours < 24) return `${hours} jam lalu`
    const days = Math.round(hours / 24)
    if (days < 30) return `${days} hari lalu`
    return new Date(value).toLocaleDateString('id-ID')
}
</script>

<template>
    <AdminLayout>
        <Head title="User Management" />

        <div class="space-y-6 p-4 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[rgb(var(--m3-color-primary-rgb))]">Administration</p>
                    <h1 class="mt-1 text-2xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))] sm:text-3xl">User Management</h1>
                    <p class="mt-1 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                        Kelola akun, pantau aktivitas, dan tangguhkan akses bila diperlukan.
                    </p>
                </div>
                <div class="flex items-center gap-2 rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] px-4 py-3">
                    <UsersIcon class="h-5 w-5 text-[rgb(var(--m3-color-primary-rgb))]" />
                    <div>
                        <p class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Hasil filter</p>
                        <p class="text-lg font-bold leading-none text-[rgb(var(--m3-color-on-surface-rgb))]">{{ users.total }}</p>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-4 sm:p-5">
                <form class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12" @submit.prevent="applyFilters">
                    <div class="xl:col-span-4">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Cari</label>
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[rgb(var(--m3-color-on-surface-variant-rgb))]" />
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Nama, email, Telegram, WhatsApp…"
                                class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] py-2.5 pl-9 pr-3 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] placeholder:text-[rgb(var(--m3-color-on-surface-variant-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]"
                            />
                        </div>
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Status</label>
                        <select v-model="status" class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-3 py-2.5 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]">
                            <option value="">Semua</option>
                            <option value="active">Aktif</option>
                            <option value="suspended">Ditangguhkan</option>
                            <option value="verified">Terverifikasi</option>
                            <option value="unverified">Belum verifikasi</option>
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Aktivitas</label>
                        <select v-model="activity" class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-3 py-2.5 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]">
                            <option value="">Semua</option>
                            <option value="online">Online (5 menit)</option>
                            <option value="7d">7 hari</option>
                            <option value="30d">30 hari</option>
                            <option value="dormant">Dormant &gt; 30 hari</option>
                            <option value="never">Belum pernah</option>
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Role</label>
                        <select v-model="role" class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-3 py-2.5 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]">
                            <option value="">Semua</option>
                            <option v-for="item in roles" :key="item" :value="item">{{ item }}</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 xl:col-span-2">
                        <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-[rgb(var(--m3-color-primary-rgb))] px-4 py-2.5 text-sm font-semibold text-[rgb(var(--m3-color-on-primary-rgb))] transition-colors hover:bg-[rgb(var(--m3-color-primary-container-rgb))] hover:text-[rgb(var(--m3-color-on-primary-container-rgb))]">
                            <Filter class="h-4 w-4" /> Terapkan
                        </button>
                        <button type="button" class="rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] p-2.5 text-[rgb(var(--m3-color-on-surface-variant-rgb))] transition-colors hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]" title="Reset filter" @click="clearFilters">
                            <RotateCcw class="h-4 w-4" />
                        </button>
                    </div>
                </form>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                    <span v-if="activeFilterCount > 0" class="inline-flex items-center gap-1.5 rounded-full bg-[rgb(var(--m3-color-primary-container-rgb))] px-3 py-1 font-medium text-[rgb(var(--m3-color-on-primary-container-rgb))]">
                        {{ activeFilterCount }} filter aktif
                    </span>
                    <span v-else>Semua user ditampilkan.</span>

                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2">
                            <span>Baris</span>
                            <select v-model.number="perPage" class="rounded-lg border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-2 py-1 text-xs text-[rgb(var(--m3-color-on-surface-rgb))]" @change="changePerPage">
                                <option v-for="size in [10, 15, 25, 50, 100]" :key="size" :value="size">{{ size }}</option>
                            </select>
                        </label>

                        <div class="relative">
                            <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-[rgb(var(--m3-color-outline-variant-rgb))] px-3 py-1.5 font-medium transition-colors hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]" @click="columnsOpen = !columnsOpen">
                                <Columns3 class="h-3.5 w-3.5" /> Kolom <ChevronDown class="h-3.5 w-3.5" />
                            </button>
                            <div v-if="columnsOpen" class="absolute right-0 z-20 mt-2 w-52 rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-high-rgb))] p-2 shadow-xl">
                                <label v-for="column in columns" :key="column.key" class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]" :class="{ 'opacity-50': column.locked }">
                                    <input v-model="visibleColumns[column.key]" type="checkbox" :disabled="column.locked" class="rounded border-[rgb(var(--m3-color-outline-variant-rgb))] text-[rgb(var(--m3-color-primary-rgb))]" />
                                    <span class="text-[rgb(var(--m3-color-on-surface-rgb))]">{{ column.label }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk bar -->
            <div v-if="selected.length > 0" class="flex flex-col gap-3 rounded-2xl border border-[rgb(var(--m3-color-primary-rgb))] bg-[rgb(var(--m3-color-primary-container-rgb))] p-4 text-[rgb(var(--m3-color-on-primary-container-rgb))] sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-semibold">{{ selected.length }} user dipilih</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[rgb(var(--m3-color-tertiary-rgb))] px-3 py-2 text-xs font-semibold text-[rgb(var(--m3-color-on-tertiary-rgb))]" @click="askBulk('active')">
                        <CheckCircle2 class="h-4 w-4" /> Aktifkan
                    </button>
                    <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[rgb(var(--m3-color-error-rgb))] px-3 py-2 text-xs font-semibold text-[rgb(var(--m3-color-on-error-rgb))]" @click="askBulk('suspended')">
                        <Ban class="h-4 w-4" /> Suspend
                    </button>
                    <button type="button" class="rounded-xl border border-[rgb(var(--m3-color-outline-rgb))] px-3 py-2 text-xs font-semibold" @click="selected = []">Batal</button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))]">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px]">
                        <thead class="border-b border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-high-rgb))]">
                            <tr>
                                <th class="w-12 px-4 py-3 text-left">
                                    <input type="checkbox" :checked="allSelected" aria-label="Pilih semua user di halaman ini" class="rounded border-[rgb(var(--m3-color-outline-rgb))] text-[rgb(var(--m3-color-primary-rgb))]" @change="toggleAll" />
                                </th>
                                <SortableTh v-if="isVisible('name')" column="name" label="User" :sort="currentSort" :direction="currentDirection" @sort="sortBy" />
                                <SortableTh v-if="isVisible('role')" column="role" label="Role" :sort="currentSort" :direction="currentDirection" @sort="sortBy" />
                                <SortableTh v-if="isVisible('status')" column="status" label="Status" :sort="currentSort" :direction="currentDirection" @sort="sortBy" />
                                <SortableTh v-if="isVisible('verified')" column="verified" label="Verifikasi" :sort="currentSort" :direction="currentDirection" @sort="sortBy" />
                                <SortableTh v-if="isVisible('last_active')" column="last_active" label="Terakhir aktif" :sort="currentSort" :direction="currentDirection" @sort="sortBy" />
                                <SortableTh v-if="isVisible('joined')" column="joined" label="Bergabung" :sort="currentSort" :direction="currentDirection" @sort="sortBy" />
                                <SortableTh v-if="isVisible('transactions')" column="transactions" label="Transaksi" :sort="currentSort" :direction="currentDirection" align="right" @sort="sortBy" />
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[rgb(var(--m3-color-outline-variant-rgb))]">
                            <tr v-for="user in users.data" :key="user.id" class="transition-colors hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]">
                                <td class="px-4 py-3">
                                    <input type="checkbox" :checked="selected.includes(user.id)" :aria-label="`Pilih ${user.name}`" class="rounded border-[rgb(var(--m3-color-outline-rgb))] text-[rgb(var(--m3-color-primary-rgb))]" @change="toggleOne(user.id)" />
                                </td>
                                <td v-if="isVisible('name')" class="px-4 py-3">
                                    <Link :href="route('admin.users.show', user.id)" class="font-semibold text-[rgb(var(--m3-color-on-surface-rgb))] hover:text-[rgb(var(--m3-color-primary-rgb))]">
                                        {{ user.name }}
                                    </Link>
                                    <p class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ user.email }}</p>
                                </td>
                                <td v-if="isVisible('role')" class="px-4 py-3">
                                    <span :class="['rounded-full px-2.5 py-1 text-xs font-medium capitalize', roleBadge(userRole(user))]">{{ userRole(user) }}</span>
                                </td>
                                <td v-if="isVisible('status')" class="px-4 py-3">
                                    <span :class="['rounded-full px-2.5 py-1 text-xs font-medium capitalize', statusBadge(user.status)]">{{ user.status || 'active' }}</span>
                                </td>
                                <td v-if="isVisible('verified')" class="px-4 py-3">
                                    <span :class="['inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium', verificationBadge(user.email_verified_at)]">
                                        <ShieldCheck v-if="user.email_verified_at" class="h-3 w-3" />
                                        {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                                    </span>
                                </td>
                                <td v-if="isVisible('last_active')" class="px-4 py-3 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                                    <span :title="user.last_active_at || 'Belum pernah'">{{ relativeTime(user.last_active_at) }}</span>
                                </td>
                                <td v-if="isVisible('joined')" class="px-4 py-3 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                                    {{ formatDate(user.created_at) }}
                                </td>
                                <td v-if="isVisible('transactions')" class="px-4 py-3 text-right text-sm font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">
                                    {{ user.transaction_logs_count ?? 0 }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link :href="route('admin.users.show', user.id)" class="rounded-lg p-2 text-[rgb(var(--m3-color-on-surface-variant-rgb))] transition-colors hover:bg-[rgb(var(--m3-color-primary-container-rgb))] hover:text-[rgb(var(--m3-color-on-primary-container-rgb))]" title="Lihat detail">
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                        <button v-if="canModify(user) && user.status === 'suspended'" type="button" class="rounded-lg p-2 text-[rgb(var(--m3-color-tertiary-rgb))] transition-colors hover:bg-[rgb(var(--m3-color-tertiary-container-rgb))]" title="Aktifkan user" @click="askStatus(user, 'active')">
                                            <CheckCircle2 class="h-4 w-4" />
                                        </button>
                                        <button v-else-if="canModify(user)" type="button" class="rounded-lg p-2 text-[rgb(var(--m3-color-error-rgb))] transition-colors hover:bg-[rgb(var(--m3-color-error-container-rgb))]" title="Suspend user" @click="askStatus(user, 'suspended')">
                                            <Ban class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="users.data.length === 0" class="px-6 py-16 text-center">
                    <UsersIcon class="mx-auto h-10 w-10 text-[rgb(var(--m3-color-on-surface-variant-rgb))] opacity-40" />
                    <p class="mt-3 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">Tidak ada user yang cocok</p>
                    <p class="mt-1 text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Coba ubah kata kunci atau reset filter.</p>
                </div>

                <AdminPagination :paginator="users" />
            </div>
        </div>

        <ConfirmDialog
            v-model:open="modal.open"
            :title="modalTitle"
            :description="modal.status === 'suspended' ? 'User tidak akan bisa login sampai diaktifkan kembali.' : 'User akan bisa login dan mengakses aplikasi kembali.'"
            :confirm-label="modal.status === 'suspended' ? 'Suspend' : 'Aktifkan'"
            :tone="modal.status === 'suspended' ? 'danger' : 'primary'"
            :loading="form.processing"
            @confirm="confirmStatus"
        >
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                Alasan {{ modal.status === 'suspended' ? '(wajib)' : '(opsional)' }}
            </label>
            <textarea
                v-model="reason"
                rows="3"
                maxlength="255"
                placeholder="Contoh: penyalahgunaan akun, permintaan user, dsb."
                class="w-full rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-rgb))] px-3 py-2 text-sm text-[rgb(var(--m3-color-on-surface-rgb))] placeholder:text-[rgb(var(--m3-color-on-surface-variant-rgb))] focus:border-transparent focus:ring-2 focus:ring-[rgb(var(--m3-color-primary-rgb))]"
            />
            <p v-if="reasonError" class="mt-1 text-xs font-medium text-[rgb(var(--m3-color-error-rgb))]">{{ reasonError }}</p>
            <p v-if="form.errors.ids" class="mt-1 text-xs font-medium text-[rgb(var(--m3-color-error-rgb))]">{{ form.errors.ids }}</p>
        </ConfirmDialog>
    </AdminLayout>
</template>
