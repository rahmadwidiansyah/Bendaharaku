<script setup>
/**
 * M3BarChart — grafik statistik 7 hari di bubble bot (paritas MessageBarChart).
 * Tema Chart.js dari Material/utils/m3Chart.js (token M3, sadar light/dark).
 * Animasi dimatikan saat prefers-reduced-motion.
 */
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { Chart, registerables } from 'chart.js'
import AppIcon from '@/Components/AppIcon.vue'
import { m3ChartChrome, m3TooltipOptions, m3CssVar, m3ChartFontFamily } from '@/Material/utils/m3Chart.js'

const { t } = useI18n()

if (!Chart._bendaharakuRegistered) {
    Chart.register(...registerables)
    Chart._bendaharakuRegistered = true
}

const props = defineProps({
    component: { type: Object, required: true },
})

const canvasRef = ref(null)
let chartInstance = null
const renderError = ref('')

const hasData = computed(() => {
    const inc = props.component.incomeData ?? []
    const exp = props.component.expenseData ?? []
    return [...inc, ...exp].some((v) => Number(v) > 0)
})

function stripMarkdown(s) {
    if (!s) return s
    return s.replace(/\*\*/g, '').trim()
}

const displayTitle = computed(() => {
    if (props.component.title) return stripMarkdown(props.component.title)
    if (props.component.translationKey) {
        const translated = t(props.component.translationKey)
        if (translated !== props.component.translationKey) return stripMarkdown(translated)
    }
    return stripMarkdown(t('chat.statistik.title'))
})

const subtitle = computed(() => t('chat.statistik.subtitle'))
const incomeLabel = computed(() => t('chat.statistik.chartIncome'))
const expenseLabel = computed(() => t('chat.statistik.chartExpense'))

const fallbackTotalIncome = computed(() => {
    const backend = props.component.totalIncome
    const arr = props.component.incomeData ?? []
    const sum = arr.reduce((a, b) => a + Number(b || 0), 0)
    if (backend === undefined || backend === null) return sum
    const num = Number(backend)
    if (num === 0 && sum > 0) return sum
    return num
})

const fallbackTotalExpense = computed(() => {
    const backend = props.component.totalExpense
    const arr = props.component.expenseData ?? []
    const sum = arr.reduce((a, b) => a + Number(b || 0), 0)
    if (backend === undefined || backend === null) return sum
    const num = Number(backend)
    if (num === 0 && sum > 0) return sum
    return num
})

const fallbackNet = computed(() => fallbackTotalIncome.value - fallbackTotalExpense.value)

function formatCompact(n) {
    if (n >= 1_000_000_000) return (n / 1_000_000_000).toFixed(1).replace(/\.0$/, '') + 'B'
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M'
    if (n >= 1_000) return (n / 1_000).toFixed(1).replace(/\.0$/, '') + 'K'
    return String(Math.round(n))
}

function formatRupiah(n) {
    const v = Number(n) || 0
    return 'Rp ' + v.toLocaleString('id-ID')
}

function withAlpha(color, alpha) {
    const c = String(color || '').trim()
    if (/^#[0-9a-f]{6}$/i.test(c)) {
        return c + Math.round(alpha * 255).toString(16).padStart(2, '0')
    }
    return c
}

