<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    providerStatuses: {
        type: Object,
        required: true,
    },
    availableProviders: {
        type: Array,
        required: true,
    },
    modelsByProvider: {
        type: Object,
        required: true,
    },
    usageStats: {
        type: Object,
        default: () => ({})
    },
    recentLogs: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const selectedProvider = ref(props.availableProviders[0] || 'gemini');
const isTesting = ref(false);
const testResult = ref(null);
const statusOverrides = ref({});

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

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">
        <Head title="Pengaturan AI" />

        <div
            class="fixed top-[-10%] left-[50%] -translate-x-1/2 w-[400px] h-[400px] bg-indigo-500 blur-[150px] opacity-[0.14] rounded-full pointer-events-none z-0">
        </div>

        <div class="p-5 pb-32 w-full lg:max-w-4xl mx-auto lg:px-8 relative z-10 animate-slide-up min-h-screen">
            <header class="flex justify-between items-center mb-8 pt-4">
                <div>
                    <p
                        class="text-2xs text-indigo-400 font-black mb-1.5 uppercase tracking-[0.2em] flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                        BYOK AI
                    </p>
                    <h1 class="text-3xl font-black text-white tracking-tight leading-none">Pengaturan AI</h1>
                </div>

                <Link
                    :href="route('settings.index')"
                    class="px-4 py-2 bg-gray-900 text-gray-300 border border-white/10 text-2xs font-bold uppercase tracking-widest rounded-xl hover:border-indigo-500/40 hover:text-white transition-all">
                    Kembali
                </Link>
            </header>

            <div v-if="page.props.flash?.success"
                class="mb-5 p-4 rounded-xl bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 text-sm font-medium">
                {{ page.props.flash.success }}
            </div>

            <div v-if="page.props.flash?.error"
                class="mb-5 p-4 rounded-xl bg-rose-500/10 text-rose-300 border border-rose-500/20 text-sm font-medium">
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
                            <label class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2">
                                Penyedia AI
                            </label>

                            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                <select
                                    v-model="selectedProvider"
                                    class="w-full sm:max-w-xs bg-gray-900 border border-white/10 text-white rounded-xl p-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-400 transition-all">
                                    <option v-for="provider in availableProviders" :key="provider" :value="provider">
                                        {{ providerLabel(provider) }}
                                    </option>
                                </select>

                                <span
                                    class="px-3 py-2 rounded-full text-2xs font-black tracking-widest uppercase border w-fit"
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
                            <label class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2">
                                Kunci API Personal
                            </label>
                            <input
                                type="password"
                                v-model="form.api_key"
                                placeholder="Kosongkan jika tidak ingin mengganti token tersimpan"
                                class="w-full bg-gray-900 border border-white/10 text-white rounded-xl p-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-400 transition-all" />
                            <p class="text-2xs text-gray-500 mt-2 leading-relaxed">
                                Token tersimpan tidak pernah dikirim balik ke browser.
                            </p>
                        </div>

                        <div>
                            <label class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2">
                                Varian Model
                            </label>
                            <select
                                v-model="form.selected_model"
                                class="w-full sm:max-w-xs bg-gray-900 border border-white/10 text-white rounded-xl p-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-400 transition-all">
                                <option v-for="model in currentModels" :key="model" :value="model">
                                    {{ model }}
                                </option>
                            </select>
                        </div>

                        <label class="flex items-start gap-3 bg-gray-900/80 p-4 rounded-xl border border-white/10 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_active_provider"
                                class="mt-1 h-4 w-4 rounded border-white/10 text-indigo-500 bg-gray-800 focus:ring-indigo-500" />
                            <span>
                                <span class="block text-sm font-bold text-white">Jadikan otak pemrosesan utama</span>
                                <span class="block text-2xs text-gray-400 mt-1 leading-relaxed">
                                    Provider ini akan menjadi kandidat utama untuk integrasi AI berikutnya.
                                </span>
                            </span>
                        </label>

                        <div v-if="testResult"
                            class="p-4 rounded-xl text-sm font-medium"
                            :class="testResult.success ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-300 border border-rose-500/20'">
                            {{ testResult.message }}
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center sm:justify-end gap-3 pt-5 border-t border-white/10">
                            <button
                                type="button"
                                @click="runConnectionTest"
                                :disabled="isTesting"
                                class="px-4 py-3 border border-white/10 rounded-xl text-2xs font-bold uppercase tracking-widest text-gray-300 bg-gray-900 hover:border-indigo-500/40 hover:text-white transition-all disabled:opacity-50">
                                {{ isTesting ? 'Menguji...' : 'Test Connection' }}
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-3 rounded-xl text-2xs font-bold uppercase tracking-widest text-white bg-indigo-600 hover:bg-indigo-500 transition-all shadow-lg shadow-indigo-500/20 disabled:opacity-50">
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-1 bg-gray-900/50 border border-white/10 p-6 rounded-2xl h-fit">
                        <h3 class="text-sm font-bold text-white mb-4">Penggunaan Token (Total)</h3>
                        
                        <div v-if="Object.keys(usageStats).length === 0" class="text-sm text-gray-500 text-center py-4">
                            Belum ada riwayat penggunaan AI.
                        </div>
                        
                        <div v-else class="space-y-4">
                            <div v-for="(stat, provider) in usageStats" :key="provider" class="bg-gray-800/50 p-4 rounded-xl border border-white/5">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-xs font-bold uppercase tracking-wider text-gray-300">{{ providerLabel(provider) }}</span>
                                    <span class="text-xs font-mono text-indigo-400">{{ formatNumber(stat.total_used) }} tk</span>
                                </div>
                                <div class="flex justify-between text-2xs text-gray-500 mt-1">
                                    <span>Prompt: {{ formatNumber(stat.total_prompt) }}</span>
                                    <span>Completion: {{ formatNumber(stat.total_completion) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2 bg-gray-900/50 border border-white/10 p-6 rounded-2xl">
                        <h3 class="text-sm font-bold text-white mb-4">Riwayat Pemrosesan AI (10 Terakhir)</h3>
                        
                        <div v-if="recentLogs.length === 0" class="text-sm text-gray-500 text-center py-8">
                            Belum ada transaksi yang diproses oleh AI.
                        </div>

                        <div v-else class="space-y-3">
                            <div v-for="log in recentLogs" :key="log.id" class="bg-gray-800/30 p-4 rounded-xl border border-white/5 relative overflow-hidden">
                                <div class="absolute left-0 top-0 bottom-0 w-1" 
                                    :class="{
                                        'bg-emerald-500': log.status === 'Executed',
                                        'bg-amber-500': log.status === 'Draft',
                                        'bg-rose-500': log.status === 'Failed'
                                    }">
                                </div>
                                
                                <div class="pl-2">
                                    <div class="flex justify-between items-start mb-2">
                                        <p class="text-sm text-white font-medium break-words leading-snug">"{{ log.input_text }}"</p>
                                        <span class="shrink-0 text-2xs text-gray-500">{{ log.date }}</span>
                                    </div>
                                    
                                    <div class="flex flex-wrap items-center gap-3 text-2xs">
                                        <span class="px-2 py-1 bg-gray-900 rounded border border-white/10 text-gray-300 font-mono">{{ log.provider }}</span>
                                        
                                        <span v-if="log.status !== 'Failed'" class="flex items-center gap-1 text-gray-400">
                                            <span>Confidence:</span>
                                            <span :class="log.confidence >= 80 ? 'text-emerald-400' : 'text-amber-400'">{{ log.confidence }}%</span>
                                        </span>
                                        
                                        <span class="px-2 py-1 rounded font-bold uppercase tracking-widest"
                                            :class="{
                                                'bg-emerald-500/10 text-emerald-400': log.status === 'Executed',
                                                'bg-amber-500/10 text-amber-400': log.status === 'Draft',
                                                'bg-rose-500/10 text-rose-400': log.status === 'Failed'
                                            }">
                                            {{ log.status }}
                                        </span>
                                    </div>

                                    <div v-if="log.error" class="mt-3 p-3 bg-rose-500/10 border border-rose-500/10 rounded text-xs text-rose-300">
                                        {{ log.error }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes slide-up {
    from {
        transform: translateY(15px);
        opacity: 0;
    }

    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.animate-slide-up {
    animation: slide-up 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}
</style>