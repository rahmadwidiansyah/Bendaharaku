<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DateModal from '@/Components/DateModal.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, shallowRef, onMounted, watch, computed, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';
import { formatNumber } from '@/utils/format.js';
import { useI18n } from 'vue-i18n';
import AppIcon from '@/Components/AppIcon.vue';
import { getCategoryIconColor } from '@/Composables/useIcon.js';

const { t } = useI18n();

Chart.register(...registerables);

const props = defineProps({
    startDate: String,
    endDate: String,
    totalIncome: Number,
    totalExpense: Number,
    totalDebt: Number,
    totalReceivable: Number,
    cumulativeBalance: Number,
    expensesByCategory: Array,
    incomesByCategory: Array,
    debtsByCategory: Array,
    receivablesByCategory: Array,
    dailyLabels: Array,
    dailyIncome: Array,
    dailyExpense: Array,
    cumulativeData: Array,
    todayIndex: Number,
    allDailyLabels: Array,
    allDailyDates: Array,
    allDailyIncome: Array,
    allDailyExpense: Array,
    allDailyDebt: Array,
    allDailyReceivable: Array
});

const charts = shallowRef({});
const barView = ref('harian');
const categoryView = ref('expense');
const barChartRef = ref(null);
const barChartKey = ref(0);
const cumulativeChartKey = ref(0);
const doughnutChartKey = ref(0);

const isDateFilterActive = computed(() => usePage().url.includes('start_date='));

const cssVar = (name, fallback) =>
    getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;

// Warna arus kas — baca token CSS (otomatis mengikuti tema dark/light):
// masuk hijau, keluar merah, hutang kuning, piutang ungu (statis)
const FLOW_COLORS = computed(() => ({
    income: cssVar('--color-income-chart', '#34D399'),
    expense: cssVar('--color-expense-chart', '#F87171'),
    debt: cssVar('--color-debt-chart', '#FBBF24'),
    receivable: cssVar('--color-receivable-chart', '#C084FC'),
}));

const barViews = [
    { key: 'harian', label: () => t('analytics.view.daily') },
    { key: 'mingguan', label: () => t('analytics.view.weekly') },
    { key: 'bulanan', label: () => t('analytics.view.monthly') },
];

const categoryViews = [
    { key: 'expense', label: () => t('analytics.categoryTab.expenseShort') },
    { key: 'income', label: () => t('analytics.categoryTab.incomeShort') },
    { key: 'debt', label: () => t('analytics.categoryTab.debtShort') },
    { key: 'receivable', label: () => t('analytics.categoryTab.receivableShort') },
];

const destroyChart = (id) => {
    if (charts.value[id]) {
        charts.value[id].destroy();
        delete charts.value[id];
    }
};

const initCumulativeChart = async () => {
    try {
        destroyChart('cumulative');
        cumulativeChartKey.value++;
        await nextTick();

        const ctx = document.getElementById('cumulativeChart')?.getContext('2d');
        if (!ctx) {
            console.warn('Cumulative chart canvas context not found');
            return;
        }

        // Validasi data sebelum membuat chart
        if (!props.dailyLabels || !props.cumulativeData || 
            props.dailyLabels.length === 0 || props.cumulativeData.length === 0) {
            console.warn('Insufficient data for cumulative chart:', {
                labels: props.dailyLabels?.length,
                data: props.cumulativeData?.length
            });
            return;
        }

        const brand = cssVar('--color-brand', '#a855f7');

        let grad = ctx.createLinearGradient(0, 0, 0, 140);
        grad.addColorStop(0, hexToRgba(brand, 0.35));
        grad.addColorStop(1, hexToRgba(brand, 0));

        charts.value['cumulative'] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: props.dailyLabels,
                datasets: [{
                    data: props.cumulativeData,
                    borderColor: brand,
                    borderWidth: 2.5,
                    backgroundColor: grad,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointHoverRadius: 7,
                    pointBackgroundColor: brand,
                    pointBorderColor: cssVar('--color-gray-950', '#121212'),
                    pointBorderWidth: 1.5,
                    pointHoverBackgroundColor: brand,
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 400 },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(18,18,18,0.95)',
                        titleColor: '#9CA3AF',
                        titleFont: { size: 11, weight: 'bold' },
                        bodyColor: '#fff',
                        bodyFont: { size: 13, weight: 'bold' },
                        padding: { x: 12, y: 8 },
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            title: (items) => {
                                const d = new Date(items[0].label + 'T00:00:00');
                                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                            },
                            label: (item) => 'Rp ' + Number(item.raw).toLocaleString('id-ID'),
                        }
                    },
                },
                scales: { x: { display: false }, y: { display: false } }
            }
        });
    } catch (error) {
        console.error('Failed to initialize cumulative chart:', error);
    }
};

