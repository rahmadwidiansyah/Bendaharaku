<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DateModal from '@/Components/DateModal.vue';
import CreateTransactionFab from '@/Components/CreateTransactionFab.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, shallowRef, onMounted, watch, computed, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';
import { formatNumber } from '@/utils/format.js';

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

    let grad = ctx.createLinearGradient(0, 0, 0, 140);
    grad.addColorStop(0, 'rgba(252,165,255,0.4)');
    grad.addColorStop(1, 'rgba(252,165,255,0)');

    charts.value['cumulative'] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: props.dailyLabels,
            datasets: [{
                data: props.cumulativeData,
                borderColor: '#FCA5FF',
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

const renderBarChart = async (view) => {
    barView.value = view;
    let labels = [], incomes = [], expenses = [], debts = [], receivables = [];

    if (view === 'harian') {
        labels = props.allDailyLabels || [];
        incomes = props.allDailyIncome || [];
        expenses = props.allDailyExpense || [];
        debts = props.allDailyDebt || [];
        receivables = props.allDailyReceivable || [];
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
    barChartKey.value++; // Force canvas reset
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
                { label: 'In', data: incomes, backgroundColor: '#34D399', borderRadius: 4 },
                { label: 'Out', data: expenses, backgroundColor: '#FCA5FF', borderRadius: 4 },
                { label: 'Hutang', data: debts, backgroundColor: '#FBBF24', borderRadius: 4 },
                { label: 'Piutang', data: receivables, backgroundColor: '#C084FC', borderRadius: 4 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 400 }, // Short animation
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#9CA3AF', font: { size: 12, weight: 'bold' } } },
                y: { display: false }
            }
        }
    });

    setTimeout(() => {
        if (barScrollBox.value) {
            barScrollBox.value.scrollLeft = barScrollBox.value.scrollWidth;
        }
    }, 100);
};

const activeCategoryData = computed(() => {
    if (categoryView.value === 'expense') {
        return { labels: props.expensesByCategory.map(x => x.name), values: props.expensesByCategory.map(x => x.total), ids: props.expensesByCategory.map(x => x.id), icons: props.expensesByCategory.map(x => x.icon), total: props.totalExpense, labelName: 'Total Pengeluaran' };
    } else if (categoryView.value === 'income') {
        return { labels: props.incomesByCategory.map(x => x.name), values: props.incomesByCategory.map(x => x.total), ids: props.incomesByCategory.map(x => x.id), icons: props.incomesByCategory.map(x => x.icon), total: props.totalIncome, labelName: 'Total Pemasukan' };
    } else if (categoryView.value === 'debt') {
        return { labels: props.debtsByCategory.map(x => x.name), values: props.debtsByCategory.map(x => x.total), ids: props.debtsByCategory.map(x => x.id), icons: props.debtsByCategory.map(x => x.icon), total: props.totalDebt, labelName: 'Total Hutang' };
    } else {
        return { labels: props.receivablesByCategory.map(x => x.name), values: props.receivablesByCategory.map(x => x.total), ids: props.receivablesByCategory.map(x => x.id), icons: props.receivablesByCategory.map(x => x.icon), total: props.totalReceivable, labelName: 'Total Piutang' };
    }
});

const activeColors = computed(() => {
    return categoryView.value === 'expense'
        ? ['#FCA5FF', '#A78BFA', '#818CF8', '#60A5FA', '#38BDF8', '#4ADE80']
        : categoryView.value === 'income'
            ? ['#34D399', '#6EE7B7', '#A7F3D0', '#10B981']
            : categoryView.value === 'debt'
                ? ['#FBBF24', '#FCD34D', '#FDE68A', '#FEF3C7']
                : ['#C084FC', '#D8B4FE', '#E9D5FF', '#F3E8FF'];
});

