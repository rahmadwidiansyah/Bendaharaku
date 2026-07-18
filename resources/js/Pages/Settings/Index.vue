<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useLayoutPreference } from '@/Composables/useLayoutPreference';
import { useI18n } from 'vue-i18n';
import { useLocale } from '@/Composables/useLocale.js';

const { t } = useI18n();
const { currentPreference, setLocale } = useLocale();
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
        <Head :title="$t('settings.title')" />

        <!-- Ambient glow -->
        <div class="fixed top-[-10%] left-[50%] -translate-x-1/2 w-[400px] h-[400px] bg-purple-500 blur-[150px] opacity-[0.15] rounded-full pointer-events-none z-0"></div>

        <div class="p-5 w-full lg:max-w-4xl mx-auto lg:px-8 relative z-10 animate-slide-up min-h-screen">

            <!-- Page Header -->
            <header class="pt-4 mb-4 lg:mb-10">
                <div class="hidden lg:block">
                    <p class="text-2xs text-purple-500 font-black mb-1.5 uppercase tracking-[0.2em] flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                        {{ $t('settings.subtitle') }}
                    </p>
                    <h1 class="text-3xl font-black text-white tracking-tight leading-none">{{ $t('settings.title') }}</h1>
                </div>
            </header>

            <div class="space-y-10">

                <!-- ─── SECTION: AKUN ──────────────────────────────────────── -->
                <section>
                    <div class="flex items-center gap-3 mb-4">
                        <h2 class="text-2xs font-black text-gray-400 uppercase tracking-[0.2em]">{{ $t('settings.account') }}</h2>
                        <div class="flex-1 h-px bg-white/5"></div>
                    </div>

                    <Link
                        :href="route('profile.edit')"
                        class="flex items-center gap-4 p-5 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl hover:border-purple-500/40 active:scale-[0.99] transition-all group">
                        <div class="w-11 h-11 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center border border-purple-500/20 shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-white leading-tight">{{ $t('settings.profile.title') }}</p>
                            <p class="text-2xs text-gray-500 mt-0.5">{{ $t('settings.profile.desc') }}</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-600 group-hover:text-purple-400 transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </section>

                <!-- ─── SECTION: TRANSAKSI ─────────────────────────────────── -->
                <section>
                    <div class="flex items-center gap-3 mb-4">
                        <h2 class="text-2xs font-black text-gray-400 uppercase tracking-[0.2em]">{{ $t('settings.transaction') }}</h2>
                        <div class="flex-1 h-px bg-white/5"></div>
                    </div>

                    <!-- Saldo Minus Toggle -->
                    <div class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div class="w-11 h-11 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center border border-amber-500/20 shrink-0 mt-0.5">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white leading-tight">{{ $t('settings.negativeBalance.title') }}</p>
                                    <p class="text-2xs text-gray-500 mt-1 leading-relaxed max-w-xs">
                                        {{ $t('settings.negativeBalance.desc') }}
                                    </p>
                                </div>
                            </div>
                            <!-- Toggle switch -->
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="transactionLogicForm.allow_negative_balance"
                                :disabled="transactionLogicForm.processing"
                                @click="transactionLogicForm.allow_negative_balance = !transactionLogicForm.allow_negative_balance; updateTransactionLogic()"
                                :class="[
                                    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-amber-500/60 focus:ring-offset-2 focus:ring-offset-gray-900 disabled:opacity-50 disabled:cursor-not-allowed mt-0.5',
                                    transactionLogicForm.allow_negative_balance ? 'bg-amber-500' : 'bg-gray-700'
                                ]">
                                <span :class="[
                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                    transactionLogicForm.allow_negative_balance ? 'translate-x-5' : 'translate-x-0'
                                ]"></span>
                            </button>
                        </div>
                        <!-- Status label -->
                        <p class="mt-4 text-2xs font-bold pl-15"
                            :class="transactionLogicForm.allow_negative_balance ? 'text-amber-400' : 'text-gray-600'">
                            {{ transactionLogicForm.allow_negative_balance
                                ? $t('settings.negativeBalanceOn')
                                : $t('settings.negativeBalanceOff') }}
                        </p>
                    </div>
                </section>

                <!-- ─── SECTION: TAMPILAN ──────────────────────────────────── -->
                <section>
                    <div class="flex items-center gap-3 mb-4">
                        <h2 class="text-2xs font-black text-gray-400 uppercase tracking-[0.2em]">{{ $t('settings.appearance') }}</h2>
                        <div class="flex-1 h-px bg-white/5"></div>
                    </div>

                    <div class="space-y-3">
                        <!-- Tema -->
                        <div class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl p-5">
                            <div class="flex items-start gap-4">
                                <div class="w-11 h-11 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center border border-purple-500/20 shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-white leading-tight">{{ $t('settings.theme.title') }}</p>
                                    <p class="text-2xs text-gray-500 mt-0.5 mb-3">{{ $t('settings.theme.desc') }}</p>
                                    <div class="flex gap-2">
                                        <span class="px-3 py-1.5 rounded-lg bg-purple-500/20 text-purple-400 border border-purple-500/30 text-2xs font-bold uppercase tracking-widest">
                                            {{ $t('settings.theme.dark') }} ✓
                                        </span>
                                        <span class="px-3 py-1.5 rounded-lg bg-gray-800 text-gray-600 border border-white/5 text-2xs font-bold uppercase tracking-widest cursor-not-allowed" :title="$t('settings.theme.lightSoon')">
                                            {{ $t('settings.theme.light') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tata letak — hanya tampil di desktop -->
                        <div class="hidden lg:block bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl p-5">
                            <div class="flex items-start gap-4">
                                <div class="w-11 h-11 rounded-xl bg-orange-500/10 text-orange-400 flex items-center justify-center border border-orange-500/20 shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-white leading-tight">{{ $t('settings.layout.title') }}</p>
                                    <p class="text-2xs text-gray-500 mt-0.5 mb-3">{{ $t('settings.layout.desc') }}</p>
                                    <div class="flex gap-2">
                                        <button
                                            @click="isDesktopLayout = true"
                                            :class="[
                                                'px-3 py-1.5 rounded-lg text-2xs font-bold uppercase tracking-widest transition-all border',
                                                isDesktopLayout
                                                    ? 'bg-orange-500/20 text-orange-400 border-orange-500/30'
                                                    : 'bg-gray-800 text-gray-500 border-white/5 hover:border-orange-500/30 hover:text-orange-400'
                                            ]">
                                            {{ $t('settings.layout.desktop') }} {{ isDesktopLayout ? '✓' : '' }}
                                        </button>
                                        <button
                                            @click="isDesktopLayout = false"
                                            :class="[
                                                'px-3 py-1.5 rounded-lg text-2xs font-bold uppercase tracking-widest transition-all border',
                                                !isDesktopLayout
                                                    ? 'bg-orange-500/20 text-orange-400 border-orange-500/30'
                                                    : 'bg-gray-800 text-gray-500 border-white/5 hover:border-orange-500/30 hover:text-orange-400'
                                            ]">
                                            {{ $t('settings.layout.mobile') }} {{ !isDesktopLayout ? '✓' : '' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ─── SECTION: BAHASA ─────────────────────────────────────────── -->
                <section>
                    <div class="flex items-center gap-3 mb-4">
                        <h2 class="text-2xs font-black text-gray-400 uppercase tracking-[0.2em]">{{ $t('settings.language') }}</h2>
                        <div class="flex-1 h-px bg-white/5"></div>
                    </div>

                    <div class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl p-5 space-y-3">
                        <p class="text-sm font-bold text-white mb-4">{{ $t('settings.lang.title') }}</p>

                        <!-- Auto (Device) -->
                        <label class="flex items-center gap-4 cursor-pointer group">
                            <div class="relative">
                                <input type="radio" name="language" value="auto" :checked="currentPreference === 'auto'" @change="setLocale('auto')" class="sr-only" />
                                <div :class="['w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors', currentPreference === 'auto' ? 'border-purple-500 bg-purple-500/20' : 'border-gray-600 group-hover:border-gray-400']">
                                    <div v-if="currentPreference === 'auto'" class="w-2.5 h-2.5 rounded-full bg-purple-400"></div>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white">{{ $t('settings.lang.auto') }}</p>
                                <p class="text-2xs text-gray-500">{{ $t('settings.lang.autoDesc') }}</p>
                            </div>
                            <span v-if="currentPreference === 'auto'" class="ml-auto text-2xs font-bold text-purple-400 uppercase tracking-widest">{{ $t('settings.lang.current') }}</span>
                        </label>

                        <div class="border-t border-white/5"></div>

                        <!-- Bahasa Indonesia -->
                        <label class="flex items-center gap-4 cursor-pointer group">
                            <div class="relative">
                                <input type="radio" name="language" value="id" :checked="currentPreference === 'id'" @change="setLocale('id')" class="sr-only" />
                                <div :class="['w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors', currentPreference === 'id' ? 'border-purple-500 bg-purple-500/20' : 'border-gray-600 group-hover:border-gray-400']">
                                    <div v-if="currentPreference === 'id'" class="w-2.5 h-2.5 rounded-full bg-purple-400"></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-lg">🇮🇩</span>
                                <p class="text-sm font-bold text-white">{{ $t('settings.lang.id') }}</p>
                            </div>
                            <span v-if="currentPreference === 'id'" class="ml-auto text-2xs font-bold text-purple-400 uppercase tracking-widest">{{ $t('settings.lang.current') }}</span>
                        </label>

                        <!-- English -->
                        <label class="flex items-center gap-4 cursor-pointer group">
                            <div class="relative">
                                <input type="radio" name="language" value="en" :checked="currentPreference === 'en'" @change="setLocale('en')" class="sr-only" />
                                <div :class="['w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors', currentPreference === 'en' ? 'border-purple-500 bg-purple-500/20' : 'border-gray-600 group-hover:border-gray-400']">
                                    <div v-if="currentPreference === 'en'" class="w-2.5 h-2.5 rounded-full bg-purple-400"></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-lg">🇺🇸</span>
                                <p class="text-sm font-bold text-white">{{ $t('settings.lang.en') }}</p>
                            </div>
                            <span v-if="currentPreference === 'en'" class="ml-auto text-2xs font-bold text-purple-400 uppercase tracking-widest">{{ $t('settings.lang.current') }}</span>
                        </label>
                    </div>
                </section>

                <!-- ─── SECTION: INTEGRASI ─────────────────────────────────── -->
                <section>
                    <div class="flex items-center gap-3 mb-4">
                        <h2 class="text-2xs font-black text-gray-400 uppercase tracking-[0.2em]">{{ $t('settings.ai') }}</h2>
                        <div class="flex-1 h-px bg-white/5"></div>
                    </div>

                    <div class="space-y-3">
                        <!-- AI -->
                        <Link
                            :href="route('settings.ai.index')"
                            class="flex items-center gap-4 p-5 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl hover:border-indigo-500/40 active:scale-[0.99] transition-all group">
                            <div class="w-11 h-11 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center border border-indigo-500/20 shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-white leading-tight">{{ $t('settings.aiSettings.title') }}</p>
                                <p class="text-2xs text-gray-500 mt-0.5">{{ $t('settings.aiSettings.desc') }}</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-600 group-hover:text-indigo-400 transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>

                        <!-- Telegram -->
                        <div class="flex items-center gap-4 p-5 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl">
                            <div class="w-11 h-11 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center border border-blue-500/20 shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-white leading-tight">{{ $t('settings.telegram.title') }}</p>
                                <p class="text-2xs text-gray-500 mt-0.5">{{ $t('settings.telegram.desc') }}</p>
                            </div>
                            <span class="shrink-0 px-2.5 py-1 rounded-full bg-blue-500/15 text-blue-400 border border-blue-500/25 text-2xs font-bold uppercase tracking-widest">
                                {{ $t('settings.telegram.status') }}
                            </span>
                        </div>
                    </div>
                </section>

                <!-- ─── SECTION: DATA ───────────────────────────────────────── -->
                <section>
                    <div class="flex items-center gap-3 mb-4">
                        <h2 class="text-2xs font-black text-gray-400 uppercase tracking-[0.2em]">{{ $t('settings.data.section') }}</h2>
                        <div class="flex-1 h-px bg-white/5"></div>
                    </div>

                    <div class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl p-5">
                        <div class="flex items-start gap-4">
                            <div class="w-11 h-11 rounded-xl bg-yellow-500/10 text-yellow-400 flex items-center justify-center border border-yellow-500/20 shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-white leading-tight">{{ $t('settings.data.title') }}</p>
                                <p class="text-2xs text-gray-500 mt-0.5 mb-4">{{ $t('settings.data.desc') }}</p>
                                <button
                                    type="button"
                                    class="flex items-center gap-2 px-4 py-2.5 bg-yellow-500/15 text-yellow-400 border border-yellow-500/25 text-2xs font-bold uppercase tracking-widest rounded-xl hover:bg-yellow-500 hover:text-gray-900 hover:border-yellow-500 transition-all">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    {{ $t('settings.data.exportBtn') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
