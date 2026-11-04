<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
    AlertTriangle,
    ArrowLeft,
    CheckCircle2,
    Clock,
    Code2,
    Info,
    User,
    Wrench,
    XCircle,
} from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    log: { type: Object, required: true },
})

const params = computed(() => {
    if (props.log.params === null || props.log.params === undefined) return null
    try {
        return JSON.stringify(typeof props.log.params === 'string' ? JSON.parse(props.log.params) : props.log.params, null, 2)
    } catch {
        return String(props.log.params)
    }
})

const raw = computed(() => {
    try {
        return JSON.stringify({ ...props.log, params: props.log.params }, null, 2)
    } catch {
        return String(props.log)
    }
})

const statusBadge = computed(() => {
    const palette = {
        success: 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]',
        error: 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]',
        validation_error: 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]',
        exception: 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]',
        timeout: 'bg-[rgb(var(--m3-color-secondary-container-rgb))] text-[rgb(var(--m3-color-on-secondary-container-rgb))]',
    }
    return palette[props.log.status] || 'bg-[rgb(var(--m3-color-surface-container-high-rgb))] text-[rgb(var(--m3-color-on-surface-variant-rgb))]'
})

const StatusIcon = computed(() => {
    if (props.log.status === 'success') return CheckCircle2
    if (props.log.status === 'error' || props.log.status === 'exception') return XCircle
    return Clock
})

const formatDateTime = (value) =>
    value
        ? new Date(value).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'medium' })
        : '—'
</script>

<template>
    <AdminLayout>
        <Head title="Detail AI Tool Log" />

        <div class="space-y-6 p-4 sm:p-6">
            <Link :href="route('admin.ai_tool_logs.index')" class="inline-flex items-center gap-2 text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] hover:text-[rgb(var(--m3-color-primary-rgb))]">
                <ArrowLeft class="h-4 w-4" /> Kembali ke AI Tool Logs
            </Link>

            <div class="flex flex-col gap-4 rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]">
                        <Wrench class="h-6 w-6" />
                    </span>
                    <div>
                        <h1 class="text-2xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.tool }}</h1>
                        <p class="text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Log #{{ log.id }} · {{ formatDateTime(log.created_at) }}</p>
                    </div>
                </div>
                <span :class="['inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold capitalize', statusBadge]">
                    <component :is="StatusIcon" class="h-3.5 w-3.5" />
                    {{ (log.status || '—').replace('_', ' ') }}
                </span>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <section class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Ringkasan</h2>
                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Tool</dt>
                                <dd class="mt-1 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.tool }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Trace ID</dt>
                                <dd class="mt-1 break-all font-mono text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.trace_id }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">User</dt>
                                <dd class="mt-1 flex items-center gap-1.5 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">
                                    <User class="h-3.5 w-3.5" /> {{ log.user_email || ('#'+log.user_id) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Durasi eksekusi</dt>
                                <dd class="mt-1 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.duration_ms != null ? log.duration_ms + ' ms' : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Jumlah baris hasil</dt>
                                <dd class="mt-1 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.result_rows ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Waktu</dt>
                                <dd class="mt-1 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatDateTime(log.created_at) }}</dd>
                            </div>
                            <div v-if="log.error_message" class="sm:col-span-2">
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Pesan error</dt>
                                <dd class="mt-1 rounded-xl border border-[rgb(var(--m3-color-error-container-rgb))] bg-[rgb(var(--m3-color-error-container-rgb))] p-3 text-sm text-[rgb(var(--m3-color-on-error-container-rgb))]">{{ log.error_message }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section v-if="params" class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                        <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]"><Code2 class="h-4 w-4" /> Parameter tool</h2>
                        <pre class="max-h-72 overflow-auto rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-high-rgb))] p-4 text-xs text-[rgb(var(--m3-color-on-surface-rgb))]">{{ params }}</pre>
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
                                <dd class="font-medium capitalize text-[rgb(var(--m3-color-on-surface-rgb))]">{{ (log.status || '—').replace('_', ' ') }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Tool</dt>
                                <dd class="text-right text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.tool }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-2xl border border-[rgb(var(--m3-color-secondary-container-rgb))] bg-[rgb(var(--m3-color-secondary-container-rgb))] p-4 text-sm text-[rgb(var(--m3-color-on-secondary-container-rgb))]">
                        <div class="flex items-start gap-2">
                            <Info class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>Log ini untuk audit eksekusi tool assistant. Parameter berisi argumen dari LLM (tanggal, id, limit) — bukan secret provider.</p>
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
