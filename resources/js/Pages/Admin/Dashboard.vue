<script setup>
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
    Activity,
    AlertTriangle,
    ArrowUpRight,
    CalendarDays,
    CheckCircle2,
    Clock,
    Database,
    ReceiptText,
    ServerCog,
    ShieldAlert,
    UserCheck,
    UserPlus,
    Users,
} from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AdminChart from '@/Components/Admin/AdminChart.vue'

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    charts: { type: Object, default: () => ({}) },
    attention: { type: Array, default: () => [] },
    recent_users: { type: Array, default: () => [] },
    recent_audit: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
    system: { type: Object, default: () => ({}) },
    range: { type: Number, default: 30 },
    generated_at: { type: String, default: null },
    admin_user: { type: Object, default: null },
})

const ranges = [
    { value: 7, label: '7 hari' },
    { value: 30, label: '30 hari' },
    { value: 90, label: '90 hari' },
]

const changeRange = (range) => {
    router.get(route('admin.dashboard'), { range }, { preserveScroll: true, preserveState: true })
}

const labels = computed(() =>
    (props.charts.labels ?? []).map((value) =>
        new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }),
    ),
)

const activeSeries = computed(() => [
    { label: 'User aktif', data: props.charts.active ?? [], role: 'primary' },
])

const transactionSeries = computed(() => [
    { label: 'Transaksi', data: props.charts.transactions ?? [], role: 'tertiary' },
])

const newUserSeries = computed(() => [
    { label: 'User baru', data: props.charts.new_users ?? [], role: 'secondary' },
])

const roleLabels = computed(() => (props.roles ?? []).map((item) => item.role))
const roleSeries = computed(() => [
    { label: 'User', data: (props.roles ?? []).map((item) => item.total) },
])

const attentionCount = computed(
    () => (props.attention ?? []).filter((item) => item.severity !== 'ok' && item.value > 0).length,
)

const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(Number(value ?? 0))

const formatDateTime = (value) =>
    value
        ? new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
        : '—'

const relativeTime = (value) => {
    if (!value) return '—'
    const diff = Date.now() - new Date(value).getTime()
    const minutes = Math.max(0, Math.round(diff / 60000))
    if (minutes < 1) return 'Baru saja'
    if (minutes < 60) return `${minutes} menit lalu`
    const hours = Math.round(minutes / 60)
    if (hours < 24) return `${hours} jam lalu`
    return `${Math.round(hours / 24)} hari lalu`
}

const severityClass = (severity) => {
    if (severity === 'critical') {
        return 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]'
    }
    if (severity === 'warning') {
        return 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]'
    }
    return 'bg-[rgb(var(--m3-color-surface-container-high-rgb))] text-[rgb(var(--m3-color-on-surface-variant-rgb))]'
}
</script>

