<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import {
    ArrowLeft,
    Ban,
    CheckCircle2,
    Clock,
    FolderTree,
    LogIn,
    ReceiptText,
    ShieldCheck,
    Wallet,
} from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue'

const props = defineProps({
    user: { type: Object, required: true },
})

const page = usePage()
const actorRoles = computed(() => page.props.auth?.roles ?? [])
const actorId = computed(() => page.props.auth?.user?.id)
const targetRole = computed(() => props.user.roles?.[0]?.name || 'user')

const canModify = computed(() => {
    if (props.user.id === actorId.value) return false
    if (actorRoles.value.includes('owner')) return true
    if (targetRole.value === 'owner') return false
    if (actorRoles.value.includes('admin')) return true
    return targetRole.value === 'user'
})

const modalOpen = ref(false)
const processing = ref(false)

const isSuspended = computed(() => props.user.status === 'suspended')
const role = computed(() => props.user.roles?.[0]?.name || 'user')

const formatDateTime = (value) =>
    value
        ? new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
        : '—'

const toggleStatus = () => {
    processing.value = true
    router.post(
        route('admin.users.update_status', props.user.id),
        {
            status: isSuspended.value ? 'active' : 'suspended',
            reason: isSuspended.value ? 'Diaktifkan dari halaman detail user' : 'Ditangguhkan dari halaman detail user',
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                modalOpen.value = false
            },
            onFinish: () => {
                processing.value = false
            },
        },
    )
}
</script>

<template>
    <AdminLayout>
        <Head :title="`User: ${user.name}`" />

        <div class="space-y-6 p-4 sm:p-6">
            <Link :href="route('admin.users.index')" class="inline-flex items-center gap-2 text-sm font-medium text-[rgb(var(--m3-color-on-surface-variant-rgb))] transition-colors hover:text-[rgb(var(--m3-color-primary-rgb))]">
                <ArrowLeft class="h-4 w-4" /> Kembali ke daftar user
            </Link>

            <div class="flex flex-col gap-4 rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[rgb(var(--m3-color-primary-container-rgb))] text-xl font-bold text-[rgb(var(--m3-color-on-primary-container-rgb))]">
                        {{ user.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user.name }}</h1>
                            <span class="rounded-full bg-[rgb(var(--m3-color-secondary-container-rgb))] px-2.5 py-1 text-xs font-semibold capitalize text-[rgb(var(--m3-color-on-secondary-container-rgb))]">{{ role }}</span>
                            <span
                                :class="[
                                    'rounded-full px-2.5 py-1 text-xs font-semibold capitalize',
                                    isSuspended
                                        ? 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]'
                                        : 'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]',
                                ]"
                            >
                                {{ user.status || 'active' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ user.email }}</p>
                        <p class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">User ID #{{ user.id }}</p>
                    </div>
                </div>

                <button
                    v-if="canModify"
                    type="button"
                    :class="[
                        'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-colors',
                        isSuspended
                            ? 'bg-[rgb(var(--m3-color-tertiary-rgb))] text-[rgb(var(--m3-color-on-tertiary-rgb))]'
                            : 'bg-[rgb(var(--m3-color-error-rgb))] text-[rgb(var(--m3-color-on-error-rgb))]',
                    ]"
                    @click="modalOpen = true"
                >
                    <CheckCircle2 v-if="isSuspended" class="h-4 w-4" />
                    <Ban v-else class="h-4 w-4" />
                    {{ isSuspended ? 'Aktifkan user' : 'Suspend user' }}
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Dompet</p>
                        <Wallet class="h-5 w-5 text-[rgb(var(--m3-color-primary-rgb))]" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user.wallets_count ?? 0 }}</p>
                </div>
                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Kategori</p>
                        <FolderTree class="h-5 w-5 text-[rgb(var(--m3-color-tertiary-rgb))]" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user.categories_count ?? 0 }}</p>
                </div>
                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Transaksi</p>
                        <ReceiptText class="h-5 w-5 text-[rgb(var(--m3-color-secondary-rgb))]" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user.transaction_logs_count ?? 0 }}</p>
                </div>
                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Verifikasi</p>
                        <ShieldCheck class="h-5 w-5" :class="user.email_verified_at ? 'text-[rgb(var(--m3-color-tertiary-rgb))]' : 'text-[rgb(var(--m3-color-on-surface-variant-rgb))]'" />
                    </div>
                    <p class="mt-2 text-lg font-bold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user.email_verified_at ? 'Terverifikasi' : 'Belum' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Aktivitas</h2>
                    <dl class="space-y-4 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="flex items-center gap-2 text-[rgb(var(--m3-color-on-surface-variant-rgb))]"><Clock class="h-4 w-4" /> Terakhir aktif</dt>
                            <dd class="text-right font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatDateTime(user.last_active_at) }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="flex items-center gap-2 text-[rgb(var(--m3-color-on-surface-variant-rgb))]"><LogIn class="h-4 w-4" /> Login terakhir</dt>
                            <dd class="text-right font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatDateTime(user.last_login_at) }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Bergabung</dt>
                            <dd class="text-right font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatDateTime(user.created_at) }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Email diverifikasi</dt>
                            <dd class="text-right font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ formatDateTime(user.email_verified_at) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-rgb))] p-5">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Kontak</h2>
                    <dl class="space-y-4 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Email</dt>
                            <dd class="break-all text-right font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user.email }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Telegram ID</dt>
                            <dd class="text-right font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user.telegram_id || '—' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">WhatsApp</dt>
                            <dd class="text-right font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user.whatsapp_number || '—' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-[rgb(var(--m3-color-on-surface-variant-rgb))]">Locale</dt>
                            <dd class="text-right font-medium text-[rgb(var(--m3-color-on-surface-rgb))]">{{ user.locale || '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="rounded-2xl border border-[rgb(var(--m3-color-primary-container-rgb))] bg-[rgb(var(--m3-color-primary-container-rgb))] p-4 text-sm text-[rgb(var(--m3-color-on-primary-container-rgb))]">
                Halaman ini hanya menampilkan jumlah data. Saldo dan isi transaksi tidak ditampilkan di panel admin.
            </div>
        </div>

        <ConfirmDialog
            v-model:open="modalOpen"
            :title="isSuspended ? `Aktifkan ${user.name}?` : `Suspend ${user.name}?`"
            :description="isSuspended ? 'User akan bisa login kembali.' : 'User akan langsung dikeluarkan dan tidak bisa login sampai diaktifkan.'"
            :confirm-label="isSuspended ? 'Aktifkan' : 'Suspend'"
            :tone="isSuspended ? 'primary' : 'danger'"
            :loading="processing"
            @confirm="toggleStatus"
        />
    </AdminLayout>
</template>