function formatCompact(n) {
    if (n >= 1e9) return (n / 1e9).toFixed(1) + 'B';
    if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M';
    if (n >= 1e3) return (n / 1e3).toFixed(0) + 'K';
    return n.toString();
}

/**
 * Bangun dataset arus kas untuk seluruh periode yang tersedia.
 * Kontainer chart menyediakan scroll horizontal agar periode lama tetap dapat diakses.
 */
function buildBarData(view) {
    const dates = props.allDailyDates || [];
    const labels = [], incomes = [], expenses = [], debts = [], receivables = [];

    function pushNonEmpty(l, i, e, d, r) {
        if (i || e || d || r) {
            labels.push(l); incomes.push(i); expenses.push(e); debts.push(d); receivables.push(r);
        }
    }

    if (view === 'harian') {
        dates.forEach((date, i) => {
            const d = new Date(date);
            pushNonEmpty(
                d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }),
                Math.abs(props.allDailyIncome[i] || 0),
                Math.abs(props.allDailyExpense[i] || 0),
                Math.abs(props.allDailyDebt[i] || 0),
                Math.abs(props.allDailyReceivable[i] || 0)
            );
        });
        return { labels, incomes, expenses, debts, receivables };
    }

    if (view === 'mingguan') {
        let bucket = null;
        dates.forEach((date, i) => {
            const d = new Date(date);
            if (!bucket) {
                bucket = {
                    start: d.getDate(),
                    end: d.getDate(),
                    inc: 0,
                    exp: 0,
                    debt: 0,
                    rec: 0,
                    count: 0,
                };
            }

            bucket.end = d.getDate();
            bucket.inc += Math.abs(props.allDailyIncome[i] || 0);
            bucket.exp += Math.abs(props.allDailyExpense[i] || 0);
            bucket.debt += Math.abs(props.allDailyDebt[i] || 0);
            bucket.rec += Math.abs(props.allDailyReceivable[i] || 0);
            bucket.count++;

            if (bucket.count === 7 || i === dates.length - 1) {
                pushNonEmpty(`${bucket.start}-${bucket.end}`, bucket.inc, bucket.exp, bucket.debt, bucket.rec);
                bucket = null;
            }
        });
        return { labels, incomes, expenses, debts, receivables };
    }

    // bulanan — seluruh periode yang tersedia
    const monthMap = new Map();
    dates.forEach((dateStr, i) => {
        const d = new Date(dateStr);
        const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        if (!monthMap.has(key)) {
            monthMap.set(key, {
                label: d.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }),
                inc: 0, exp: 0, debt: 0, rec: 0,
            });
        }
        const m = monthMap.get(key);
        m.inc += Math.abs(props.allDailyIncome[i] || 0);
        m.exp += Math.abs(props.allDailyExpense[i] || 0);
        m.debt += Math.abs(props.allDailyDebt[i] || 0);
        m.rec += Math.abs(props.allDailyReceivable[i] || 0);
    });

    Array.from(monthMap.values()).forEach((m) => {
        pushNonEmpty(m.label, m.inc, m.exp, m.debt, m.rec);
    });

    return { labels, incomes, expenses, debts, receivables };
}

