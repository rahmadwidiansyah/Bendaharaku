<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch, onMounted, shallowRef } from 'vue';
import Chart from 'chart.js/auto'; // Pastikan chart.js sudah terinstall

const props = defineProps({
    providerStatuses: { type: Object, required: true },
    availableProviders: { type: Array, required: true },
    modelsByProvider: { type: Object, required: true },
    usageStats: { type: Object, default: () => ({}) },
    recentLogs: { type: Array, default: () => [] }
});

const page = usePage();
const selectedProvider = ref(props.availableProviders[0] || 'gemini');
const isTesting = ref(false);
const testResult = ref(null);
const statusOverrides = ref({});

// --- Analitik State ---
const overview = ref({ total_requests: 0, success_rate: 0, draft_rate: 0, total_tokens: 0 });
const performanceChartRef = ref(null);
const chartInstance = shallowRef(null);

const fetchAnalytics = async () => {
    try {
        const { data } = await axios.get(route('settings.ai.api.dashboard'));
        overview.value = data.overview;
        
        // Konfigurasi Chart (Menggunakan styling yang lebih detail)
        if (chartInstance.value) chartInstance.value.destroy();
        if (performanceChartRef.value) {
            chartInstance.value = new Chart(performanceChartRef.value, {
                type: 'line',
                data: {
                    labels: data.performance.map(d => d.date),
                    datasets: [{
                        label: 'Final Confidence',
                        data: data.performance.map(d => parseFloat(d.final_conf) * 100),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#9ca3af' } } },
                    scales: { 
                        y: { min: 0, max: 100, ticks: { color: '#6b7280' } }, 
                        x: { ticks: { color: '#6b7280' } } 
                    }
                }
            });
        }
    } catch (e) { 
        console.error("Gagal load analitik", e); 
    }
};

// --- Form & Settings Logic ---
const form = useForm({
    provider: selectedProvider.value,
    api_key: '',
    selected_model: '',
    is_active_provider: false,
});

const providerLabel = (provider) => provider.toUpperCase();

const syncFormWithProps = (provider) => {
    const config = props.providerStatuses[provider];
    const models = props.modelsByProvider[provider] || [];

    form.provider = provider;
    form.api_key = '';
    form.selected_model = config?.selected_model || models[0] || '';
    form.is_active_provider = config?.is_active_provider || false;
    testResult.value = null;
};

watch(selectedProvider, (newProvider) => {
    syncFormWithProps(newProvider);
}, { immediate: true });

const currentStatus = computed(() => {
    return statusOverrides.value[selectedProvider.value]
        || props.providerStatuses[selectedProvider.value]?.status
        || 'Not Configured';
});

const currentModels = computed(() => props.modelsByProvider[selectedProvider.value] || []);

const runConnectionTest = async () => {
    isTesting.value = true;
    testResult.value = null;

    try {
        const response = await axios.post(route('settings.ai.test'), {
            provider: form.provider,
            api_key: form.api_key,
        });

        testResult.value = {
            success: Boolean(response.data.success),
            message: response.data.message,
        };

        if (response.data.success) {
            statusOverrides.value = {
                ...statusOverrides.value,
                [form.provider]: 'Connected',
            };
        }
    } catch (error) {
        testResult.value = {
            success: false,
            message: error.response?.data?.message || 'Gagal terhubung dengan server penguji.',
        };
    } finally {
        isTesting.value = false;
    }
};

const submitSettings = () => {
    form.patch(route('settings.ai.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.api_key = '';
            testResult.value = null;
            statusOverrides.value = {};
        },
    });
};

const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(num || 0);

onMounted(() => {
    fetchAnalytics();
});
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">
        <Head title="Pengaturan AI" />
        
        <div class="fixed top-[-10%] left-[50%] -translate-x-1/2 w-[400px] h-[400px] bg-indigo-500 blur-[150px] opacity-[0.14] rounded-full pointer-events-none z-0"></div>

        <div class="p-5 pb-32 w-full lg:max-w-4xl mx-auto lg:px-8 relative z-10 animate-slide-up min-h-screen">
            <header class="flex justify-between items-center mb-8 pt-4">
                <div>
                    <p class="text-2xs text-indigo-400 font-black mb-1.5 uppercase tracking-[0.2em] flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span> BYOK AI
                    </p>
                    <h1 class="text-3xl font-black text-white tracking-tight leading-none">Pengaturan AI</h1>
                </div>
                <Link :href="route('settings.index')" class="px-4 py-2 bg-gray-900 text-gray-300 border border-white/10 text-2xs font-bold uppercase tracking-widest rounded-xl hover:border-indigo-500/40 hover:text-white transition-all">
                    Kembali
                </Link>
            </header>

            <div v-if="page.props.flash?.success" class="mb-5 p-4 rounded-xl bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 text-sm font-medium">
                {{ page.props.flash.success }}
            </div>

            <div v-if="page.props.flash?.error" class="mb-5 p-4 rounded-xl bg-rose-500/10 text-rose-300 border border-rose-500/20 text-sm font-medium">
                {{ page.props.flash.error }}
            </div>

            <div class="space-y-6">
                <div class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-6 rounded-2xl">
                    <div class="border-b border-white/10 pb-5 mb-6">
                        <h2 class="text-xl font-bold text-white">Integrasi Kecerdasan Buatan</h2>
                        <p class="text-sm text-gray-400 mt-1">Gunakan kunci API personal untuk mengaktifkan asisten finansial cerdas.</p>
                    </div>

                    <form @submit.prevent="submitSettings" class="space-y-6">
                        <div>
                            <label class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2">Penyedia AI</label>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                <select v-model="selectedProvider" class="w-full sm:max-w-xs bg-gray-900 border border-white/10 text-white rounded-xl p-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-400 transition-all">
                                    <option v-for="provider in availableProviders" :key="provider" :value="provider">
                                        {{ providerLabel(provider) }}
                                    </option>
                                </select>
                                <span class="px-3 py-2 rounded-full text-2xs font-black tracking-widest uppercase border w-fit" 
                                      :class="{
                                          'bg-emerald-500/10 text-emerald-300 border-emerald-500/20': currentStatus === 'Connected', 
                                          'bg-rose-500/10 text-rose-300 border-rose-500/20': currentStatus === 'Invalid', 
                                          'bg-gray-700/40 text-gray-300 border-white/10': currentStatus === 'Not Configured'
                                      }">
                                    {{ currentStatus }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2">Kunci API Personal</label>
                            <input type="password" v-model="form.api_key" placeholder="Kosongkan jika tidak ingin mengganti token tersimpan" class="w-full bg-gray-900 border border-white/10 text-white rounded-xl p-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-400 transition-all" />
                        </div>

                        <div>
                            <label class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2">Varian Model</label>
                            <select v-model="form.selected_model" class="w-full sm:max-w-xs bg-gray-900 border border-white/10 text-white rounded-xl p-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-400 transition-all">
                                <option v-for="model in currentModels" :key="model" :value="model">{{ model }}</option>
                            </select>
                        </div>

                        <label class="flex items-start gap-3 bg-gray-900/80 p-4 rounded-xl border border-white/10 cursor-pointer">
                            <input type="checkbox" v-model="form.is_active_provider" class="mt-1 h-4 w-4 rounded border-white/10 text-indigo-500 bg-gray-800 focus:ring-indigo-500" />
                            <span><span class="block text-sm font-bold text-white">Jadikan otak pemrosesan utama</span></span>
                        </label>

                        <div v-if="testResult" :class="['p-3 rounded-lg text-sm border', testResult.success ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20' : 'bg-rose-500/10 text-rose-300 border-rose-500/20']">
                            {{ testResult.message }}
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center sm:justify-end gap-3 pt-5 border-t border-white/10">
                            <button type="button" @click="runConnectionTest" :disabled="isTesting" class="px-4 py-3 border border-white/10 rounded-xl text-2xs font-bold uppercase tracking-widest text-gray-300 bg-gray-900 hover:border-indigo-500/40 disabled:opacity-50">
                                {{ isTesting ? 'Menguji...' : 'Test Connection' }}
                            </button>
                            <button type="submit" :disabled="form.processing" class="px-5 py-3 rounded-xl text-2xs font-bold uppercase tracking-widest text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                                Simpan Pengaturan
                            </button>
                        </div>
                    </form>

                    <div class="mt-12 space-y-6">
                        <div class="border-b border-white/10 pb-5">
                            <h2 class="text-xl font-bold text-white">Analitik Performa AI</h2>
                        </div>
                        
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="bg-gray-900 border border-white/5 p-4 rounded-xl">
                                <p class="text-2xs text-gray-400 uppercase">Requests</p>
                                <h3 class="text-xl font-black text-white">{{ formatNumber(overview.total_requests) }}</h3>
                            </div>
                            <div class="bg-gray-900 border border-emerald-500/10 p-4 rounded-xl">
                                <p class="text-2xs text-emerald-400 uppercase">Success</p>
                                <h3 class="text-xl font-black text-emerald-400">{{ overview.success_rate }}%</h3>
                            </div>
                            <div class="bg-gray-900 border border-amber-500/10 p-4 rounded-xl">
                                <p class="text-2xs text-amber-400 uppercase">Drafts</p>
                                <h3 class="text-xl font-black text-amber-400">{{ overview.draft_rate }}%</h3>
                            </div>
                            <div class="bg-gray-900 border border-indigo-500/10 p-4 rounded-xl">
                                <p class="text-2xs text-indigo-400 uppercase">Tokens</p>
                                <h3 class="text-xl font-mono text-indigo-300">{{ formatNumber(overview.total_tokens) }}</h3>
                            </div>
                        </div>

                        <div class="bg-gray-900 border border-white/5 p-6 rounded-2xl h-[300px]">
                            <canvas ref="performanceChartRef"></canvas>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-1 bg-gray-900/50 border border-white/10 p-6 rounded-2xl h-fit">
                    </div>
                    <div class="lg:col-span-2 bg-gray-900/50 border border-white/10 p-6 rounded-2xl">
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>