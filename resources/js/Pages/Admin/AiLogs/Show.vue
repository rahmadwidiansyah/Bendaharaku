<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
    ArrowLeft,
    Activity,
    AlertTriangle,
    CheckCircle2,
    Clock,
    Code2,
    Info,
    RotateCw,
    XCircle,
} from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    log: { type: Object, required: true },
})

const pretty = (value) => {
    if (value === null || value === undefined || value === '') return null
    try {
        return JSON.stringify(typeof value === 'string' ? JSON.parse(value) : value, null, 2)
    } catch {
        return String(value)
    }
}

const properties = computed(() => pretty(props.log.properties))
const changes = computed(() => pretty(props.log.changes))
const raw = computed(() => pretty(props.log))

const statusBadge = computed(() => {
    const palette = {
        success: 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]',
        error: 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]',
        retry: 'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]',
        pending: 'bg-[rgb(var(--m3-color-secondary-container-rgb))] text-[rgb(var(--m3-color-on-secondary-container-rgb))]',
    }
    return palette[props.log.status] || 'bg-[rgb(var(--m3-color-surface-container-high-rgb))] text-[rgb(var(--m3-color-on-surface-variant-rgb))]'
})

const StatusIcon = computed(() => {
    if (props.log.status === 'success') return CheckCircle2
    if (props.log.status === 'error') return XCircle
    if (props.log.status === 'retry') return RotateCw
    return Clock
})

const formatDateTime = (value) =>
    value
        ? new Date(value).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'medium' })
        : '—'
</script>

<template>
    <AdminLayout>
        <Head title="Detail AI Log" />

        <div class="space-y-6 p-4 sm:p-6">
            <Link :href="route('admin.ai_logs.index')" class="inline-flex items-center gap-2 text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] hover:text-[rgb(var(--m3-color-primary-rgb))]">
                <ArrowLeft class="h-4 w-4" /> Kembali ke AI Logs
            </Link>

            <div class="flex flex-col gap-4 rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]">
                        <Activity class="h-6 w-6" />
                    </span>
                    <div>
                        <h1 class="text-2xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.log_name || 'AI Log' }}</h1>
                        <p class="text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Log #{{ log.id }} · {{ formatDateTime(log.created_at) }}</p>
                    </div>
                </div>
                <span :class="['inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold capitalize', statusBadge]">
                    <component :is="StatusIcon" class="h-3.5 w-3.5" />
                    {{ log.status || 'pending' }}
                </span>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <section class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Ringkasan</h2>
                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Log name</dt>
                                <dd class="mt-1 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.log_name || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Log ID</dt>
                                <dd class="mt-1 font-mono text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">#{{ log.id }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Deskripsi</dt>
                                <dd class="mt-1 text-sm text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.description || 'Tanpa deskripsi' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Waktu</dt>
                                <dd class="mt-1 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatDateTime(log.created_at) }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section v-if="properties" class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                        <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]"><Code2 class="h-4 w-4" /> Properties</h2>
                        <pre class="max-h-72 overflow-auto rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-high-rgb))] p-4 text-xs text-[rgb(var(--m3-color-on-surface-rgb))]">{{ properties }}</pre>
                    </section>

                    <section v-if="changes" class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                        <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]"><RotateCw class="h-4 w-4" /> Changes</h2>
                        <pre class="max-h-72 overflow-auto rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-high-rgb))] p-4 text-xs text-[rgb(var(--m3-color-on-surface-rgb))]">{{ changes }}</pre>
                    </section>
                </div>

                <div class="space-y-4">
                    <section class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Quick info</h2>
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Log ID</dt>
                                <dd class="font-mono text-[rgb(var(--m3-color-on-surface-rgb))]">#{{ log.id }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Status</dt>
                                <dd class="font-medium capitalize text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.status }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Log name</dt>
                                <dd class="text-right text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.log_name || '—' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-2xl border border-[rgb(var(--m3-color-secondary-container-rgb))] bg-[rgb(var(--m3-color-secondary-container-rgb))] p-4 text-sm text-[rgb(var(--m3-color-on-secondary-container-rgb))]">
                        <div class="flex items-start gap-2">
                            <Info class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>Log ini untuk operasional AI. Data sensitif dan secret provider tidak ditampilkan di panel admin.</p>
                        </div>
                    </section>
                </div>
            </div>

            <section v-if="raw" class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]"><AlertTriangle class="h-4 w-4" /> Raw JSON</h2>
                <pre class="max-h-80 overflow-auto rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-high-rgb))] p-4 text-xs text-[rgb(var(--m3-color-on-surface-rgb))]">{{ raw }}</pre>
            </section>
        </div>
    </AdminLayout>
</template>
