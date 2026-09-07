<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { Chart, registerables } from 'chart.js';
import AppIcon from '@/Components/AppIcon.vue';

const { t } = useI18n();

// Prevent double registration when component remounts (HMR / multiple instances)
if (!Chart._bendaharakuRegistered) {
    Chart.register(...registerables);
    Chart._bendaharakuRegistered = true;
}

const props = defineProps({
    component: { type: Object, required: true },
});

const canvasRef = ref(null);
let chartInstance = null;
const renderError = ref('');

const hasData = computed(() => {
    const inc = props.component.incomeData ?? [];
    const exp = props.component.expenseData ?? [];
    return [...inc, ...exp].some((v) => Number(v) > 0);
});

function stripMarkdown(s) {
    if (!s) return s;
    return s.replace(/\*\*/g, '').trim();
}

const displayTitle = computed(() => {
    if (props.component.title) return stripMarkdown(props.component.title);
    if (props.component.translationKey) {
        const translated = t(props.component.translationKey);
        // t() returns key itself if missing, and may contain **
        if (translated !== props.component.translationKey) return stripMarkdown(translated);
    }
    return stripMarkdown(t('chat.statistik.title'));
});

const subtitle = computed(() => t('chat.statistik.subtitle'));
const incomeLabel = computed(() => t('chat.statistik.chartIncome'));
const expenseLabel = computed(() => t('chat.statistik.chartExpense'));

const fallbackTotalIncome = computed(() => {
    const backend = props.component.totalIncome;
    const arr = props.component.incomeData ?? [];
    const sum = arr.reduce((a, b) => a + Number(b || 0), 0);
    // Backend may be missing (old messages) or 0 while sum >0 — use sum then
    if (backend === undefined || backend === null) return sum;
    const num = Number(backend);
    if (num === 0 && sum > 0) return sum;
    return num;
});

const fallbackTotalExpense = computed(() => {
    const backend = props.component.totalExpense;
    const arr = props.component.expenseData ?? [];
    const sum = arr.reduce((a, b) => a + Number(b || 0), 0);
    if (backend === undefined || backend === null) return sum;
    const num = Number(backend);
    if (num === 0 && sum > 0) return sum;
    return num;
});

const fallbackNet = computed(() => fallbackTotalIncome.value - fallbackTotalExpense.value);

function formatCompact(n) {
    if (n >= 1_000_000_000) return (n / 1_000_000_000).toFixed(1).replace(/\.0$/, '') + 'B';
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M';
    if (n >= 1_000) return (n / 1_000).toFixed(1).replace(/\.0$/, '') + 'K';
    return String(Math.round(n));
}

function formatRupiah(n) {
    const v = Number(n) || 0;
    return 'Rp ' + v.toLocaleString('id-ID');
}

function render() {
    renderError.value = '';
    if (!canvasRef.value) return;
    if (!hasData.value) return;
    try {
        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }
        const labels = props.component.labels ?? [];
        const incomeData = props.component.incomeData ?? [];
        const expenseData = props.component.expenseData ?? [];
        if (!labels.length) {
            renderError.value = t('chat.statistik.noData');
            return;
        }
        const maxVal = Math.max(1, ...incomeData.map(Number), ...expenseData.map(Number));
        const yMax = Math.ceil(maxVal * 1.2 / 1000) * 1000 || 1000;
        if (yMax <= 0) return;

        chartInstance = new Chart(canvasRef.value, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: incomeLabel.value,
                        data: incomeData,
                        backgroundColor: 'rgba(74, 222, 128, 0.85)',
                        borderColor: '#4ade80',
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8,
                    },
                    {
                        label: expenseLabel.value,
                        data: expenseData,
                        backgroundColor: 'rgba(248, 113, 113, 0.85)',
                        borderColor: '#f87171',
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 400 },
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { color: '#9ca3af', font: { size: 11, weight: '600' }, boxWidth: 12, padding: 12, usePointStyle: true },
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.dataset.label}: Rp ${formatCompact(ctx.parsed.y)}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#9ca3af', font: { size: 10, weight: '600' } },
                        border: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        max: yMax,
                        grid: { color: 'rgba(255,255,255,0.06)' },
                        ticks: { color: '#6b7280', font: { size: 10 }, callback: (v) => formatCompact(v) },
                        border: { display: false },
                    },
                },
            },
        });
    } catch (e) {
        console.error('BarChart render error', e);
        renderError.value = e?.message ?? t('chat.statistik.renderFailed');
    }
}