const renderBarChart = async (view) => {
    barView.value = view;
    const { labels, incomes, expenses, debts, receivables } = buildBarData(view);

    destroyChart('bar');
    barChartKey.value++;
    await nextTick();

    const canvas = barChartRef.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    charts.value['bar'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: t('analytics.chartLabels.income'), data: incomes, backgroundColor: FLOW_COLORS.value.income, borderRadius: 5, barPercentage: 0.9, categoryPercentage: 0.85 },
                { label: t('analytics.chartLabels.expense'), data: expenses, backgroundColor: FLOW_COLORS.value.expense, borderRadius: 5, barPercentage: 0.9, categoryPercentage: 0.85 },
                { label: t('analytics.chartLabels.debt'), data: debts, backgroundColor: FLOW_COLORS.value.debt, borderRadius: 5, barPercentage: 0.9, categoryPercentage: 0.85 },
                { label: t('analytics.chartLabels.receivable'), data: receivables, backgroundColor: FLOW_COLORS.value.receivable, borderRadius: 5, barPercentage: 0.9, categoryPercentage: 0.85 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 400 },
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#9CA3AF', font: { size: 11, weight: 'bold' }, maxRotation: 0 }
                },
                y: {
                    display: true,
                    grid: { color: 'rgba(255,255,255,0.06)', drawBorder: false },
                    border: { display: false },
                    ticks: { display: false }
                }
            }
        },
        plugins: [{
            id: 'barLabels',
            afterDraw(chart) {
                const ctx2 = chart.ctx;
                const isMobile = typeof window !== 'undefined' && window.innerWidth < 640;
                const fontSize = isMobile ? 9 : 10;
                const placedLabels = [];
                
                ctx2.font = `bold ${fontSize}px sans-serif`;
                ctx2.fillStyle = '#D1D5DB';
                ctx2.textAlign = 'center';
                ctx2.shadowColor = 'rgba(0, 0, 0, 0.5)';
                ctx2.shadowBlur = 2;
                ctx2.shadowOffsetY = 1;
                
                chart.data.datasets.forEach((ds, dsIndex) => {
                    const meta = chart.getDatasetMeta(dsIndex);
                    meta.data.forEach((el, i) => {
                        const val = Number(ds.data[i]);
                        if (!val || val <= 0) return;
                        
                        const text = formatCompact(val);
                        const textWidth = ctx2.measureText(text).width;
                        const textHeight = fontSize;
                        
                        const positions = [
                            { x: el.x, y: el.y - 4, baseline: 'bottom' },
                            { x: el.x, y: el.y - 12, baseline: 'bottom' },
                            { x: el.x, y: el.y - 20, baseline: 'bottom' },
                            { x: el.x, y: el.y + 12, baseline: 'top' }
                        ];
                        
                        let placed = false;
                        for (const pos of positions) {
                            const labelBounds = {
                                x: pos.x - textWidth / 2 - 2,
                                y: pos.baseline === 'bottom' ? pos.y - textHeight - 2 : pos.y,
                                width: textWidth + 4,
                                height: textHeight + 4
                            };
                            
                            const hasCollision = placedLabels.some(placed => {
                                return !(labelBounds.x + labelBounds.width < placed.x ||
                                        labelBounds.x > placed.x + placed.width ||
                                        labelBounds.y + labelBounds.height < placed.y ||
                                        labelBounds.y > placed.y + placed.height);
                            });
                            
                            if (!hasCollision && pos.y > 10 && pos.y < chart.height - 10) {
                                ctx2.textBaseline = pos.baseline;
                                ctx2.fillText(text, pos.x, pos.y);
                                placedLabels.push(labelBounds);
                                placed = true;
                                break;
                            }
                        }
                    });
                });
                
                ctx2.shadowColor = 'transparent';
            }
        }]
    });
};

const chartMinWidth = computed(() => {
    const { labels } = buildBarData(barView.value);
    const minWidthPerLabel = 80; // 80px per time period
    return Math.max(labels.length * minWidthPerLabel, 300); // minimum 300px
});

const cumulativeChartMinWidth = computed(() => {
    const labelCount = props.dailyLabels?.length ?? 0;
    const minWidthPerLabel = 40; // 40px per day label for line chart
    return Math.max(labelCount * minWidthPerLabel, 300); // minimum 300px
});

const activeCategoryData = computed(() => {
    function sortDesc(items) {
        return [...items].sort((a, b) => b.total - a.total);
    }
    if (categoryView.value === 'expense') {
        const s = sortDesc(props.expensesByCategory);
        return { labels: s.map(x => x.name), values: s.map(x => x.total), ids: s.map(x => x.id), icons: s.map(x => x.icon), total: props.totalExpense, labelName: t('analytics.totalExpense') };
    } else if (categoryView.value === 'income') {
        const s = sortDesc(props.incomesByCategory);
        return { labels: s.map(x => x.name), values: s.map(x => x.total), ids: s.map(x => x.id), icons: s.map(x => x.icon), total: props.totalIncome, labelName: t('analytics.totalIncome') };
    } else if (categoryView.value === 'debt') {
        const s = sortDesc(props.debtsByCategory);
        return { labels: s.map(x => x.name), values: s.map(x => x.total), ids: s.map(x => x.id), icons: s.map(x => x.icon), total: s.reduce((a, b) => a + b.total, 0), labelName: t('analytics.totalDebt') };
    } else {
        const s = sortDesc(props.receivablesByCategory);
        return { labels: s.map(x => x.name), values: s.map(x => x.total), ids: s.map(x => x.id), icons: s.map(x => x.icon), total: s.reduce((a, b) => a + b.total, 0), labelName: t('analytics.totalReceivable') };
    }
});

