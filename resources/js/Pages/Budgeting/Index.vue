<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import AppIcon from '@/Components/AppIcon.vue';
import BaseModal from '@/Components/BaseModal.vue';
import Button from '@/Components/Button.vue';
import ConfirmationDialog from '@/Components/ConfirmationDialog.vue';
import { getCategoryIconColor } from '@/Composables/useIcon.js';
import { useBotAvatar } from '@/Composables/useBotAvatar.js';
import { useToast } from '@/Composables/useToast.js';
import { formatRupiah, formatDate } from '@/utils/format.js';

const { t } = useI18n();
const { showToast } = useToast();

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    expenseGroups: {
        type: Object,
        default: () => ({}),
    },
    botName: {
        type: String,
        default: 'Ken-Chan',
    },
    botAvatar: {
        type: String,
        default: null,
    },
});

const { avatarFailed, initials, onAvatarError } = useBotAvatar(() => props.botName);

const budgetData = ref(null);
const isLoading = ref(true);
const loadError = ref(false);
const isGenerating = ref(false);
const showAiNotes = ref(false);
const showRegenerateConfirm = ref(false);
const expandedGroups = ref(new Set());
const generateError = ref(null);
const generationTimer = ref(null);

// Batas maksimal polling status generate — sinkron dengan job timeout (300s).
// Setelah lewat, polling dihentikan dan spinner dimatikan supaya tidak muter terus.
const POLL_MAX_MS = 5 * 60 * 1000;
const generationStartedAt = ref(0);

const toggleGroup = (key) => {
    const next = new Set(expandedGroups.value);
    if (next.has(key)) next.delete(key);
    else next.add(key);
    expandedGroups.value = next;
};

const currentMonth = ref(new Date().getMonth() + 1);
const currentYear = ref(new Date().getFullYear());

// ─── Data fetching ─────────────────────────────────────────────────
const fetchBudget = async () => {
    isLoading.value = true;
    loadError.value = false;
    try {
        const response = await axios.get(`/api/v1/budget/${currentYear.value}/${currentMonth.value}`);
        budgetData.value = response.data;
    } catch (error) {
        if (error.response && error.response.status === 404) {
            budgetData.value = null;
        } else {
            loadError.value = true;
            console.error('Error fetching budget:', error);
        }
    } finally {
        isLoading.value = false;
    }
};

// ─── Generate AI (async: request hanya dispatch job, polling status) ──
const stopGenerationPolling = () => {
    if (generationTimer.value) {
        clearInterval(generationTimer.value);
        generationTimer.value = null;
    }
};

const fetchGenerationStatus = async () => {
    try {
        const { data } = await axios.get('/api/v1/budget/generate/status', {
            params: { month: currentMonth.value, year: currentYear.value },
        });
        return data;
    } catch (error) {
        return null;
    }
};

const startGenerationPolling = () => {
    stopGenerationPolling();
    generationStartedAt.value = Date.now();
    generationTimer.value = setInterval(async () => {
        const status = await fetchGenerationStatus();
        if (!status || status.status === 'processing' || status.status === 'pending') {
            if (Date.now() - generationStartedAt.value > POLL_MAX_MS) {
                stopGenerationPolling();
                isGenerating.value = false;
                generateError.value = t('budgeting.timeout');
                showToast(t('budgeting.timeout'), 'error');
                await fetchBudget();
            }
            return;
        }

        stopGenerationPolling();
        isGenerating.value = false;

        if (status.status === 'completed') {
            generateError.value = null;
            showToast(t('budgeting.generated'));
            await fetchBudget();
        } else if (status.status === 'failed') {
            generateError.value = status.error_message || t('budgeting.aiError', { bot: botName });
            showToast(t('budgeting.aiError', { bot: botName }), 'error');
            await fetchBudget();
        }
    }, 2000);
};

const generateBudget = async () => {
    if (isGenerating.value) return;
    isGenerating.value = true;
    generateError.value = null;
    try {
        await axios.post('/api/v1/budget/generate', {
            month: currentMonth.value,
            year: currentYear.value,
        });
        startGenerationPolling();
    } catch (error) {
        isGenerating.value = false;
        showToast(error.response?.data?.message || t('budgeting.aiError', { bot: botName }), 'error');
        console.error('Error dispatching budget generation:', error);
    }
};

