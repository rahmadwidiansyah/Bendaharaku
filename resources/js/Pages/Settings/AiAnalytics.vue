<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, shallowRef } from 'vue';
import axios from 'axios';
import Chart from 'chart.js/auto';
import { formatNumber, formatUSD } from '@/utils/format.js';

const isLoading = ref(true);
const overview = ref({});
const learning = ref({});
const dateRange = ref(30);

// Refs for Chart Canvases
const providerChartRef = ref(null);
const performanceChartRef = ref(null);

// Store chart instances to destroy them before re-rendering
const chartInstances = shallowRef({ provider: null, performance: null });

const fetchData = async () => {
    isLoading.value = true;
    try {
        const endDate = new Date().toISOString().split('T')[0];
        const startDate = new Date(new Date().setDate(new Date().getDate() - dateRange.value)).toISOString().split('T')[0];

        const [dashRes, feedRes] = await Promise.all([
            axios.get(route('api.ai.analytics.dashboard'), { params: { start_date: startDate, end_date: endDate } }),
            axios.get(route('api.ai.analytics.feedback'))
        ]);

        overview.value = dashRes.data.overview;
        learning.value = feedRes.data.learning;
        
        renderCharts(dashRes.data.providers, dashRes.data.performance);
    } catch (error) {
        console.error("Gagal memuat data analitik:", error);
    } finally {
        isLoading.value = false;
    }
};