onMounted(async () => {
    await nextTick();
    render();
});

watch(() => [props.component.labels, props.component.incomeData, props.component.expenseData, incomeLabel.value, expenseLabel.value], () => nextTick(render), { deep: true });

onBeforeUnmount(() => {
    if (chartInstance) {
        try { chartInstance.destroy(); } catch (_) {}
        chartInstance = null;
    }
});
</script>

<template>
    <div class="rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] p-3 sm:p-4">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 rounded-lg bg-[var(--color-brand-subtle)] border border-[var(--color-brand-border)] flex items-center justify-center shrink-0">
                <AppIcon icon="bar-chart-3" iconClass="w-4 h-4 text-[var(--color-brand)]" />
            </div>
            <div>
                <p class="text-xs font-bold text-[var(--color-text-primary)] leading-none">{{ displayTitle }}</p>
                <p class="text-2xs text-[var(--color-text-muted)]">{{ subtitle }}</p>
            </div>
        </div>
        <div v-if="renderError" class="py-6 text-center text-xs text-[var(--color-text-muted)]">{{ renderError }}</div>
        <div v-else-if="!hasData" class="py-8 text-center">
            <p class="text-xs text-[var(--color-text-muted)]">{{ $t('chat.statistik.emptyTitle') }}</p>
            <p class="text-2xs text-[var(--color-text-muted)]/70 mt-1">{{ $t('chat.statistik.emptyHint') }}</p>
        </div>
        <template v-else>
            <div class="relative h-[180px] w-full">
                <canvas ref="canvasRef" class="w-full h-full"></canvas>
            </div>
            <div class="mt-3 space-y-2">
                <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-[var(--color-income-bg)] border border-[var(--color-income-border)]">
                    <span class="flex items-center gap-2 text-xs font-medium text-[var(--color-income-text)]">
                        <AppIcon icon="trending-up" iconClass="w-4 h-4" />
                        {{ $t('chat.statistik.incomeLabel') }}
                    </span>
                    <span class="text-xs font-bold tabular-nums text-[var(--color-income-text)]">{{ formatRupiah(fallbackTotalIncome) }}</span>
                </div>
                <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-[var(--color-expense-bg)] border border-[var(--color-expense-border)]">
                    <span class="flex items-center gap-2 text-xs font-medium text-[var(--color-expense-text)]">
                        <AppIcon icon="trending-down" iconClass="w-4 h-4" />
                        {{ $t('chat.statistik.expenseLabel') }}
                    </span>
                    <span class="text-xs font-bold tabular-nums text-[var(--color-expense-text)]">{{ formatRupiah(fallbackTotalExpense) }}</span>
                </div>
                <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-[var(--color-surface-muted)] border border-[var(--color-border-default)]">
                    <span class="flex items-center gap-2 text-xs font-medium text-[var(--color-text-secondary)]">
                        <AppIcon icon="wallet" iconClass="w-4 h-4 text-[var(--color-text-muted)]" />
                        {{ $t('chat.statistik.netLabel') }}
                    </span>
                    <span class="text-xs font-bold tabular-nums" :class="fallbackNet >= 0 ? 'text-[var(--color-income-text)]' : 'text-[var(--color-expense-text)]'">{{ formatRupiah(fallbackNet) }}</span>
                </div>
            </div>
        </template>
    </div>
</template>