const activeColors = computed(() => {
    const base = {
        expense: () => cssVar('--color-expense-chart', '#F87171'),
        income: () => cssVar('--color-income-chart', '#34D399'),
        debt: () => cssVar('--color-debt-chart', '#FBBF24'),
        receivable: () => cssVar('--color-receivable-chart', '#C084FC'),
    }[categoryView.value]();
    return buildPalette(base);
});

// Ramp 6 shade dari satu warna base: terang → gelap (urutan rank tetap sama)
function buildPalette(baseHex) {
    const mix = (hex, weight) => {
        const { r, g, b } = hexRgb(hex);
        const t = (v) => {
            const n = weight > 0 ? Math.round(v + (255 - v) * weight) : Math.round(v * (1 + weight));
            return Math.max(0, Math.min(255, n));
        };
        return `#${[t(r), t(g), t(b)].map((n) => n.toString(16).padStart(2, '0')).join('')}`;
    };
    return [mix(baseHex, 0.40), mix(baseHex, 0.22), baseHex, mix(baseHex, -0.15), mix(baseHex, -0.30), mix(baseHex, -0.45)];
}

const iconColorClass = computed(() => {
    const map = { income: 'Income', expense: 'Expense', debt: 'Debt', receivable: 'Receivable' };
    return getCategoryIconColor(map[categoryView.value]);
});

const categoryColors = computed(() => {
    const data = activeCategoryData.value;
    if (!data.labels.length) return [];
    const palette = activeColors.value;
    const sorted = data.values.map((v, i) => ({ v, i })).sort((a, b) => a.v - b.v);
    return data.labels.map((_, i) => {
        const rank = sorted.findIndex(s => s.i === i);
        return palette[Math.round((rank / Math.max(sorted.length - 1, 1)) * (palette.length - 1))];
    });
});

function hexRgb(hex) {
    const v = parseInt(hex.slice(1), 16);
    return { r: v >> 16, g: (v >> 8) & 255, b: v & 255 };
}

function hexToRgba(hex, alpha) {
    const { r, g, b } = hexRgb(hex);
    return `rgba(${r},${g},${b},${alpha})`;
}

function applyGradients(chart, colors, labels) {
    if (!chart) return;
    const cvs = chart.canvas;
    const cx = cvs.width / 2;
    const cy = cvs.height / 2;
    const outerR = Math.min(cvs.width, cvs.height) / 2;
    const innerR = outerR * 0.7;
    const ctx = cvs.getContext('2d');

    chart.data.datasets[0].backgroundColor = labels.map((_, i) => {
        const c = colors[i % colors.length];
        const rgb = hexRgb(c);
        const g = ctx.createRadialGradient(cx, cy, innerR, cx, cy, outerR);
        g.addColorStop(0, `rgba(${rgb.r},${rgb.g},${rgb.b},0.35)`);
        g.addColorStop(1, c);
        return g;
    });
    chart.update();
}

const renderDoughnutChart = async () => {
    const dataObj = activeCategoryData.value;
    destroyChart('doughnut');
    doughnutChartKey.value++;
    await nextTick();

    const ctx = document.getElementById('mainChart')?.getContext('2d');
    if (!ctx || !dataObj.labels.length) return;

    const palette = activeColors.value;
    const sorted = dataObj.values.map((v, i) => ({ v, i })).sort((a, b) => a.v - b.v);
    const paint = dataObj.labels.map((_, i) => {
        const rank = sorted.findIndex(s => s.i === i);
        return palette[Math.round((rank / Math.max(sorted.length - 1, 1)) * (palette.length - 1))];
    });

    charts.value['doughnut'] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: dataObj.labels,
            datasets: [{
                data: dataObj.values,
                backgroundColor: paint,
                borderWidth: 2,
                borderColor: cssVar('--color-gray-950', '#121212')
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '80%',
            animation: { duration: 400 },
            plugins: { legend: { display: false } },
            onResize: (chart) => {
                const d = activeCategoryData.value;
                const p = activeColors.value;
                const s = d.values.map((v, i) => ({ v, i })).sort((a, b) => a.v - b.v);
                const paint2 = d.labels.map((_, i) => {
                    const rank = s.findIndex(x => x.i === i);
                    return p[Math.round((rank / Math.max(s.length - 1, 1)) * (p.length - 1))];
                });
                applyGradients(chart, paint2, d.labels);
            }
        }
    });

    requestAnimationFrame(() => applyGradients(charts.value['doughnut'], paint, dataObj.labels));
};