const checkStatusAndFetch = async () => {
    if (!isCurrentPeriod.value) {
        await fetchBudget();
        return;
    }
    const status = await fetchGenerationStatus();

    // Hanya aktifkan spinner & polling jika status benar-benar active (pending/processing).
    // Backend sudah handle stuck job > 10 menit → dikembalikan sebagai failed.
    // Jika status failed/completed/idle/null → langsung fetch budget saja, tidak perlu polling.
    if (status && (status.status === 'pending' || status.status === 'processing')) {
        isGenerating.value = true;
        startGenerationPolling();
        await fetchBudget();
    } else if (status && status.status === 'failed' && status.error_message) {
        // Tampilkan error terakhir tapi jangan putar spinner
        generateError.value = status.error_message;
        await fetchBudget();
    } else {
        await fetchBudget();
    }
};

const requestRegenerate = () => {
    showRegenerateConfirm.value = true;
};

const confirmRegenerate = () => {
    showRegenerateConfirm.value = false;
    generateBudget();
};

// ─── Period navigation ─────────────────────────────────────────────
const shiftMonth = (delta) => {
    const date = new Date(currentYear.value, currentMonth.value - 1 + delta, 1);
    currentYear.value = date.getFullYear();
    currentMonth.value = date.getMonth() + 1;
};

const periodLabel = computed(() =>
    new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(
        new Date(currentYear.value, currentMonth.value - 1, 1),
    ),
);

// Generate AI hanya untuk bulan berjalan; bulan lain (lampau/mendatang) hanya bisa dilihat & diedit manual
const isCurrentPeriod = computed(() => {
    const now = new Date();
    return currentYear.value === now.getFullYear() && currentMonth.value === now.getMonth() + 1;
});

watch([currentMonth, currentYear], () => {
    stopGenerationPolling();
    generateError.value = null;
    checkStatusAndFetch();
});

// ─── Derived data ──────────────────────────────────────────────────
const summaryMap = computed(() => budgetData.value?.summary ?? {});
const budgetItems = computed(() => budgetData.value?.items ?? []);

const mergeRows = (list) =>
    list.map((entry) => {
        const item = budgetItems.value.find(
            (i) => i.budgetable_type?.endsWith('Category') && i.budgetable_id === entry.id,
        );
        const summary = item ? summaryMap.value[item.id] ?? {} : {};
        return {
            ...entry,
            name: entry.category_name,
            item,
            target: Number(summary.target ?? item?.target_amount ?? 0),
            spent: Number(summary.spent ?? 0),
            remaining: Number(summary.remaining ?? 0),
        };
    });

// Kategori terisi (budget/transaksi) tampil duluan, sisanya alfabetis
const categoryRows = computed(() => {
    const rows = mergeRows(props.categories);
    return [...rows].sort((a, b) => {
        const aActive = a.target > 0 || a.spent > 0;
        const bActive = b.target > 0 || b.spent > 0;
        if (aActive !== bActive) return aActive ? -1 : 1;
        if (aActive) return b.spent - a.spent;
        return String(a.name).localeCompare(String(b.name));
    });
});

// Grup pengeluaran (Fix Cost / Additional Cost) — dari AI, per periode budget
const groupRows = computed(() => {
    const configOrder = Object.keys(props.expenseGroups);
    const configNames = props.expenseGroups;
    const groups = budgetData.value?.expense_groups ?? [];

    return [...groups]
        .sort((a, b) => configOrder.indexOf(a.group_key) - configOrder.indexOf(b.group_key))
        .map((group) => {
            const members = categoryRows.value.filter((row) => (group.category_ids ?? []).includes(row.id));
            const target = members.reduce((sum, row) => sum + row.target, 0);
            const spent = members.reduce((sum, row) => sum + row.spent, 0);
            return {
                key: group.group_key,
                name: configNames[group.group_key] ?? group.group_name,
                members,
                target,
                spent,
                remaining: target - spent,
            };
        });
});

const visibleRows = computed(() => groupRows.value);

const totalBudget = computed(() => Number(budgetData.value?.total_budget_amount ?? 0));
const totalSpent = computed(() =>
    budgetItems.value
        .filter((item) => item.budgetable_type?.endsWith('Category'))
        .reduce((acc, item) => acc + (Number(summaryMap.value[item.id]?.spent) || 0), 0),
);
const totalRemaining = computed(() => totalBudget.value - totalSpent.value);

