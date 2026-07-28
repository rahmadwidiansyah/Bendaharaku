<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DateModal from '@/Components/DateModal.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, shallowRef, onMounted, watch, computed, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';
import { formatNumber } from '@/utils/format.js';
import { useI18n } from 'vue-i18n';
import AppIcon from '@/Components/AppIcon.vue';

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
    allDailyIncome: Array,
    allDailyExpense: Array,
    allDailyDebt: Array,
    allDailyReceivable: Array
});

const charts = shallowRef({});
const barView = ref('harian');
const categoryView = ref('expense');
const barScrollBox = ref(null);
const barChartRef = ref(null);
const barChartContainerRef = ref(null);
const barChartKey = ref(0);
const cumulativeChartKey = ref(0);
const doughnutChartKey = ref(0);

const destroyChart = (id) => {
    if (charts.value[id]) {
        charts.value[id].destroy();
        delete charts.value[id];
    }
};

const initCumulativeChart = async () => {
    destroyChart('cumulative');
    cumulativeChartKey.value++;
    await nextTick();

    const ctx = document.getElementById('cumulativeChart')?.getContext('2d');
    if (!ctx) return;

    const brand = getComputedStyle(document.documentElement).getPropertyValue('--color-brand').trim() || '#a855f7';

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
                pointRadius: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 400 },
            plugins: { legend: { display: false } },
            scales: { x: { display: false }, y: { display: false } }
        }
    });
};

function formatCompact(n) {
    if (n >= 1e9) return (n / 1e9).toFixed(1) + 'B';
    if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M';
    if (n >= 1e3) return (n / 1e3).toFixed(0) + 'K';
    return n.toString();
}

const renderBarChart = async (view) => {
    barView.value = view;
    let labels = [], incomes = [], expenses = [], debts = [], receivables = [];

    function takeLast5(arr) {
        return arr ? arr.slice(-5) : [];
    }

    if (view === 'harian') {
        labels = takeLast5(props.allDailyLabels);
        incomes = takeLast5(props.allDailyIncome);
        expenses = takeLast5(props.allDailyExpense);
        debts = takeLast5(props.allDailyDebt);
        receivables = takeLast5(props.allDailyReceivable);
    } else if (view === 'mingguan') {
        if (props.allDailyLabels && props.allDailyLabels.length > 0) {
            let tempInc = 0, tempExp = 0, tempDebt = 0, tempRecv = 0;
            let startLabel = props.allDailyLabels[0];
            for (let i = 0; i < props.allDailyLabels.length; i++) {
                tempInc += (props.allDailyIncome[i] || 0);
                tempExp += (props.allDailyExpense[i] || 0);
                tempDebt += (props.allDailyDebt[i] || 0);
                tempRecv += (props.allDailyReceivable[i] || 0);
                if ((i + 1) % 7 === 0 || i === props.allDailyLabels.length - 1) {
                    let endLabel = props.allDailyLabels[i];
                    labels.push(startLabel.split(' ')[0] + '-' + endLabel);
                    incomes.push(tempInc);
                    expenses.push(tempExp);
                    debts.push(tempDebt);
                    receivables.push(tempRecv);
                    tempInc = 0; tempExp = 0; tempDebt = 0; tempRecv = 0;
                    if (i + 1 < props.allDailyLabels.length) startLabel = props.allDailyLabels[i + 1];
                }
            }
        }
    } else if (view === 'bulanan') {
        if (props.allDailyLabels && props.allDailyLabels.length > 0) {
            let currentMonth = '';
            let tempInc = 0, tempExp = 0, tempDebt = 0, tempRecv = 0;
            props.allDailyLabels.forEach((lbl, i) => {
                let parts = lbl.split(' ');
                let month = parts.length >= 2 ? parts[1] + ' ' + (parts[2] || '') : lbl;
                if (i === 0) currentMonth = month;
                if (month !== currentMonth) {
                    labels.push(currentMonth);
                    incomes.push(tempInc);
                    expenses.push(tempExp);
                    debts.push(tempDebt);
                    receivables.push(tempRecv);
                    currentMonth = month;
                    tempInc = 0; tempExp = 0; tempDebt = 0; tempRecv = 0;
                }
                tempInc += (props.allDailyIncome[i] || 0);
                tempExp += (props.allDailyExpense[i] || 0);
                tempDebt += (props.allDailyDebt[i] || 0);
                tempRecv += (props.allDailyReceivable[i] || 0);
            });
            if (currentMonth) {
                labels.push(currentMonth);
                incomes.push(tempInc);
                expenses.push(tempExp);
                debts.push(tempDebt);
                receivables.push(tempRecv);
            }
        }
    }

    destroyChart('bar');
    barChartKey.value++;
    await nextTick();

    const canvas = barChartRef.value;
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    if (barChartContainerRef.value) {
        let calculatedWidth = view === 'harian' ? labels.length * 45 : labels.length * 80;
        barChartContainerRef.value.style.minWidth = `max(100%, ${calculatedWidth}px)`;
    }

    charts.value['bar'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: t('analytics.chartLabels.income'), data: incomes, backgroundColor: '#34D399', borderRadius: 4, barPercentage: 0.85, categoryPercentage: 0.8 },
                { label: t('analytics.chartLabels.expense'), data: expenses, backgroundColor: '#F87171', borderRadius: 4, barPercentage: 0.85, categoryPercentage: 0.8 },
                { label: t('analytics.chartLabels.debt'), data: debts, backgroundColor: '#FBBF24', borderRadius: 4, barPercentage: 0.85, categoryPercentage: 0.8 },
                { label: t('analytics.chartLabels.receivable'), data: receivables, backgroundColor: '#C084FC', borderRadius: 4, barPercentage: 0.85, categoryPercentage: 0.8 }
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
                    ticks: { color: '#9CA3AF', font: { size: 10, weight: 'bold' }, maxRotation: 45 }
                },
                y: { display: false }
            }
        },
        plugins: [{
            id: 'barLabels',
            afterDraw(chart) {
                const ctx2 = chart.ctx;
                chart.data.datasets.forEach((ds) => {
                    const meta = chart.getDatasetMeta(chart.data.datasets.indexOf(ds));
                    meta.data.forEach((el, i) => {
                        const val = Number(ds.data[i]);
                        if (!val || val <= 0) return;
                        const y = el.y;
                        const x = el.x;
                        ctx2.fillStyle = '#9CA3AF';
                        ctx2.font = 'bold 9px sans-serif';
                        ctx2.textAlign = 'center';
                        ctx2.textBaseline = 'bottom';
                        ctx2.fillText(formatCompact(val), x, y - 3);
                    });
                });
            }
        }]
    });

    setTimeout(() => {
        if (barScrollBox.value) {
            barScrollBox.value.scrollLeft = barScrollBox.value.scrollWidth;
        }
    }, 100);
};

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
        return { labels: s.map(x => x.name), values: s.map(x => x.total), ids: s.map(x => x.id), icons: s.map(x => x.icon), total: props.totalDebt, labelName: t('analytics.totalDebt') };
    } else {
        const s = sortDesc(props.receivablesByCategory);
        return { labels: s.map(x => x.name), values: s.map(x => x.total), ids: s.map(x => x.id), icons: s.map(x => x.icon), total: props.totalReceivable, labelName: t('analytics.totalReceivable') };
    }
});