const switchCategory = (type) => {
    categoryView.value = type;
    renderDoughnutChart();
};

onMounted(() => {
    initCumulativeChart();
    renderBarChart('harian');
    renderDoughnutChart();
});
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head title="Analitik" />

        <div class="px-4 sm:px-5 pb-40 w-full lg:max-w-7xl mx-auto lg:px-8 relative z-10 overflow-x-hidden">

            <!-- Date bar -->
            <div class="flex items-center justify-between pt-3 pb-1">
<p class="text-xs text-white font-medium">
    {{ $t('analytics.showingData') }}
    <span class="text-gray-300">{{ new Date(startDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'long' }) }} – {{ new Date(endDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'long' }) }}</span>
</p>
<div class="relative">
    <DateModal :action="route('analytics.index')" :start-date="startDate" :end-date="endDate" />
</div>
</div>

<header class="flex items-center justify-between mb-4 lg:mb-6 animate-fade-in-up">
    <div class="hidden lg:block">
<p class="text-2xs text-purple-500 font-black mb-1.5 uppercase tracking-[0.2em] flex items-center gap-2">
    {{ $t('analytics.subtitle') }}
</p>
        <h1 class="text-3xl font-black text-white tracking-tight leading-none">{{ $t('analytics.title') }}</h1>
    </div>