const pct = (row) => (row.target > 0 ? Math.min(100, Math.round((row.spent / row.target) * 100)) : 0);
const isOver = (row) => row.target > 0 && row.spent > row.target;

const createdLabel = computed(() => (budgetData.value?.created_at ? formatDate(budgetData.value.created_at) : ''));

onMounted(checkStatusAndFetch);
onUnmounted(stopGenerationPolling);
</script>

<template>
    <Head :title="t('budgeting.title')" />

    <AuthenticatedLayout>
        <div class="px-4 sm:px-5 pb-40 pt-4 sm:pt-6 w-full lg:max-w-7xl mx-auto lg:px-8 relative z-10">
            <!-- Top bar: month slider kiri, aksi kanan -->
            <div class="flex items-center justify-between gap-2 mb-5 sm:mb-6">
                <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        class="w-8 h-8 shrink-0 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] flex items-center justify-center text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors"
                        aria-label="Previous month"
                        @click="shiftMonth(-1)"
                    >
                        <AppIcon icon="chevron-left" class="w-4 h-4" />
                    </button>

                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] shadow-card">
                        <AppIcon icon="calendar" class="w-3.5 h-3.5 text-[var(--color-brand)]" />
                        <span class="text-xs font-bold text-[var(--color-text-primary)]">{{ periodLabel }}</span>
                    </div>

                    <button
                        type="button"
                        class="w-8 h-8 shrink-0 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] flex items-center justify-center text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors"
                        aria-label="Next month"
                        @click="shiftMonth(1)"
                    >
                        <AppIcon icon="chevron-right" class="w-4 h-4" />
                    </button>
                </div>

                <div class="flex items-center gap-1.5">
                    <Button
                        v-if="!budgetData && isCurrentPeriod"
                        as="a"
                        :href="route('budgeting.create')"
                        variant="secondary"
                        size="xs"
                        class="w-8 h-8 shrink-0 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] flex items-center justify-center text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors"
                        :aria-label="t('budgeting.createManual')"
                    >
                        <template #icon-left><AppIcon icon="plus" class="w-4 h-4" /></template>
                    </Button>
                    <Button
                        v-if="budgetData && isCurrentPeriod"
                        as="a"
                        :href="route('budgeting.create')"
                        variant="secondary"
                        size="xs"
                        class="w-8 h-8 shrink-0 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] flex items-center justify-center text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors"
                        :aria-label="t('budgeting.createManual')"
                    >
                        <template #icon-left><AppIcon icon="pencil" class="w-4 h-4" /></template>
                    </Button>
                    <Button
                        v-if="budgetData && isCurrentPeriod"
                        variant="secondary"
                        size="xs"
                        class="w-8 h-8 shrink-0 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] flex items-center justify-center text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors"
                        :aria-label="t('budgeting.refresh')"
                        :loading="isGenerating"
                        @click="requestRegenerate"
                    >
                        <template #icon-left><AppIcon icon="refresh-cw" class="w-4 h-4" /></template>
                    </Button>
                </div>
            </div>

            <!-- Loading indicator (top) -->
            <div v-if="isLoading" class="h-0.5 rounded-full bg-[var(--color-surface-muted)]/60 overflow-hidden mb-5 sm:mb-6" role="status" aria-label="Loading">
                <div class="h-full bg-[var(--color-brand)] animate-pulse rounded-full"></div>
            </div>

            <!-- Error state -->
            <div v-if="loadError" class="rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] shadow-card p-6 text-center">
                <AppIcon icon="alert-triangle" class="w-8 h-8 mx-auto text-[var(--color-expense-text)] mb-3" />
                <p class="text-sm font-semibold text-[var(--color-text-primary)]">{{ t('budgeting.loadError') }}</p>
                <Button variant="secondary" size="sm" class="mt-4" @click="fetchBudget">{{ t('budgeting.retry') }}</Button>
            </div>

            <template v-else>
                <!-- ── Empty state: no budget for this period ── -->
                <section v-if="!budgetData" class="relative overflow-hidden rounded-xl shadow-card animate-fade-in-up">
                    <div class="absolute inset-0 bg-gradient-to-br from-[var(--color-brand-deep)] to-[var(--color-brand-mid)]"></div>
                    <div class="relative p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="botAvatar && !avatarFailed"
                                :src="botAvatar"
                                alt=""
                                class="w-12 h-12 rounded-full object-cover ring-2 ring-white/30 shrink-0"
                                @error="onAvatarError"
                            >
                            <div
                                v-else
                                class="w-12 h-12 rounded-full bg-white/20 ring-2 ring-white/30 flex items-center justify-center text-base font-black text-white shrink-0"
                            >
                                {{ initials }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-white">{{ botName }}</p>
                                <p class="text-2xs text-white/70">{{ t('budgeting.emptyDesc') }}</p>
                            </div>
                        </div>

                        <h2 class="text-lg sm:text-xl font-bold text-white mt-4">{{ t('budgeting.emptyTitle', { bot: botName }) }}</h2>

                        <button
                            v-if="isCurrentPeriod"
                            type="button"
                            :disabled="isGenerating"
                            class="mt-5 inline-flex items-center gap-2 rounded-lg bg-white px-4 sm:px-5 py-2.5 text-sm font-semibold text-[var(--color-brand-pressed)] disabled:opacity-60 disabled:cursor-not-allowed transition-colors hover:bg-white/90 active:scale-[0.98]"
                            @click="generateBudget"
                        >
                            <AppIcon v-if="!isGenerating" icon="sparkles" class="w-4 h-4" />
                            <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ isGenerating ? t('budgeting.generating') : t('budgeting.generate', { bot: botName }) }}
                        </button>
                        <div
                            v-else
                            class="mt-5 inline-flex items-start gap-2 rounded-lg bg-white/15 px-4 py-2.5 text-xs sm:text-sm font-semibold text-white/90"
                        >
                            <AppIcon icon="lock" class="w-4 h-4 shrink-0 mt-px" />
                            <span>{{ t('budgeting.pastPeriod') }}</span>
                        </div>

                        <div
                            v-if="generateError"
                            class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-white/20 bg-white/10 px-4 py-3 animate-fade-in-up"
                        >
                            <p class="text-2xs sm:text-xs text-white/90 break-words">{{ generateError }}</p>
                            <Button variant="secondary" size="sm" class="shrink-0" @click="generateBudget">
                                {{ t('budgeting.retry') }}
                            </Button>
                        </div>
                    </div>
                </section>

                <!-- ── Active state: budget exists ── -->
                <template v-else>
                    <!-- Summary card -->
                    <section class="rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] shadow-card p-5 sm:p-6 animate-fade-in-up">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="botAvatar && !avatarFailed"
                                :src="botAvatar"
                                alt=""
                                class="w-10 h-10 rounded-full object-cover ring-2 ring-[var(--color-brand-border)] shrink-0"
                                @error="onAvatarError"
                            >
                            <div v-else class="w-10 h-10 rounded-full bg-[var(--color-brand-subtle)] ring-2 ring-[var(--color-brand-border)] flex items-center justify-center text-sm font-black text-[var(--color-brand)] shrink-0">
                                {{ initials }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-base sm:text-lg font-black text-[var(--color-text-primary)] tracking-tight leading-tight">
                                    {{ t('budgeting.monthlyTitle') }}
                                </p>
                                <p class="text-2xs text-[var(--color-text-muted)] mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span class="font-semibold text-[var(--color-brand)]">{{ botName }}</span>
                                    <span aria-hidden="true">·</span>
                                    <span>{{ periodLabel }}<span v-if="createdLabel"> · {{ createdLabel }}</span></span>
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <Button variant="secondary" size="xs" :aria-label="t('budgeting.aiNotes')" @click="showAiNotes = true">
                                    <template #icon-left><AppIcon icon="lightbulb" class="w-3.5 h-3.5" /></template>
                                </Button>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-3 mt-5">
                            <div class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-surface-overlay)] p-3 sm:p-4 text-center">
                                <p class="text-base sm:text-lg font-bold leading-tight tracking-tight break-words text-[var(--color-text-primary)]">{{ formatRupiah(totalBudget) }}</p>
                                <p class="text-2xs text-[var(--color-text-muted)] mt-1">{{ t('budgeting.totalBudget') }}</p>
                            </div>
                            <div class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-expense-bg)] p-3 sm:p-4 text-center">
                                <p class="text-base sm:text-lg font-bold leading-tight tracking-tight break-words text-[var(--color-expense-text)]">{{ formatRupiah(totalSpent) }}</p>
                                <p class="text-2xs text-[var(--color-expense-text)] mt-1">{{ t('budgeting.totalSpent') }}</p>
                            </div>
                            <div class="col-span-2 sm:col-span-1 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-surface-overlay)] p-3 sm:p-4 text-center">
                                <p
                                    class="text-base sm:text-lg font-bold leading-tight tracking-tight break-words"
                                    :class="totalRemaining < 0 ? 'text-[var(--color-expense-text)]' : 'text-[var(--color-brand)]'"
                                >
                                    {{ formatRupiah(totalRemaining) }}
                                </p>
                                <p class="text-2xs text-[var(--color-text-muted)] mt-1">{{ t('budgeting.totalRemaining') }}</p>
                            </div>
                        </div>

                        <p v-if="!isCurrentPeriod" class="mt-5 text-2xs text-[var(--color-text-muted)]">
                            {{ t('budgeting.pastPeriodHint') }}
                        </p>
                        <div
                            v-if="generateError"
                            class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-[var(--color-expense-border)] bg-[var(--color-expense-bg)] px-4 py-3 animate-fade-in-up"
                        >
                            <p class="text-2xs sm:text-xs text-[var(--color-expense-text)] break-words">{{ generateError }}</p>
                            <Button variant="secondary" size="sm" class="shrink-0" @click="generateBudget">
                                {{ t('budgeting.retry') }}
                            </Button>
                        </div>
                    </section>

                    <!-- Item grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 lg:gap-4 mt-4 sm:mt-5 animate-fade-in-up">
                        <div
                            v-for="row in visibleRows"
                            :key="row.key"
                            class="rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] shadow-card p-3 transition-colors hover:border-[var(--color-brand-border)]"
                        >
                            <div
                                class="flex items-center gap-2 min-w-0 cursor-pointer select-none rounded-lg"
                                role="button"
                                :tabindex="0"
                                :aria-expanded="expandedGroups.has(row.key)"
                                @click="toggleGroup(row.key)"
                                @keydown.enter="toggleGroup(row.key)"
                                @keydown.space.prevent="toggleGroup(row.key)"
                            >
                                <AppIcon icon="layers" class="w-6 h-6 lg:w-7 lg:h-7 shrink-0 text-[var(--color-brand)]" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-2xs lg:text-xs font-bold text-[var(--color-text-primary)] truncate">{{ row.name }}</p>
                                    <p class="text-2xs text-[var(--color-text-muted)]">
                                        {{ row.members.length }} {{ t('budgeting.categories') }}
                                    </p>
                                </div>
                                <AppIcon
                                    :icon="expandedGroups.has(row.key) ? 'chevron-up' : 'chevron-down'"
                                    class="w-4 h-4 shrink-0 text-[var(--color-text-muted)]"
                                />
                            </div>

                            <div class="mt-3 space-y-2">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="text-2xs text-[var(--color-text-muted)]">{{ t('budgeting.budget') }}</span>
                                    <span class="text-xs font-semibold text-[var(--color-text-primary)]">
                                        {{ formatRupiah(row.target) }}
                                    </span>
                                </div>

                                <!-- Progress bar -->
                                <div class="h-1.5 rounded-full bg-[var(--color-surface-muted)]/60 overflow-hidden" role="progressbar" :aria-valuenow="pct(row)" aria-valuemin="0" aria-valuemax="100">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="isOver(row) ? 'bg-[var(--color-expense-text)]' : 'bg-[var(--color-brand)]'"
                                        :style="{ width: pct(row) + '%' }"
                                    ></div>
                                </div>

                                <div class="flex items-center justify-between gap-1">
                                    <span class="text-2xs text-[var(--color-text-muted)]">{{ t('budgeting.spent') }} {{ formatRupiah(row.spent) }}</span>
                                    <span
                                        class="text-2xs font-semibold"
                                        :class="row.remaining < 0 ? 'text-[var(--color-expense-text)]' : 'text-[var(--color-text-secondary)]'"
                                    >
                                        {{ t('budgeting.remaining') }} {{ formatRupiah(row.remaining) }}
                                    </span>
                                </div>

                                <p v-if="isOver(row)" class="text-2xs font-semibold text-[var(--color-expense-text)]">
                                    {{ t('budgeting.overBudget') }} {{ formatRupiah(row.spent - row.target) }}
                                </p>
                            </div>

                            <!-- Expanded members -->
                            <div
                                v-if="expandedGroups.has(row.key)"
                                class="mt-3 pt-3 border-t border-[var(--color-border-default)] space-y-2.5 animate-fade-in-up"
                            >
                                <div v-for="m in row.members" :key="m.id" class="flex items-center gap-2">
                                    <AppIcon
                                        :icon="m.icon"
                                        fallback="folder"
                                        class="w-4 h-4 shrink-0"
                                        :class="getCategoryIconColor(m.type?.name)"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-2xs font-semibold text-[var(--color-text-primary)] truncate sm:break-words">{{ m.name }}</p>
                                        <div class="h-1 rounded-full bg-[var(--color-surface-muted)]/60 overflow-hidden mt-1" role="progressbar" :aria-valuenow="pct(m)" aria-valuemin="0" aria-valuemax="100">
                                            <div
                                                class="h-full rounded-full"
                                                :class="isOver(m) ? 'bg-[var(--color-expense-text)]' : 'bg-[var(--color-brand)]'"
                                                :style="{ width: pct(m) + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-xs font-bold text-[var(--color-text-primary)]">{{ formatRupiah(m.target) }}</p>
                                        <p class="text-2xs text-[var(--color-text-muted)]">{{ t('budgeting.spent') }} {{ formatRupiah(m.spent) }}</p>
                                    </div>
                                </div>
                                <p v-if="row.members.length === 0" class="text-2xs text-[var(--color-text-muted)] text-center">—</p>
                            </div>
                        </div>
                    </div>
                </template>
            </template>

            <!-- AI Insights modal -->
            <BaseModal :show="showAiNotes" max-width="sm" :show-close-btn="false" @close="showAiNotes = false">
                <div class="flex flex-col items-center text-center py-2">
                    <img
                        v-if="botAvatar && !avatarFailed"
                        :src="botAvatar"
                        alt=""
                        class="w-14 h-14 rounded-full object-cover ring-2 ring-[var(--color-brand-border)] mb-2.5"
                        @error="onAvatarError"
                    >
                    <div v-else class="w-14 h-14 rounded-full bg-[var(--color-brand-subtle)] ring-2 ring-[var(--color-brand-border)] flex items-center justify-center text-base font-black text-[var(--color-brand)] mb-2.5">
                        {{ initials }}
                    </div>
                    <p class="text-base font-bold text-[var(--color-text-primary)] leading-tight">{{ botName }}</p>
                    <p class="text-2xs text-[var(--color-text-muted)] mt-1">
                        {{ periodLabel }}<span v-if="createdLabel"> · {{ createdLabel }}</span>
                    </p>
                </div>
                <div class="mt-4 max-h-64 overflow-y-auto rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-overlay)] p-4 text-left">
                    <p v-if="budgetData?.ai_notes" class="text-sm text-[var(--color-text-secondary)] leading-relaxed whitespace-pre-line">
                        {{ budgetData.ai_notes }}
                    </p>
                    <p v-else class="text-sm text-[var(--color-text-muted)]">—</p>
                </div>
                <div class="mt-5 flex justify-center">
                    <Button variant="secondary" size="sm" @click="showAiNotes = false">
                        {{ t('budgeting.close') }}
                    </Button>
                </div>
            </BaseModal>

            <!-- Regenerate confirmation -->
            <ConfirmationDialog
                :show="showRegenerateConfirm"
                :title="t('budgeting.refreshConfirmTitle')"
                :message="t('budgeting.refreshConfirmMsg')"
                :confirm-text="t('budgeting.refreshConfirmCta')"
                :cancel-text="t('common.cancel')"
                variant="warning"
                @close="showRegenerateConfirm = false"
                @confirm="confirmRegenerate"
            />
        </div>
    </AuthenticatedLayout>
</template>