const activeColors = computed(() => {
    return categoryView.value === 'expense'
        ? ['#F87171', '#FCA5A5', '#FECACA', '#DC2626', '#EF4444', '#B91C1C']
        : categoryView.value === 'income'
            ? ['#34D399', '#6EE7B7', '#A7F3D0', '#059669', '#10B981', '#047857']
            : categoryView.value === 'debt'
                ? ['#FBBF24', '#FCD34D', '#FDE68A', '#D97706', '#F59E0B', '#B45309']
                : ['#C084FC', '#D8B4FE', '#E9D5FF', '#7C3AED', '#A855F7', '#6D28D9'];
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
                borderColor: '#121212'
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

        <div class="px-4 sm:px-5 pb-40 w-full lg:max-w-4xl mx-auto lg:px-8 relative z-10 overflow-x-hidden">

            <!-- Date bar — top-left, minimal margin -->
            <div class="flex items-center gap-2 pt-3 pb-1 -mx-1">
                <DateModal :action="route('analytics.index')" :start-date="startDate" :end-date="endDate" />
                <span class="text-2xs text-gray-500 font-medium">
                    {{ new Date(startDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'long' }) }}
                    <span class="text-gray-600 mx-0.5">–</span>
                    {{ new Date(endDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'long' }) }}
                </span>
            </div>

            <header class="flex items-center justify-between mb-4 lg:mb-6 animate-fade-in-up">
                <div class="hidden lg:block">
                    <p class="text-2xs text-purple-500 font-black mb-1.5 uppercase tracking-[0.2em] flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                        {{ $t('analytics.subtitle') }}
                    </p>
                    <h1 class="text-3xl font-black text-white tracking-tight leading-none">{{ $t('analytics.title') }}</h1>
                </div>
            </header>

            <div class="grid grid-cols-2 gap-3 mb-5 lg:mb-6 animate-fade-in-up delay-100">
                <div
                    class="bg-linear-to-br from-green-900 to-green-800/50 p-3 lg:p-4 rounded-xl border border-white/10 relative overflow-hidden group">
                    <div class="flex items-center gap-1.5 mb-1 lg:mb-2">
                        <div class="w-1 h-1.5 lg:w-1.5 lg:h-1.5 rounded-full bg-green-400"></div>
                        <p class="text-2xs font-bold text-gray-400 uppercase tracking-widest">{{ $t('types.income') }}</p>
                    </div>
                    <p class="text-sm lg:text-md font-black text-green-400 tracking-tighter wrap-break-words relative z-10 leading-tight">
                        <span class="text-2xs mr-0.5 opacity-70">+Rp</span>{{ formatNumber(totalIncome) }}
                    </p>
                </div>
                <div
                    class="bg-linear-to-br from-red-900 to-red-800/50 p-3 lg:p-4 rounded-xl border border-white/10 relative overflow-hidden group">
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
            <div
                class="bg-linear-to-br from-gray-900 to-gray-800 border border-gray-500/10 p-4 lg:p-6 rounded-xl mb-5 lg:mb-8 animate-fade-in-up delay-200 relative overflow-hidden group">
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
            <div
                class="bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 p-4 lg:p-6 rounded-xl mb-5 lg:mb-8 animate-fade-in-up delay-300 relative overflow-hidden group">
                <div class="flex items-center justify-between gap-2 mb-4 lg:mb-6 relative z-10">
                    <h2 class="text-2xs lg:text-sm font-bold text-white uppercase tracking-widest shrink-0">{{ $t('analytics.cashflow') }}</h2>
                    <div class="flex bg-gray-900 border border-white/10 rounded-lg p-0.5 lg:p-1 relative overflow-hidden">
                        <div class="absolute top-0.5 bottom-0.5 left-0.5 w-[calc(33.33%-0.25rem)] bg-linear-to-br from-purple-500 to-purple-800 rounded-md transition-all duration-300 ease-out z-0"
                            :style="{ transform: barView === 'harian' ? 'translateX(0)' : (barView === 'mingguan' ? 'translateX(100%)' : 'translateX(200%)') }">
                        </div>
                        <button @click="renderBarChart('harian')"
                            :class="['relative z-10 text-2xs font-bold uppercase tracking-widest px-2 lg:px-3 py-1.5 rounded-md transition-all duration-300', barView === 'harian' ? 'text-white' : 'text-gray-500 hover:text-white']">{{ $t('analytics.view.daily') }}</button>
                        <button @click="renderBarChart('mingguan')"
                            :class="['relative z-10 text-2xs font-bold uppercase tracking-widest px-2 lg:px-3 py-1.5 rounded-md transition-all duration-300', barView === 'mingguan' ? 'text-white' : 'text-gray-500 hover:text-white']">{{ $t('analytics.view.weekly') }}</button>
                        <button @click="renderBarChart('bulanan')"
                            :class="['relative z-10 text-2xs font-bold uppercase tracking-widest px-2 lg:px-3 py-1.5 rounded-md transition-all duration-300', barView === 'bulanan' ? 'text-white' : 'text-gray-500 hover:text-white']">{{ $t('analytics.view.monthly') }}</button>
                    </div>
                </div>

                <div ref="barScrollBox" class="overflow-x-auto no-scrollbar pb-1">
                    <div ref="barChartContainerRef" style="min-width: 100%; height: 160px; lg:height: 180px;">
                        <canvas ref="barChartRef" :key="barChartKey"></canvas>
                    </div>
                </div>
            </div>

            <!-- CATEGORY SECTION -->
            <div class="flex items-center gap-2 mb-3 lg:mb-4 px-1 animate-fade-in-up delay-400">
                <h2 class="text-2xs font-bold text-white uppercase tracking-widest">{{ $t('analytics.categoryBreakdown') }}</h2>
                <div class="flex-1 h-px bg-linear-to-r from-purple-500 to-transparent"></div>
            </div>
            <div
                class="flex bg-gray-900 border border-white/10 rounded-xl p-1 mb-4 lg:mb-5 animate-fade-in-up delay-400 relative">
                <div class="absolute top-1 bottom-1 left-1 w-[calc(25%-0.25rem)] bg-linear-to-br from-purple-500 to-purple-800 border border-white/10 rounded-lg transition-all duration-300 ease-out z-0"
                    :style="{ transform: categoryView === 'expense' ? 'translateX(0)' : categoryView === 'income' ? 'translateX(100%)' : categoryView === 'debt' ? 'translateX(200%)' : 'translateX(300%)' }">
                </div>
                <button @click="switchCategory('expense')"
                    :class="['relative z-10 flex-1 text-2xs font-bold uppercase tracking-widest py-2 lg:py-3 transition-colors duration-300', categoryView === 'expense' ? 'text-white' : 'text-gray-500 hover:text-white']">{{ $t('analytics.categoryTab.expense') }}</button>
                <button @click="switchCategory('income')"
                    :class="['relative z-10 flex-1 text-2xs font-bold uppercase tracking-widest py-2 lg:py-3 transition-colors duration-300', categoryView === 'income' ? 'text-white' : 'text-gray-500 hover:text-white']">{{ $t('analytics.categoryTab.income') }}</button>
                <button @click="switchCategory('debt')"
                    :class="['relative z-10 flex-1 text-2xs font-bold uppercase tracking-widest py-2 lg:py-3 transition-colors duration-300', categoryView === 'debt' ? 'text-white' : 'text-gray-500 hover:text-white']">{{ $t('analytics.categoryTab.debt') }}</button>
                <button @click="switchCategory('receivable')"
                    :class="['relative z-10 flex-1 text-2xs font-bold uppercase tracking-widest py-2 lg:py-3 transition-colors duration-300', categoryView === 'receivable' ? 'text-white' : 'text-gray-500 hover:text-white']">{{ $t('analytics.categoryTab.receivable') }}</button>
            </div>

            <!-- DOUGHNUT CHART -->
            <div
                class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-4 lg:p-6 rounded-xl mb-5 lg:mb-8 animate-fade-in-up delay-500 relative overflow-hidden group">
                <div v-if="!activeCategoryData.labels.length" class="flex flex-col items-center justify-center py-8 lg:py-10">
                    <span
                        class="w-10 h-10 lg:w-12 lg:h-12 bg-gray-800 rounded-xl flex items-center justify-center text-base lg:text-xl mb-3 border border-white/10">📭</span>
                    <p class="text-2xs font-bold text-white uppercase tracking-widest">{{ $t('analytics.noData') }}</p>
                </div>
                <template v-else>
                    <div class="relative w-full h-48 lg:h-56 mb-5 lg:mb-6">
                        <canvas id="mainChart" :key="doughnutChartKey" class="relative z-10 w-full h-full"></canvas>
                        <div
                            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center justify-center pointer-events-none z-0">
                            <div
                                class="w-[100px] lg:w-[110px] h-[100px] lg:h-[110px] rounded-full bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 flex flex-col items-center justify-center text-center px-1">
                                <span class="text-[10px] lg:text-2xs text-gray-500 font-bold uppercase tracking-widest mb-1">{{
                                    activeCategoryData.labelName }}</span> <span
                                    class="text-2xs lg:text-2xs font-black text-white tracking-tighter leading-tight w-full wrap-break-word">Rp
                                    {{ formatNumber(activeCategoryData.total) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-3 lg:space-y-4">
                        <Link v-for="(label, i) in activeCategoryData.labels" :key="activeCategoryData.ids[i]"
                            :href="route('categories.show', {
                                category: activeCategoryData.ids[i],
                                start_date: startDate,
                                end_date: endDate,
                            })"
                            class="relative flex items-center justify-between bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 p-2.5 lg:p-3 rounded-xl overflow-hidden group hover:border-purple-500/30 transition-all duration-300">
                            <div class="flex items-center gap-2.5 lg:gap-3 relative z-10 w-full">
                                <div class="w-1 lg:w-1.5 h-5 lg:h-6 rounded-full"
                                    :style="{ backgroundColor: categoryColors[i] }">
                                </div>
                                <AppIcon :icon="activeCategoryData.icons[i]" class="w-5 h-5 lg:w-6 lg:h-6 text-purple-400 shrink-0" />
                                <div class="flex-1 min-w-0 pr-1 lg:pr-2">
                                    <p class="text-xs font-bold text-gray-200 truncate">{{ label }}</p>
                                    <p class="text-2xs text-gray-500 font-bold">{{ activeCategoryData.total > 0 ?
                                        ((activeCategoryData.values[i] / activeCategoryData.total) * 100).toFixed(1) : 0
                                    }}%</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-2xs lg:text-xs font-black text-white block">Rp {{
                                        formatNumber(activeCategoryData.values[i]) }}</span>
                                </div>
                            </div>
                        </Link>
                    </div>
                </template>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in-up {
    0% {
        opacity: 0;
        transform: translateY(15px);
    }

    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
}

.delay-100 {
    animation-delay: 100ms;
}

.delay-200 {
    animation-delay: 200ms;
}

.delay-300 {
    animation-delay: 300ms;
}

.delay-400 {
    animation-delay: 400ms;
}

.delay-500 {
    animation-delay: 500ms;
}
</style>
