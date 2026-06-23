<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useLayoutPreference } from '@/Composables/useLayoutPreference';

const { isDesktopLayout } = useLayoutPreference();

const props = defineProps({
    allowNegativeBalance: Boolean,
});

const transactionLogicForm = useForm({
    allow_negative_balance: props.allowNegativeBalance,
});

const updateTransactionLogic = () => {
    transactionLogicForm.patch(route('settings.transaction-logic.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head title="Pengaturan Aplikasi" />

        <div
            class="fixed top-[-10%] left-[50%] -translate-x-1/2 w-[400px] h-[400px] bg-purple-500 blur-[150px] opacity-[0.15] rounded-full pointer-events-none z-0">
        </div>

        <div class="p-5 pb-32 w-full lg:max-w-4xl mx-auto lg:px-8 relative z-10 animate-slide-up min-h-screen">

            <header class="flex justify-between items-center mb-10 pt-4">
                <div>
                    <p
                        class="text-2xs text-purple-500 font-black mb-1.5 uppercase tracking-[0.2em] flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                        Preferences
                    </p>
                    <h1 class="text-3xl font-black text-white tracking-tight leading-none">Pengaturan</h1>
                </div>
            </header>

            <div class="space-y-6">
                
                <div
                    class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-6 rounded-2xl relative overflow-hidden group">
                    <div class="flex items-start gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-green-500/10 text-green-400 flex items-center justify-center border border-green-500/20 shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-lg font-bold text-white mb-1">Format Mata Uang & Bahasa</h2>
                            <p class="text-2xs text-gray-400 mb-4 leading-relaxed">Pilih format regional dan mata uang
                                utama untuk seluruh laporan keuangan Anda.</p>

                            <select
                                class="w-full bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-4 text-sm transition-all focus:ring-2 focus:ring-green-500/50 focus:outline-none">
                                <option value="id-ID">Rupiah (Rp) - Bahasa Indonesia</option>
                                <option value="en-US">US Dollar ($) - English</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-6 rounded-2xl relative overflow-hidden group">
                    <div class="flex items-start gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center border border-purple-500/20 shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-lg font-bold text-white mb-1">Tema Tampilan</h2>
                            <p class="text-2xs text-gray-400 mb-4 leading-relaxed">Sesuaikan nuansa warna aplikasi
                                dengan preferensi Anda. Saat ini hanya tema gelap yang didukung untuk pengalaman
                                premium.</p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                <button
                                    class="w-full py-3 px-4 bg-purple-500 text-white text-2xs font-bold uppercase tracking-widest rounded-xl shadow-lg shadow-purple-500/20 flex items-center justify-center text-center">Dark
                                    Mode (Aktif)</button>
                                <button
                                    class="w-full py-3 px-4 bg-gray-800 text-gray-500 text-2xs font-bold uppercase tracking-widest rounded-xl border border-white/5 cursor-not-allowed flex items-center justify-center text-center">Light
                                    Mode</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="hidden lg:block bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-6 rounded-2xl relative overflow-hidden group">
                    <div class="flex items-start gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-orange-500/10 text-orange-400 flex items-center justify-center border border-orange-500/20 shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-lg font-bold text-white mb-1">Tata Letak Layar Lebar</h2>
                            <p class="text-2xs text-gray-400 mb-4 leading-relaxed">Pilih tampilan UI untuk layar desktop
                                Anda. Ingin tampilan lebar bersidebar atau kembali seperti tampilan minimalis ala
                                mobile?</p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                <button @click="isDesktopLayout = true"
                                    :class="['w-full py-3 px-4 text-2xs font-bold uppercase tracking-widest rounded-xl transition-all border flex items-center justify-center text-center',
                                        isDesktopLayout ? 'bg-orange-500 text-white border-orange-500 shadow-lg shadow-orange-500/20' : 'bg-gray-800 text-gray-400 border-white/5 hover:border-orange-500/30']">
                                    Desktop (Sidebar)
                                </button>
                                <button @click="isDesktopLayout = false"
                                    :class="['w-full py-3 px-4 text-2xs font-bold uppercase tracking-widest rounded-xl transition-all border flex items-center justify-center text-center',
                                        !isDesktopLayout ? 'bg-orange-500 text-white border-orange-500 shadow-lg shadow-orange-500/20' : 'bg-gray-800 text-gray-400 border-white/5 hover:border-orange-500/30']">
                                    Mobile (Minimalis)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-6 rounded-2xl relative overflow-hidden">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center border border-amber-500/20 shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m4-2a8 8 0 11-16 0 8 8 0 0116 0z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-2xs font-black text-amber-400 uppercase tracking-[0.2em] mb-1">Logika transaksi</p>
                                    <h2 class="text-lg font-bold text-white mb-1">Izinkan saldo dompet minus</h2>
                                    <p class="text-2xs text-gray-400 leading-relaxed">Cocok untuk pencatatan kerja harian: pengeluaran atau modal bisa dicatat lebih dulu, lalu pemasukan direkap saat selesai bekerja.</p>
                                </div>
                                <button type="button" role="switch" 
                                    :aria-checked="transactionLogicForm.allow_negative_balance"
                                    :disabled="transactionLogicForm.processing"
                                    @click="transactionLogicForm.allow_negative_balance = !transactionLogicForm.allow_negative_balance; updateTransactionLogic()"
                                    :class="['relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-amber-500/60 focus:ring-offset-2 focus:ring-offset-gray-900 disabled:opacity-50 disabled:cursor-not-allowed', 
                                    transactionLogicForm.allow_negative_balance ? 'bg-amber-500' : 'bg-gray-700']">
                                    <span :class="['pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out', 
                                        transactionLogicForm.allow_negative_balance ? 'translate-x-5' : 'translate-x-0']"></span>
                                </button>
                            </div>
                            <p :class="transactionLogicForm.allow_negative_balance ? 'text-amber-300' : 'text-gray-500'" class="mt-4 text-2xs font-bold">
                                {{ transactionLogicForm.allow_negative_balance ? 'Aktif — saldo negatif diperbolehkan.' : 'Nonaktif — transaksi ditolak bila saldo tidak mencukupi.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-6 rounded-2xl relative overflow-hidden group">
                    <div class="flex items-start gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center border border-indigo-500/20 shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-lg font-bold text-white mb-1">Integrasi Kecerdasan Buatan (AI)</h2>
                            <p class="text-2xs text-gray-400 mb-4 leading-relaxed">Konfigurasikan API Key personal
                                Anda dan pilih model AI untuk asisten finansial cerdas.</p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                <Link :href="route('settings.ai.index')"
                                    class="w-full py-3 px-4 flex items-center justify-center gap-2 bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 text-2xs font-bold uppercase tracking-widest rounded-xl hover:bg-indigo-500 hover:text-white transition-all shadow-lg shadow-indigo-500/10">
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="truncate">Konfigurasi AI</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-6 rounded-2xl relative overflow-hidden group">
                    <div class="flex items-start gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center border border-blue-500/20 shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-lg font-bold text-white mb-1">Notifikasi Telegram</h2>
                            <p class="text-2xs text-gray-400 mb-4 leading-relaxed">Aktifkan notifikasi otomatis ke
                                Telegram setiap kali ada transaksi baru atau pengingat tagihan.</p>

                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" checked disabled>
                                <div
                                    class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500">
                                </div>
                                <span class="ml-3 text-2xs font-bold text-gray-300 uppercase tracking-widest">Aktif
                                    (Terkoneksi dengan Bot)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-6 rounded-2xl relative overflow-hidden group">
                    <div class="flex items-start gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-yellow-500/10 text-yellow-400 flex items-center justify-center border border-yellow-500/20 shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-lg font-bold text-white mb-1">Ekspor & Pencadangan Data</h2>
                            <p class="text-2xs text-gray-400 mb-4 leading-relaxed">Unduh semua rekam jejak finansial
                                Anda ke dalam format CSV untuk diolah di Excel.</p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                <button
                                    class="w-full py-3 px-4 flex items-center justify-center gap-2 bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 text-2xs font-bold uppercase tracking-widest rounded-xl hover:bg-yellow-500 hover:text-gray-900 transition-all">
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    <span class="truncate">Ekspor Data (CSV)</span>
                                </button>
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