const renderDoughnutChart = async () => {
    const dataObj = activeCategoryData.value;
    destroyChart('doughnut');
    doughnutChartKey.value++;
    await nextTick();

    const ctx = document.getElementById('mainChart')?.getContext('2d');
    if (!ctx || !dataObj.labels.length) return;

    const colors = activeColors.value;

    charts.value['doughnut'] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: dataObj.labels,
            datasets: [{
                data: dataObj.values,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#121212'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '80%',
            animation: { duration: 400 },
            plugins: { legend: { display: false } }
        }
    });
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

        <div class="p-5 pb-40 w-full lg:max-w-4xl mx-auto lg:px-8 relative z-10 overflow-x-hidden">

            <header class="flex justify-between items-end mb-6 pt-4 animate-fade-in-up">
                <div>
                    <p
                        class="text-2xs text-purple-500 font-black mb-1.5 uppercase tracking-[0.2em] flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                        Laporan
                    </p>
                    <h1 class="text-3xl font-black text-white tracking-tight leading-none">Analitik</h1>
                </div>

                <DateModal :action="route('analytics.index')" :start-date="startDate" :end-date="endDate" />
            </header>

            <div class="grid grid-cols-2 gap-3 mb-6 animate-fade-in-up delay-100">
                <div
                    class="bg-linear-to-br from-green-900 to-green-800/50 p-4 rounded-xl border border-white/10 relative overflow-hidden group">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-1.5 h-1.5 rounded-full bg-green-400"></div>
                        <p class="text-2xs font-bold text-gray-400 uppercase tracking-widest">Pemasukan</p>
                    </div>
                    <p
                        class="text-md font-black text-green-400 tracking-tighter wrap-break-words relative z-10 leading-tight">
                        <span class="text-2xs mr-0.5 opacity-70">+Rp</span>{{ formatNumber(totalIncome) }}
                    </p>
                </div>
                <div
                    class="bg-linear-to-br from-red-900 to-red-800/50 p-4 rounded-xl border border-white/10 relative overflow-hidden group">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-1.5 h-1.5 rounded-full bg-red-400"></div>
                        <p class="text-2xs font-bold text-gray-400 uppercase tracking-widest">Pengeluaran</p>
                    </div>
                    <p class="text-md font-black text-red-400 tracking-tighter wrap-break-words relative z-10 leading-tight">
                        <span class="text-red-400 text-2xs mr-0.5 opacity-70">-Rp</span>{{ formatNumber(totalExpense) }}
                    </p>
                </div>
            </div>

            <!-- CUMULATIVE CHART -->
            <div
                class="bg-linear-to-br from-gray-900 to-gray-800 border border-gray-500/10 p-6 rounded-xl mb-8 animate-fade-in-up delay-200 relative overflow-hidden group">
                <div class="flex justify-between items-start mb-6 relative z-10">
                    <div>
                        <p class="text-xs font-bold text-white uppercase tracking-[0.2em] mb-1">Saldo Kumulatif</p>
                        <p class="text-2xs text-gray-500 font-medium">Pergerakan total kekayaan</p>
                    </div>
                    <p
                        class="text-lg font-black text-white tracking-tight bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 px-3 py-1.5 rounded-xl">
                        <span class="text-2xs text-gray-500 mr-1">Rp</span>{{ formatNumber(cumulativeBalance) }}
                    </p>
                </div>
                <div class="w-full h-[140px] relative z-10">
                    <canvas id="cumulativeChart" :key="cumulativeChartKey"></canvas>
                </div>
            </div>

            <!-- BAR CHART -->
            <div
                class="bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 p-6 rounded-xl mb-8 animate-fade-in-up delay-300 relative overflow-hidden group">
                <div class="flex justify-between items-center mb-6 relative z-10">
                    <h2 class="text-sm font-bold text-white uppercase tracking-widest">Arus Kas</h2>
                    <div class="flex bg-gray-900 border border-white/10 rounded-lg p-1 relative overflow-hidden">
                        <div class="absolute top-1 bottom-1 left-1 w-[calc(33.33%-0.25rem)] bg-linear-to-br from-purple-500 to-purple-800 rounded-md transition-all duration-300 ease-out z-0"
                            :style="{ transform: barView === 'harian' ? 'translateX(0)' : (barView === 'mingguan' ? 'translateX(100%)' : 'translateX(200%)') }">
                        </div>
                        <button @click="renderBarChart('harian')"
                            :class="['relative z-10 text-2xs font-bold uppercase tracking-widest px-3 py-1.5 rounded-md transition-all duration-300', barView === 'harian' ? 'text-white' : 'text-gray-500 hover:text-white']">Hari</button>
                        <button @click="renderBarChart('mingguan')"
                            :class="['relative z-10 text-2xs font-bold uppercase tracking-widest px-3 py-1.5 rounded-md transition-all duration-300', barView === 'mingguan' ? 'text-white' : 'text-gray-500 hover:text-white']">Pekan</button>
                        <button @click="renderBarChart('bulanan')"
                            :class="['relative z-10 text-2xs font-bold uppercase tracking-widest px-3 py-1.5 rounded-md transition-all duration-300', barView === 'bulanan' ? 'text-white' : 'text-gray-500 hover:text-white']">Bulan</button>
                    </div>
                </div>

                <div ref="barScrollBox" class="overflow-x-auto no-scrollbar pb-1">
                    <div ref="barChartContainerRef" style="min-width: 100%; height: 180px;">
                        <canvas ref="barChartRef" :key="barChartKey"></canvas>
                    </div>
                </div>
            </div>

            <!-- CATEGORY SECTION -->
            <div class="flex items-center gap-2 mb-4 px-1 animate-fade-in-up delay-400">
                <h2 class="text-2xs font-bold text-white uppercase tracking-widest">Rincian Kategori</h2>
                <div class="flex-1 h-px bg-linear-to-r from-purple-500 to-transparent"></div>
            </div>
            <div
                class="flex bg-gray-900 border border-white/10 rounded-xl p-1.5 mb-5 animate-fade-in-up delay-400 relative">
                <div class="absolute top-1.5 bottom-1.5 left-1.5 w-[calc(25%-0.375rem)] bg-linear-to-br from-purple-500 to-purple-800 border border-white/10 rounded-xl transition-all duration-300 ease-out z-0"
                    :style="{ transform: categoryView === 'expense' ? 'translateX(0)' : categoryView === 'income' ? 'translateX(100%)' : categoryView === 'debt' ? 'translateX(200%)' : 'translateX(300%)' }">
                </div>
                <button @click="switchCategory('expense')"
                    :class="['relative z-10 flex-1 text-2xs font-bold uppercase tracking-widest py-3 transition-colors duration-300', categoryView === 'expense' ? 'text-white' : 'text-gray-500 hover:text-white']">Keluar</button>
                <button @click="switchCategory('income')"
                    :class="['relative z-10 flex-1 text-2xs font-bold uppercase tracking-widest py-3 transition-colors duration-300', categoryView === 'income' ? 'text-white' : 'text-gray-500 hover:text-white']">Masuk</button>
                <button @click="switchCategory('debt')"
                    :class="['relative z-10 flex-1 text-2xs font-bold uppercase tracking-widest py-3 transition-colors duration-300', categoryView === 'debt' ? 'text-white' : 'text-gray-500 hover:text-white']">Hutang</button>
                <button @click="switchCategory('receivable')"
                    :class="['relative z-10 flex-1 text-2xs font-bold uppercase tracking-widest py-3 transition-colors duration-300', categoryView === 'receivable' ? 'text-white' : 'text-gray-500 hover:text-white']">Piutang</button>
            </div>

            <!-- DOUGHNUT CHART -->
            <div
                class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-6 rounded-xl mb-8 animate-fade-in-up delay-500 relative overflow-hidden group">
                <div v-if="!activeCategoryData.labels.length" class="flex flex-col items-center justify-center py-10">
                    <span
                        class="w-12 h-12 bg-gray-800 rounded-xl flex items-center justify-center text-xl mb-3 border border-white/10">📭</span>
                    <p class="text-2xs font-bold text-white uppercase tracking-widest">Tidak Ada Data</p>
                </div>
                <template v-else>
                    <div class="relative w-full h-56 mb-6">
                        <canvas id="mainChart" :key="doughnutChartKey" class="relative z-10 w-full h-full"></canvas>
                        <div
                            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center justify-center pointer-events-none z-0">
                            <div
                                class="w-[110px] h-[110px] rounded-full bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 flex flex-col items-center justify-center text-center px-1">
                                <span class="text-2xs text-gray-500 font-bold uppercase tracking-widest mb-1">{{
                                    activeCategoryData.labelName }}</span> <span
                                    class="text-2xs font-black text-white tracking-tighter leading-tight w-full wrap-break-word">Rp
                                    {{ formatNumber(activeCategoryData.total) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <Link v-for="(label, i) in activeCategoryData.labels" :key="activeCategoryData.ids[i]"
                            :href="route('categories.show', {
                                category: activeCategoryData.ids[i],
                                start_date: startDate,
                                end_date: endDate,
                            })"
                            class="relative flex items-center justify-between bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 p-3 rounded-xl overflow-hidden group hover:border-purple-500/30 transition-all duration-300">
                            <div class="flex items-center gap-3 relative z-10 w-full">
                                <div class="w-1.5 h-6 rounded-full"
                                    :style="{ backgroundColor: activeColors[i % activeColors.length] }">
                                </div>
                                <div
                                    class="w-8 h-8 rounded-xl bg-gray-900 flex items-center justify-center border border-white/10 overflow-hidden p-0.5">
                                    <img v-if="activeCategoryData.icons[i]?.includes('.')"
                                        :src="'/storage/' + activeCategoryData.icons[i]"
                                        class="w-full h-full object-cover">
                                    <span v-else class="text-sm">{{ activeCategoryData.icons[i] || '📁' }}</span>
                                </div>
                                <div class="flex-1 min-w-0 pr-2">
                                    <p class="text-xs font-bold text-gray-200 truncate">{{ label }}</p>
                                    <p class="text-2xs text-gray-500 font-bold">{{ activeCategoryData.total > 0 ?
                                        ((activeCategoryData.values[i] / activeCategoryData.total) * 100).toFixed(1) : 0
                                    }}%</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-xs font-black text-white block">Rp {{
                                        formatNumber(activeCategoryData.values[i]) }}</span>
                                </div>
                            </div>
                        </Link>
                    </div>
                </template>
            </div>
        </div>

        <CreateTransactionFab />
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