<template>
    <AdminLayout>
        <Head title="Admin Dashboard" />

        <div class="space-y-6 p-4 sm:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[rgb(var(--m3-color-primary-rgb))]">Overview</p>
                    <h1 class="mt-1 text-2xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))] sm:text-3xl">Admin Dashboard</h1>
                    <p class="mt-1 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                        Ringkasan kesehatan produk, aktivitas user, dan operasional AI.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-1">
                        <button
                            v-for="item in ranges"
                            :key="item.value"
                            type="button"
                            :class="[
                                'rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors',
                                range === item.value
                                    ? 'bg-[rgb(var(--m3-color-primary-rgb))] text-[rgb(var(--m3-color-on-primary-rgb))]'
                                    : 'text-[rgb(var(--m3-color-on-surface-variant-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]',
                            ]"
                            @click="changeRange(item.value)"
                        >
                            {{ item.label }}
                        </button>
                    </div>
                    <div class="flex items-center gap-1.5 rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] px-3 py-2 text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                        <Clock class="h-3.5 w-3.5" />
                        Update {{ relativeTime(generated_at) }}
                    </div>
                </div>
            </div>

            <!-- KPI cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="relative overflow-hidden rounded-2xl border border-[rgb(var(--m3-color-primary-container-rgb))] bg-[rgb(var(--m3-color-primary-container-rgb))] p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-primary-container-rgb))] opacity-80">MAU (30 hari)</p>
                            <p class="mt-2 text-3xl font-black leading-none text-[rgb(var(--m3-color-on-primary-container-rgb))]">{{ formatNumber(stats.mau) }}</p>
                            <p class="mt-2 text-xs font-medium text-[rgb(var(--m3-color-on-primary-container-rgb))] opacity-80">
                                DAU {{ formatNumber(stats.dau) }} · WAU {{ formatNumber(stats.wau) }} · Online {{ formatNumber(stats.online_now) }}
                            </p>
                        </div>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[rgb(var(--m3-color-primary-rgb))] text-[rgb(var(--m3-color-on-primary-rgb))]">
                            <UserCheck class="h-5 w-5" />
                        </span>
                    </div>
                    <p class="mt-4 text-[11px] text-[rgb(var(--m3-color-on-primary-container-rgb))] opacity-70">
                        Exclude staff &amp; user ditangguhkan.
                    </p>
                </div>

                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Total customer</p>
                            <p class="mt-2 text-3xl font-black leading-none text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatNumber(stats.total_users) }}</p>
                            <p class="mt-2 text-xs font-medium text-[rgb(var(--m3-color-tertiary-rgb))]">+{{ formatNumber(stats.new_users) }} baru dalam {{ range }} hari</p>
                        </div>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[rgb(var(--m3-color-surface-container-high-rgb))] text-[rgb(var(--m3-color-tertiary-rgb))]">
                            <Users class="h-5 w-5" />
                        </span>
                    </div>
                    <p class="mt-4 text-[11px] text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                        {{ formatNumber(stats.verified_users) }} terverifikasi · {{ formatNumber(stats.suspended_users) }} suspended
                    </p>
                </div>

                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Transaksi {{ range }} hari</p>
                            <p class="mt-2 text-3xl font-black leading-none text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatNumber(stats.transactions_range) }}</p>
                            <p class="mt-2 text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Total {{ formatNumber(stats.transactions_total) }} sepanjang waktu</p>
                        </div>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[rgb(var(--m3-color-surface-container-high-rgb))] text-[rgb(var(--m3-color-secondary-rgb))]">
                            <ReceiptText class="h-5 w-5" />
                        </span>
                    </div>
                    <p class="mt-4 text-[11px] text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Dihitung dari transaction_logs.</p>
                </div>

                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Perlu perhatian</p>
                            <p class="mt-2 text-3xl font-black leading-none text-[rgb(var(--m3-color-on-surface-rgb))]">{{ attentionCount }}</p>
                            <p class="mt-2 text-xs font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ formatNumber(system.queue_failed) }} failed job · {{ formatNumber(stats.failed_ai_24h) }} AI error</p>
                        </div>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]">
                            <ShieldAlert class="h-5 w-5" />
                        </span>
                    </div>
                    <p class="mt-4 text-[11px] text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Cek panel perhatian di bawah.</p>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">Aktivitas harian</h2>
                            <p class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">User unik aktif per hari ({{ range }} hari terakhir)</p>
                        </div>
                        <CalendarDays class="h-4 w-4 text-[rgb(var(--m3-color-primary-rgb))]" />
                    </div>
                    <AdminChart type="line" :labels="labels" :series="activeSeries" :height="250" y-label=" user" />
                </div>

                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">Transaksi &amp; user baru</h2>
                            <p class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Volume harian dalam rentang terpilih</p>
                        </div>
                        <Activity class="h-4 w-4 text-[rgb(var(--m3-color-tertiary-rgb))]" />
                    </div>
                    <AdminChart type="bar" :labels="labels" :series="[...transactionSeries, ...newUserSeries]" :height="250" legend />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <!-- Attention -->
                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">Perlu perhatian</h2>
                        <AlertTriangle class="h-4 w-4 text-[rgb(var(--m3-color-tertiary-rgb))]" />
                    </div>
                    <ul class="space-y-2">
                        <li v-for="item in attention" :key="item.key">
                            <component
                                :is="item.href ? Link : 'div'"
                                :href="item.href || undefined"
                                :class="['flex items-center justify-between gap-3 rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] px-3 py-2.5 transition-colors', item.href ? 'hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]' : '']"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    <span :class="['flex h-7 w-7 shrink-0 items-center justify-center rounded-full', severityClass(item.severity)]">
                                        <CheckCircle2 v-if="item.severity === 'ok'" class="h-3.5 w-3.5" />
                                        <AlertTriangle v-else class="h-3.5 w-3.5" />
                                    </span>
                                    <span class="truncate text-sm text-[rgb(var(--m3-color-on-surface-rgb))]">{{ item.label }}</span>
                                </div>
                                <span class="text-sm font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatNumber(item.value) }}</span>
                            </component>
                        </li>
                    </ul>
                </div>

                <!-- Recent users -->
                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">User terbaru</h2>
                        <UserPlus class="h-4 w-4 text-[rgb(var(--m3-color-primary-rgb))]" />
                    </div>
                    <ul class="space-y-2">
                        <li v-for="item in recent_users" :key="item.id">
                            <Link :href="route('admin.users.show', item.id)" class="flex items-center justify-between gap-3 rounded-xl px-2 py-2 transition-colors hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ item.name }}</p>
                                    <p class="truncate text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ item.email }}</p>
                                </div>
                                <span :class="['rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize', item.status === 'suspended' ? 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]' : 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]']">{{ item.status }}</span>
                            </Link>
                        </li>
                        <li v-if="recent_users.length === 0" class="px-2 py-6 text-center text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Belum ada user.</li>
                    </ul>
                </div>

                <!-- Role distribution -->
                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">Distribusi role</h2>
                        <ServerCog class="h-4 w-4 text-[rgb(var(--m3-color-secondary-rgb))]" />
                    </div>
                    <AdminChart v-if="roles.length > 0" type="doughnut" :labels="roleLabels" :series="roleSeries" :height="210" />
                    <p v-else class="px-2 py-10 text-center text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Belum ada role.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <!-- Recent audit -->
                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">Audit terbaru</h2>
                        <Link :href="route('admin.audit_logs.index')" class="inline-flex items-center gap-1 text-xs font-semibold text-[rgb(var(--m3-color-primary-rgb))] hover:underline">
                            Semua <ArrowUpRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                    <ul class="space-y-2">
                        <li v-for="log in recent_audit" :key="log.id">
                            <Link :href="route('admin.audit_logs.show', log.id)" class="flex items-center justify-between gap-3 rounded-xl px-2 py-2 transition-colors hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium capitalize text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.action?.replaceAll('_', ' ') }}</p>
                                    <p class="truncate text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ log.admin || 'Unknown' }} · {{ log.target }}</p>
                                </div>
                                <span class="shrink-0 text-[10px] text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ relativeTime(log.created_at) }}</span>
                            </Link>
                        </li>
                        <li v-if="recent_audit.length === 0" class="px-2 py-6 text-center text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Belum ada audit log.</li>
                    </ul>
                </div>

                <!-- System -->
                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">Kesehatan sistem</h2>
                        <Database class="h-4 w-4 text-[rgb(var(--m3-color-primary-rgb))]" />
                    </div>
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl bg-[rgb(var(--m3-color-surface-container-high-rgb))] p-3">
                            <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Queue pending</dt>
                            <dd class="mt-1 text-lg font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatNumber(system.queue_pending) }}</dd>
                        </div>
                        <div class="rounded-xl bg-[rgb(var(--m3-color-surface-container-high-rgb))] p-3">
                            <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Queue failed</dt>
                            <dd class="mt-1 text-lg font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatNumber(system.queue_failed) }}</dd>
                        </div>
                        <div class="rounded-xl bg-[rgb(var(--m3-color-surface-container-high-rgb))] p-3">
                            <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">AI error 24 jam</dt>
                            <dd class="mt-1 text-lg font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatNumber(stats.failed_ai_24h) }}</dd>
                        </div>
                        <div class="rounded-xl bg-[rgb(var(--m3-color-surface-container-high-rgb))] p-3">
                            <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Aktivitas terakhir</dt>
                            <dd class="mt-1 text-xs font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatDateTime(system.last_activity_at) }}</dd>
                        </div>
                    </dl>
                    <p v-if="system.last_failed_job_at" class="mt-3 text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                        Failed job terakhir: {{ formatDateTime(system.last_failed_job_at) }}
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-[rgb(var(--m3-color-primary-container-rgb))] bg-[rgb(var(--m3-color-primary-container-rgb))] p-4 text-sm text-[rgb(var(--m3-color-on-primary-container-rgb))]">
                Login sebagai <strong>{{ admin_user?.name }}</strong> ({{ admin_user?.email }}). Metrik active user memakai <code>last_active_at</code> dan tabel <code>user_activity_daily</code>.
            </div>
        </div>
    </AdminLayout>
</template>
