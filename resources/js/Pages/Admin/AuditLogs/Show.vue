<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
    ArrowLeft,
    CheckCircle2,
    Code2,
    FileClock,
    Globe2,
    Info,
    MonitorSmartphone,
    ShieldCheck,
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

const oldValues = computed(() => pretty(props.log.old_values))
const newValues = computed(() => pretty(props.log.new_values))
const metadata = computed(() => props.log.metadata || {})
const raw = computed(() => pretty(props.log))

const formatAction = (value) =>
    String(value || '')
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')

const formatDateTime = (value) =>
    value
        ? new Date(value).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'medium' })
        : '—'
</script>

<template>
    <AdminLayout>
        <Head title="Detail Audit Log" />

        <div class="space-y-6 p-4 sm:p-6">
            <Link :href="route('admin.audit_logs.index')" class="inline-flex items-center gap-2 text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] hover:text-[rgb(var(--m3-color-primary-rgb))]">
                <ArrowLeft class="h-4 w-4" /> Kembali ke Audit Logs
            </Link>

            <div class="flex flex-col gap-4 rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]">
                        <FileClock class="h-6 w-6" />
                    </span>
                    <div>
                        <h1 class="text-2xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatAction(log.action) }}</h1>
                        <p class="text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ log.target_type || 'Target' }} #{{ log.target_id ?? '—' }} · {{ formatDateTime(log.created_at) }}</p>
                    </div>
                </div>
                <span :class="['inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold', log.success ? 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]' : 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]']">
                    <CheckCircle2 v-if="log.success" class="h-3.5 w-3.5" />
                    <XCircle v-else class="h-3.5 w-3.5" />
                    {{ log.success ? 'Berhasil' : 'Gagal' }}
                </span>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <section class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Ringkasan event</h2>
                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Aksi</dt>
                                <dd class="mt-1 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatAction(log.action) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Target</dt>
                                <dd class="mt-1 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.target_type }} #{{ log.target_id ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Waktu</dt>
                                <dd class="mt-1 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatDateTime(log.created_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Admin</dt>
                                <dd class="mt-1 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.admin?.name || 'Unknown' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section v-if="oldValues || newValues" class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Perubahan</h2>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div v-if="oldValues">
                                <h3 class="mb-1.5 text-xs font-semibold text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Sebelum</h3>
                                <pre class="max-h-64 overflow-auto rounded-xl border border-[rgb(var(--m3-color-error-container-rgb))] bg-[rgb(var(--m3-color-error-container-rgb))] p-3 text-xs text-[rgb(var(--m3-color-on-error-container-rgb))]">{{ oldValues }}</pre>
                            </div>
                            <div v-if="newValues">
                                <h3 class="mb-1.5 text-xs font-semibold text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Sesudah</h3>
                                <pre class="max-h-64 overflow-auto rounded-xl border border-[rgb(var(--m3-color-tertiary-container-rgb))] bg-[rgb(var(--m3-color-tertiary-container-rgb))] p-3 text-xs text-[rgb(var(--m3-color-on-tertiary-container-rgb))]">{{ newValues }}</pre>
                            </div>
                        </div>
                    </section>

                    <section v-if="Object.keys(metadata).length > 0" class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Detail request</h2>
                        <dl class="space-y-4 text-sm">
                            <div v-if="metadata.ip" class="flex items-start gap-3">
                                <Globe2 class="mt-0.5 h-4 w-4 shrink-0 text-[rgb(var(--m3-color-on-surface-variant-rgb))]" />
                                <div>
                                    <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">IP address</dt>
                                    <dd class="font-mono text-[rgb(var(--m3-color-on-surface-rgb))]">{{ metadata.ip }}</dd>
                                </div>
                            </div>
                            <div v-if="metadata.reason" class="flex items-start gap-3">
                                <Info class="mt-0.5 h-4 w-4 shrink-0 text-[rgb(var(--m3-color-on-surface-variant-rgb))]" />
                                <div>
                                    <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Alasan</dt>
                                    <dd class="text-[rgb(var(--m3-color-on-surface-rgb))]">{{ metadata.reason }}</dd>
                                </div>
                            </div>
                            <div v-if="metadata.user_agent" class="flex items-start gap-3">
                                <MonitorSmartphone class="mt-0.5 h-4 w-4 shrink-0 text-[rgb(var(--m3-color-on-surface-variant-rgb))]" />
                                <div class="min-w-0">
                                    <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">User agent</dt>
                                    <dd class="break-all rounded-lg bg-[rgb(var(--m3-color-surface-container-high-rgb))] p-2 text-xs text-[rgb(var(--m3-color-on-surface-rgb))]">{{ metadata.user_agent }}</dd>
                                </div>
                            </div>
                        </dl>
                    </section>
                </div>

                <div class="space-y-4">
                    <section class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                        <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]"><ShieldCheck class="h-4 w-4" /> Pelaku</h2>
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Nama</dt>
                                <dd class="mt-0.5 font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.admin?.name || 'Unknown' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Email</dt>
                                <dd class="mt-0.5 break-all text-[rgb(var(--m3-color-on-surface-rgb))]">{{ log.admin?.email || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Admin ID</dt>
                                <dd class="mt-0.5 font-mono text-[rgb(var(--m3-color-on-surface-rgb))]">#{{ log.admin_id }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-2xl border border-[rgb(var(--m3-color-secondary-container-rgb))] bg-[rgb(var(--m3-color-secondary-container-rgb))] p-4 text-sm text-[rgb(var(--m3-color-on-secondary-container-rgb))]">
                        <div class="flex items-start gap-2">
                            <Info class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>Audit log bersifat append-only. Jangan mengubah atau menghapus entri untuk menjaga integritas jejak audit.</p>
                        </div>
                    </section>
                </div>
            </div>

            <section v-if="raw" class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]"><Code2 class="h-4 w-4" /> Raw data</h2>
                <pre class="max-h-80 overflow-auto rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-high-rgb))] p-4 text-xs text-[rgb(var(--m3-color-on-surface-rgb))]">{{ raw }}</pre>
            </section>
        </div>
    </AdminLayout>
</template>