</header>

            <div class="grid grid-cols-2 gap-3 mb-5 lg:mb-6 animate-fade-in-up delay-100">
                <div class="bg-linear-to-br from-success-deep to-success-mid/50 p-3 lg:p-4 rounded-xl border border-white/10 relative overflow-hidden group">
                    <div class="flex items-center gap-1.5 mb-1 lg:mb-2">
                        <div class="w-1 h-1.5 lg:w-1.5 lg:h-1.5 rounded-full bg-green-400"></div>
                        <p class="text-2xs font-bold text-gray-400 uppercase tracking-widest">{{ $t('types.income') }}</p>
                    </div>
                    <p class="text-sm lg:text-md font-black text-green-400 tracking-tighter wrap-break-words relative z-10 leading-tight">
                        <span class="text-2xs mr-0.5 opacity-70">+Rp</span>{{ formatNumber(totalIncome) }}
                    </p>
                </div>
                <div class="bg-linear-to-br from-danger-deep to-danger-mid/50 p-3 lg:p-4 rounded-xl border border-white/10 relative overflow-hidden group">
                    <div class="flex items-center gap-1.5 mb-1 lg:mb-2">
                        <div class="w-1 h-1.5 lg:w-1.5 lg:h-1.5 rounded-full bg-red-400"></div>
                        <p class="text-2xs font-bold text-gray-400 uppercase tracking-widest">{{ $t('types.expense') }}</p>
                    </div>
                    <p class="text-sm lg:text-md font-black text-red-400 tracking-tighter wrap-break-words relative z-10 leading-tight">
                        <span class="text-red-400 text-2xs mr-0.5 opacity-70">-Rp</span>{{ formatNumber(totalExpense) }}
                    </p>
                </div>
            </div>

            <!-- CUMULATIVE CHART -->
            <div class="bg-linear-to-br from-gray-900 to-gray-800 border border-gray-500/10 p-4 lg:p-6 rounded-xl mb-5 lg:mb-8 animate-fade-in-up delay-200 relative overflow-hidden group">
                <div class="flex justify-between items-start mb-4 lg:mb-6 relative z-10">
                    <div>
                <p class="text-2xs lg:text-xs font-bold text-white uppercase tracking-[0.2em] mb-1">{{ $t('analytics.cumulativeBalance') }}</p>
                        <p class="text-2xs text-gray-500 font-medium">{{ $t('analytics.cumulativeDesc') }}</p>
                    </div>
                    <p class="text-base lg:text-lg font-black text-white tracking-tight px-2.5 lg:px-3 py-1 lg:py-1.5 rounded-xl">
                        <span class="text-2xs text-gray-500 mr-1">Rp</span>{{ formatNumber(cumulativeBalance) }}
                    </p>
                </div>
                <div class="w-full h-[120px] lg:h-[140px] relative z-10">
                    <canvas id="cumulativeChart" :key="cumulativeChartKey"></canvas>
                </div>
            </div>

            <!-- BAR CHART -->
            <div class="bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 p-4 lg:p-6 rounded-xl mb-5 lg:mb-8 animate-fade-in-up delay-300 relative overflow-hidden group">
                <div class="flex items-center justify-between gap-2 mb-3 relative z-10">
                    <h2 class="text-2xs lg:text-sm font-bold text-white uppercase tracking-widest shrink-0">{{ $t('analytics.cashflow') }}</h2>

                    <!-- Toggle tanpa sliding-indicator: styling langsung di tombol aktif -->
                    <div class="flex bg-gray-900 border border-white/10 rounded-lg p-1 gap-1">
                        <button v-for="v in barViews" :key="v.key" @click="renderBarChart(v.key)"
                            :class="[
                                'text-2xs font-bold uppercase tracking-widest px-2 lg:px-3 py-1.5 rounded-md transition-colors duration-200',
                                barView === v.key
                                    ? 'bg-linear-to-br from-brand-soft to-brand-deep text-white shadow-sm'
                                    : 'text-gray-500 hover:text-white'
                            ]">{{ v.label() }}</button>
                    </div>
                </div>

                <!-- Legend manual, karena legend chart.js dimatikan -->
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mb-4 relative z-10">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: FLOW_COLORS.income }"></span>
                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">{{ $t('analytics.chartLabels.income') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: FLOW_COLORS.expense }"></span>
                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">{{ $t('analytics.chartLabels.expense') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: FLOW_COLORS.debt }"></span>
                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">{{ $t('analytics.chartLabels.debt') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: FLOW_COLORS.receivable }"></span>
                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">{{ $t('analytics.chartLabels.receivable') }}</span>
                    </div>
                </div>

                <!-- Chart container dengan horizontal scroll -->
                <div class="overflow-x-auto scrollbar-custom">
                    <div :style="{ minWidth: chartMinWidth + 'px', height: '220px' }">
                        <canvas ref="barChartRef" :key="barChartKey"></canvas>
                    </div>
                </div>
            </div>

            <!-- CATEGORY SECTION -->
            <div class="flex items-center gap-2 mb-3 lg:mb-4 px-1 animate-fade-in-up delay-400">
                <h2 class="text-2xs font-bold text-white uppercase tracking-widest">{{ $t('analytics.categoryBreakdown') }}</h2>
                <div class="flex-1 h-px bg-linear-to-r from-purple-500 to-transparent"></div>
            </div>

            <!-- Toggle tanpa sliding-indicator -->
            <div class="grid grid-cols-4 gap-1 bg-gray-900 border border-white/10 rounded-xl p-1 mb-4 lg:mb-5 animate-fade-in-up delay-400">
                <button v-for="c in categoryViews" :key="c.key" @click="switchCategory(c.key)"
                    :class="[
                        'text-2xs font-bold uppercase tracking-widest py-2 lg:py-3 rounded-lg transition-colors duration-200',
                        categoryView === c.key
                            ? 'bg-linear-to-br from-brand-soft to-brand-deep text-white shadow-sm'
                            : 'text-gray-500 hover:text-white'
                    ]">{{ c.label() }}</button>
            </div>

            <!-- DOUGHNUT CHART -->
            <div class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-4 lg:p-6 rounded-xl mb-5 lg:mb-8 animate-fade-in-up delay-500 relative overflow-hidden group">
                <div v-if="!activeCategoryData.labels.length" class="flex flex-col items-center justify-center py-8 lg:py-10">
                    <span class="w-10 h-10 lg:w-12 lg:h-12 bg-gray-800 rounded-xl flex items-center justify-center text-base lg:text-xl mb-3 border border-white/10">📭</span>
                    <p class="text-2xs font-bold text-white uppercase tracking-widest">{{ $t('analytics.noData') }}</p>
                </div>
                <template v-else>
                    <div class="relative w-full h-48 lg:h-56 mb-5 lg:mb-6">
                        <canvas id="mainChart" :key="doughnutChartKey" class="relative z-10 w-full h-full"></canvas>
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center justify-center pointer-events-none z-0">
                            <div class="w-[100px] lg:w-[110px] h-[100px] lg:h-[110px] rounded-full bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 flex flex-col items-center justify-center text-center px-1">
                                <span class="text-[10px] lg:text-2xs text-gray-500 font-bold uppercase tracking-widest mb-1">{{ activeCategoryData.labelName }}</span>
                                <span class="text-2xs lg:text-2xs font-black text-white tracking-tighter leading-tight w-full wrap-break-word">Rp {{ formatNumber(activeCategoryData.total) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-3 lg:space-y-4">
                        <template v-for="(label, i) in activeCategoryData.labels" :key="i">
                            <Link v-if="activeCategoryData.ids[i] != null"
                                :href="route('categories.show', {
                                    category: activeCategoryData.ids[i],
                                    start_date: startDate,
                                    end_date: endDate,
                                })"
                                class="relative flex items-center justify-between bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 p-2.5 lg:p-3 rounded-xl overflow-hidden group hover:border-purple-500/30 transition-all duration-300">
                                <div class="flex items-center gap-2.5 lg:gap-3 relative z-10 w-full">
                                    <div class="w-1 lg:w-1.5 h-5 lg:h-6 rounded-full" :style="{ backgroundColor: categoryColors[i] }"></div>
                                    <AppIcon :icon="activeCategoryData.icons[i]" :class="['w-5 h-5 lg:w-6 lg:h-6 shrink-0', iconColorClass]" />
                                    <div class="flex-1 min-w-0 pr-1 lg:pr-2">
                                        <p class="text-xs font-bold text-gray-200 truncate">{{ label }}</p>
                                        <p class="text-2xs text-gray-500 font-bold">{{ activeCategoryData.total > 0 ? ((activeCategoryData.values[i] / activeCategoryData.total) * 100).toFixed(1) : 0 }}%</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-2xs lg:text-xs font-black text-white block">Rp {{ formatNumber(activeCategoryData.values[i]) }}</span>
                                    </div>
                                </div>
                            </Link>
                            <div v-else
                                class="relative flex items-center justify-between bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 p-2.5 lg:p-3 rounded-xl overflow-hidden">
                                <div class="flex items-center gap-2.5 lg:gap-3 relative z-10 w-full">
                                    <div class="w-1 lg:w-1.5 h-5 lg:h-6 rounded-full" :style="{ backgroundColor: categoryColors[i] }"></div>
                                    <AppIcon :icon="activeCategoryData.icons[i]" :class="['w-5 h-5 lg:w-6 lg:h-6 shrink-0', iconColorClass]" />
                                    <div class="flex-1 min-w-0 pr-1 lg:pr-2">
                                        <p class="text-xs font-bold text-gray-200 truncate">{{ label }}</p>
                                        <p class="text-2xs text-gray-500 font-bold">{{ activeCategoryData.total > 0 ? ((activeCategoryData.values[i] / activeCategoryData.total) * 100).toFixed(1) : 0 }}%</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-2xs lg:text-xs font-black text-white block">Rp {{ formatNumber(activeCategoryData.values[i]) }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in-up {
    0% { opacity: 0; transform: translateY(15px); }
    100% { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up { animation: fade-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
.delay-100 { animation-delay: 100ms; }
.delay-200 { animation-delay: 200ms; }
.delay-300 { animation-delay: 300ms; }
.delay-400 { animation-delay: 400ms; }
.delay-500 { animation-delay: 500ms; }
</style>

<style>
/* Custom scrollbar styling for bar chart */
.scrollbar-custom {
    scrollbar-width: thin;
    scrollbar-color: rgba(139, 92, 246, 0.3) rgba(31, 41, 55, 0.5);
}

.scrollbar-custom::-webkit-scrollbar {
    height: 6px;
}

.scrollbar-custom::-webkit-scrollbar-track {
    background: rgba(31, 41, 55, 0.5);
    border-radius: 3px;
}

.scrollbar-custom::-webkit-scrollbar-thumb {
    background: rgba(139, 92, 246, 0.3);
    border-radius: 3px;
}

.scrollbar-custom::-webkit-scrollbar-thumb:hover {
    background: rgba(139, 92, 246, 0.5);
}
</style>