function render() {
    renderError.value = ''
    if (!canvasRef.value) return
    if (!hasData.value) return
    try {
        if (chartInstance) {
            chartInstance.destroy()
            chartInstance = null
        }
        const labels = props.component.labels ?? []
        const incomeData = props.component.incomeData ?? []
        const expenseData = props.component.expenseData ?? []
        if (!labels.length) {
            renderError.value = t('chat.statistik.noData')
            return
        }
        const maxVal = Math.max(1, ...incomeData.map(Number), ...expenseData.map(Number))
        const yMax = Math.ceil(maxVal * 1.2 / 1000) * 1000 || 1000
        if (yMax <= 0) return

        const chrome = m3ChartChrome()
        const fontFamily = m3ChartFontFamily()
        const incomeColor = m3CssVar('--m3-color-income', '#4ade80')
        const expenseColor = m3CssVar('--m3-color-expense', '#f87171')
        const reducedMotion = typeof window !== 'undefined'
            && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

        chartInstance = new Chart(canvasRef.value, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: incomeLabel.value,
                        data: incomeData,
                        backgroundColor: withAlpha(incomeColor, 0.85),
                        borderColor: incomeColor,
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8,
                    },
                    {
                        label: expenseLabel.value,
                        data: expenseData,
                        backgroundColor: withAlpha(expenseColor, 0.85),
                        borderColor: expenseColor,
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
                animation: reducedMotion ? false : { duration: 400 },
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: chrome.labels,
                            font: { size: 11, weight: '600', family: fontFamily },
                            boxWidth: 12,
                            padding: 12,
                            usePointStyle: true,
                        },
                    },
                    tooltip: {
                        ...m3TooltipOptions(chrome),
                        callbacks: {
                            label: (ctx) => `${ctx.dataset.label}: Rp ${formatCompact(ctx.parsed.y)}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: chrome.labels, font: { size: 10, weight: '600', family: fontFamily } },
                        border: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        max: yMax,
                        grid: { color: chrome.grid },
                        ticks: { color: chrome.ticks, font: { size: 10, family: fontFamily }, callback: (v) => formatCompact(v) },
                        border: { display: false },
                    },
                },
            },
        })
    } catch (e) {
        console.error('BarChart render error', e)
        renderError.value = e?.message ?? t('chat.statistik.renderFailed')
    }
}

onMounted(async () => {
    await nextTick()
    render()
})

watch(
    () => [props.component.labels, props.component.incomeData, props.component.expenseData, incomeLabel.value, expenseLabel.value],
    () => nextTick(render),
    { deep: true },
)

onBeforeUnmount(() => {
    if (chartInstance) {
        try { chartInstance.destroy() } catch (_) {}
        chartInstance = null
    }
})
</script>

<template>
    <div class="m3-chart">
        <div class="m3-chart__head">
            <span class="m3-chart__icon-chip" aria-hidden="true">
                <AppIcon icon="bar-chart-3" iconClass="m3-chart__icon" />
            </span>
            <div class="m3-chart__titles">
                <p class="m3-chart__title">{{ displayTitle }}</p>
                <p class="m3-chart__subtitle">{{ subtitle }}</p>
            </div>
        </div>

        <div v-if="renderError" class="m3-chart__state">{{ renderError }}</div>

        <div v-else-if="!hasData" class="m3-chart__state">
            <p class="m3-chart__state-title">{{ t('chat.statistik.emptyTitle') }}</p>
            <p class="m3-chart__state-hint">{{ t('chat.statistik.emptyHint') }}</p>
        </div>

        <template v-else>
            <div class="m3-chart__canvas-wrap">
                <canvas ref="canvasRef" role="img" :aria-label="t('chat.statistik.barChartAria')" />
            </div>

            <div class="m3-chart__summaries">
                <div class="m3-chart__summary m3-chart__summary--income">
                    <span class="m3-chart__summary-label">
                        <AppIcon icon="trending-up" iconClass="m3-chart__summary-icon" />
                        {{ t('chat.statistik.incomeLabel') }}
                    </span>
                    <span class="m3-chart__summary-value">{{ formatRupiah(fallbackTotalIncome) }}</span>
                </div>

                <div class="m3-chart__summary m3-chart__summary--expense">
                    <span class="m3-chart__summary-label">
                        <AppIcon icon="trending-down" iconClass="m3-chart__summary-icon" />
                        {{ t('chat.statistik.expenseLabel') }}
                    </span>
                    <span class="m3-chart__summary-value">{{ formatRupiah(fallbackTotalExpense) }}</span>
                </div>

                <div class="m3-chart__summary m3-chart__summary--net">
                    <span class="m3-chart__summary-label">
                        <AppIcon icon="wallet" iconClass="m3-chart__summary-icon m3-chart__summary-icon--net" />
                        {{ t('chat.statistik.netLabel') }}
                    </span>
                    <span class="m3-chart__summary-value" :class="fallbackNet >= 0 ? 'm3-chart__summary-value--income' : 'm3-chart__summary-value--expense'">
                        {{ formatRupiah(fallbackNet) }}
                    </span>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.m3-chart {
    margin: var(--m3-space-1-5) var(--m3-space-2);
    padding: var(--m3-space-3);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-lowest);
    overflow: hidden;
}

.m3-chart__head {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2-5);
    margin-bottom: var(--m3-space-3);
}

.m3-chart__icon-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-icon-xl);
    height: var(--m3-size-icon-xl);
    flex-shrink: 0;
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
}

.m3-chart__icon {
    width: var(--m3-size-icon-2xs);
    height: var(--m3-size-icon-2xs);
}

.m3-chart__titles {
    min-width: 0;
}

.m3-chart__title {
    margin: 0;
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-on-surface);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-chart__subtitle {
    margin: 0;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-chart__state {
    padding: var(--m3-space-6) var(--m3-space-3);
    text-align: center;
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-on-surface-variant);
}

.m3-chart__state-title {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-on-surface-variant);
}

.m3-chart__state-hint {
    margin: var(--m3-space-1) 0 0;
    font-size: var(--m3-type-label-small-size);
    color: color-mix(in srgb, var(--m3-color-on-surface-variant) 70%, transparent);
}

.m3-chart__canvas-wrap {
    position: relative;
    height: 11.25rem;
    width: 100%;
}

.m3-chart__summaries {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
    margin-top: var(--m3-space-3);
}

.m3-chart__summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
    padding: var(--m3-space-2) var(--m3-space-3);
    border-radius: var(--m3-radius-md);
    border: 1px solid transparent;
}

.m3-chart__summary--income {
    background: var(--m3-color-income-container);
    border-color: color-mix(in srgb, var(--m3-color-income) 30%, transparent);
    color: var(--m3-color-on-income-container);
}

.m3-chart__summary--expense {
    background: var(--m3-color-expense-container);
    border-color: color-mix(in srgb, var(--m3-color-expense) 30%, transparent);
    color: var(--m3-color-on-expense-container);
}

.m3-chart__summary--net {
    background: var(--m3-color-surface-container);
    border-color: var(--m3-color-outline-variant);
    color: var(--m3-color-on-surface-variant);
}

.m3-chart__summary-label {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-2);
    font-size: var(--m3-type-body-small-size);
    font-weight: var(--m3-type-emphasized-body-small-weight);
}

.m3-chart__summary-icon {
    width: var(--m3-size-icon-2xs);
    height: var(--m3-size-icon-2xs);
    flex-shrink: 0;
}

.m3-chart__summary-icon--net {
    color: var(--m3-color-on-surface-variant);
}

.m3-chart__summary-value {
    font-size: var(--m3-type-body-small-size);
    font-weight: var(--m3-type-emphasized-body-small-weight);
    font-variant-numeric: tabular-nums;
}

.m3-chart__summary--income .m3-chart__summary-value {
    color: var(--m3-color-income);
}

.m3-chart__summary--expense .m3-chart__summary-value {
    color: var(--m3-color-expense);
}

.m3-chart__summary-value--income { color: var(--m3-color-income); }
.m3-chart__summary-value--expense { color: var(--m3-color-expense); }
</style>