const renderCharts = (providersData, performanceData) => {
    // 1. Provider Pie Chart
    if (chartInstances.value.provider) chartInstances.value.provider.destroy();
    if (providerChartRef.value) {
        chartInstances.value.provider = new Chart(providerChartRef.value, {
            type: 'doughnut',
            data: {
                labels: Object.keys(providersData).map(k => k.toUpperCase()),
                datasets: [{
                    data: Object.values(providersData),
                    backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#f43f5e'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { color: '#9ca3af', font: { family: 'monospace' } } } },
                cutout: '75%'
            }
        });
    }

    // 2. Performance Line Chart
    if (chartInstances.value.performance) chartInstances.value.performance.destroy();
    if (performanceChartRef.value) {
        chartInstances.value.performance = new Chart(performanceChartRef.value, {
            type: 'line',
            data: {
                labels: performanceData.map(d => d.date),
                datasets: [
                    {
                        label: 'Avg Raw Confidence',
                        data: performanceData.map(d => parseFloat(d.raw_conf) * 100),
                        borderColor: '#f59e0b', // Amber
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Avg Final Confidence',
                        data: performanceData.map(d => parseFloat(d.final_conf) * 100),
                        borderColor: '#10b981', // Emerald
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { labels: { color: '#9ca3af' } } },
                scales: {
                    x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280' } },
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280' }, min: 0, max: 100 }
                }
            }
        });
    }
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">
        <Head title="AI Analytics Dashboard" />

        <div class="fixed top-[-10%] left-[50%] -translate-x-1/2 w-[500px] h-[500px] bg-indigo-600 blur-[150px] opacity-[0.15] rounded-full pointer-events-none z-0"></div>

        <div class="p-5 pb-32 w-full lg:max-w-7xl mx-auto lg:px-8 relative z-10 animate-slide-up min-h-screen">
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pt-4 gap-4">
                <div>
                    <p class="text-2xs text-indigo-400 font-black mb-1.5 uppercase tracking-[0.2em] flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                        SPRINT 4E.6
                    </p>
                    <h1 class="text-3xl font-black text-white tracking-tight leading-none">AI Analytics</h1>
                </div>

                <div class="flex items-center gap-3">
                    <select v-model="dateRange" @change="fetchData" class="bg-gray-900 border border-white/10 text-white rounded-xl py-2 px-4 text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-400">
                        <option :value="7">7 Hari Terakhir</option>
                        <option :value="30">30 Hari Terakhir</option>
                        <option :value="90">90 Hari Terakhir</option>
                    </select>
                    <Link :href="route('settings.ai.index')" class="px-4 py-2 bg-gray-900 text-gray-300 border border-white/10 text-2xs font-bold uppercase tracking-widest rounded-xl hover:border-indigo-500/40 hover:text-white transition-all">
                        Kembali
                    </Link>
                </div>
            </header>

            <div v-if="isLoading" class="flex justify-center items-center py-20">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
            </div>

            <div v-else class="space-y-6">
                <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                    <div class="bg-gray-900/50 border border-white/10 p-5 rounded-2xl flex flex-col justify-between">
                        <p class="text-2xs text-gray-400 font-bold uppercase tracking-widest mb-2">Total Requests</p>
                        <h3 class="text-2xl font-black text-white">{{ formatNumber(overview.total_requests) }}</h3>
                    </div>
                    <div class="bg-gray-900/50 border border-white/10 p-5 rounded-2xl flex flex-col justify-between relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent pointer-events-none"></div>
                        <p class="text-2xs text-gray-400 font-bold uppercase tracking-widest mb-2">Success Rate</p>
                        <h3 class="text-2xl font-black text-emerald-400">{{ overview.success_rate }}%</h3>
                    </div>
                    <div class="bg-gray-900/50 border border-white/10 p-5 rounded-2xl flex flex-col justify-between">
                        <p class="text-2xs text-gray-400 font-bold uppercase tracking-widest mb-2">Draft Rate</p>
                        <h3 class="text-2xl font-black text-amber-400">{{ overview.draft_rate }}%</h3>
                    </div>
                    <div class="bg-gray-900/50 border border-white/10 p-5 rounded-2xl flex flex-col justify-between relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-rose-500/10 to-transparent pointer-events-none"></div>
                        <p class="text-2xs text-gray-400 font-bold uppercase tracking-widest mb-2">Correction Rate</p>
                        <h3 class="text-2xl font-black text-rose-400">{{ overview.correction_rate }}%</h3>
                    </div>
                    <div class="bg-gray-900/50 border border-white/10 p-5 rounded-2xl flex flex-col justify-between">
                        <p class="text-2xs text-gray-400 font-bold uppercase tracking-widest mb-2">Total Tokens</p>
                        <h3 class="text-2xl font-mono text-indigo-300">{{ formatNumber(overview.total_tokens) }}</h3>
                    </div>
                    <div class="bg-gray-900/50 border border-white/10 p-5 rounded-2xl flex flex-col justify-between">
                        <p class="text-2xs text-gray-400 font-bold uppercase tracking-widest mb-2">Est. Cost</p>
                        <h3 class="text-2xl font-mono text-white">{{ formatUSD(overview.estimated_cost) }}</h3>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-gray-900/50 border border-white/10 p-6 rounded-2xl lg:col-span-1 h-[400px] flex flex-col">
                        <h3 class="text-sm font-bold text-white mb-4">Traffic by Provider</h3>
                        <div class="relative flex-1 w-full">
                            <canvas ref="providerChartRef"></canvas>
                        </div>
                    </div>
                    
                    <div class="bg-gray-900/50 border border-white/10 p-6 rounded-2xl lg:col-span-2 h-[400px] flex flex-col">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-sm font-bold text-white">Confidence Trend</h3>
                                <p class="text-2xs text-gray-400 mt-1">Raw AI vs Sistem Pembobotan Bendaharaku</p>
                            </div>
                        </div>
                        <div class="relative flex-1 w-full">
                            <canvas ref="performanceChartRef"></canvas>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-gray-900/50 border border-white/10 p-6 rounded-2xl">
                        <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            Most Learned Keywords
                        </h3>
                        <div class="space-y-3" v-if="learning.top_memories?.length">
                            <div v-for="mem in learning.top_memories" :key="mem.keyword_pattern" class="flex justify-between items-center p-3 bg-gray-800/50 rounded-lg border border-white/5">
                                <span class="text-sm font-medium text-white">{{ mem.keyword_pattern }}</span>
                                <div class="flex gap-4 text-2xs font-mono text-gray-400">
                                    <span>Hits: <span class="text-white">{{ mem.hit_count }}</span></span>
                                    <span>Weight: <span class="text-indigo-400">{{ parseFloat(mem.effective_weight).toFixed(2) }}</span></span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-500 py-4 text-center">Belum ada memori yang terbentuk.</div>
                    </div>

                    <div class="bg-gray-900/50 border border-white/10 p-6 rounded-2xl">
                        <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Top Corrected Categories (ID)
                        </h3>
                        <div class="space-y-3" v-if="learning.top_corrected_categories?.length">
                            <div v-for="cat in learning.top_corrected_categories" :key="cat.category_id" class="flex justify-between items-center p-3 bg-rose-500/10 rounded-lg border border-rose-500/10">
                                <span class="text-sm font-medium text-rose-300">Kategori ID: {{ cat.category_id }}</span>
                                <span class="text-2xs font-bold px-2 py-1 bg-rose-500/20 text-rose-300 rounded">{{ cat.count }} koreksi</span>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-500 py-4 text-center">Belum ada log koreksi pengguna.</div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes slide-up {
    from { transform: translateY(15px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.animate-slide-up {
    animation: slide-up 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}
</style>