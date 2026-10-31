<script setup>
/**
 * Material/Welcome — halaman pembuka dengan desain M3 Expressive.
 * Dirender saat ui_theme = 'm3' (default aplikasi); pasangan Default UI
 * ada di Pages/Welcome.vue. Salinan teks sengaja statis (bahasa
 * Indonesia) mengikuti halaman Welcome Default.
 */
import { Head, Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';

defineProps({ canLogin: Boolean, canRegister: Boolean });
</script>

<template>
    <Head title="Welcome" />
    <div
        class="min-h-screen font-sans antialiased
               bg-[rgb(var(--m3-color-surface-rgb))] text-[rgb(var(--m3-color-on-surface-rgb))]"
    >
        <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 sm:px-6">
            <!-- Top app bar -->
            <nav class="flex w-full items-center justify-between py-4 sm:py-5">
                <div class="flex items-center gap-2.5">
                    <img
                        src="/icons/logo.png"
                        alt="Bendaharaku"
                        class="h-9 w-9 rounded-xl object-contain
                               bg-[rgb(var(--m3-color-surface-container-high-rgb))] p-1"
                    />
                    <span class="text-base font-bold tracking-tight">Bendaharaku</span>
                </div>
                <div v-if="canLogin" class="flex items-center gap-2">
                    <Link v-if="$page.props.auth?.user" href="/dashboard">
                        <button
                            type="button"
                            class="rounded-full border border-[rgb(var(--m3-color-outline-variant-rgb))]
                                   px-4 py-2 text-sm font-semibold transition-opacity hover:opacity-80
                                   text-[rgb(var(--m3-color-on-surface-rgb))]"
                        >
                            Dashboard
                        </button>
                    </Link>
                    <template v-else>
                        <Link href="/login">
                            <button
                                type="button"
                                class="rounded-full px-4 py-2 text-sm font-semibold transition-opacity hover:opacity-80
                                       text-[rgb(var(--m3-color-primary-rgb))]"
                            >
                                Masuk
                            </button>
                        </Link>
                        <Link v-if="canRegister" href="/register" class="hidden sm:inline-flex">
                            <button
                                type="button"
                                class="rounded-full px-4 py-2 text-sm font-semibold transition-opacity hover:opacity-90
                                       bg-[rgb(var(--m3-color-primary-rgb))]
                                       text-[rgb(var(--m3-color-on-primary-rgb))]"
                            >
                                Daftar
                            </button>
                        </Link>
                    </template>
                </div>
            </nav>

            <main class="flex flex-1 flex-col items-center justify-center py-10 sm:py-16">
                <!-- Hero -->
                <div class="w-full max-w-2xl animate-fade-in-up text-center">
                    <span
                        class="mb-5 inline-block rounded-full border
                               border-[rgb(var(--m3-color-outline-variant-rgb))]
                               bg-[rgb(var(--m3-color-surface-container-rgb))]
                               px-3.5 py-1.5 text-2xs font-bold uppercase tracking-widest
                               text-[rgb(var(--m3-color-on-surface-variant-rgb))]"
                    >
                        Manajemen Keuangan Pribadi
                    </span>
                    <h1 class="mb-4 text-4xl font-bold leading-[1.08] tracking-tight sm:text-5xl">
                        Kendali Finansial
                        <span class="text-[rgb(var(--m3-color-primary-rgb))]">di Ujung Jari</span>
                    </h1>
                    <p
                        class="mx-auto mb-8 max-w-[52ch] text-sm leading-relaxed
                               text-[rgb(var(--m3-color-on-surface-variant-rgb))] sm:text-base"
                    >
                        Catat pemasukan harian, pantau saldo dompet, dan lacak hutang-piutang — cepat, aman, terstruktur.
                    </p>
                    <div v-if="canLogin" class="mx-auto flex w-full max-w-md flex-col justify-center gap-3 sm:flex-row">
                        <template v-if="$page.props.auth?.user">
                            <Link href="/dashboard" class="flex-1 sm:w-auto">
                                <button
                                    type="button"
                                    class="w-full rounded-full px-7 py-3.5 text-sm font-bold shadow-lg transition-all
                                           hover:opacity-90 active:scale-[0.98]
                                           bg-[rgb(var(--m3-color-primary-rgb))]
                                           text-[rgb(var(--m3-color-on-primary-rgb))]"
                                >
                                    Buka Aplikasi
                                </button>
                            </Link>
                        </template>
                        <template v-else>
                            <Link v-if="canRegister" href="/register" class="flex-1 sm:w-auto">
                                <button
                                    type="button"
                                    class="w-full rounded-full px-7 py-3.5 text-sm font-bold shadow-lg transition-all
                                           hover:opacity-90 active:scale-[0.98]
                                           bg-[rgb(var(--m3-color-primary-rgb))]
                                           text-[rgb(var(--m3-color-on-primary-rgb))]"
                                >
                                    Buat Akun Baru
                                </button>
                            </Link>
                            <Link href="/login" class="flex-1 sm:w-auto">
                                <button
                                    type="button"
                                    class="w-full rounded-full px-7 py-3.5 text-sm font-bold transition-all
                                           hover:opacity-90 active:scale-[0.98]
                                           bg-[rgb(var(--m3-color-secondary-container-rgb))]
                                           text-[rgb(var(--m3-color-on-secondary-container-rgb))]"
                                >
                                    Sudah Punya Akun
                                </button>
                            </Link>
                        </template>
                    </div>
                </div>

                <!-- Fitur: 3 kartu atas + 2 kartu bawah -->
                <div class="mt-12 grid w-full max-w-4xl grid-cols-1 gap-4 sm:mt-16 sm:grid-cols-3">
                    <div
                        v-for="(fitur, i) in [
                            { icon: 'bar-chart-3', chip: 'primary', judul: 'Grafik Pintar', teks: 'Pantau arus kas dengan diagram bulanan interaktif.' },
                            { icon: 'wallet', chip: 'secondary', judul: 'Multi Dompet', teks: 'Pisahkan uang di BCA, GoPay, atau tunai.' },
                            { icon: 'handshake', chip: 'tertiary', judul: 'Lacak Hutang', teks: 'Catat siapa berutang beserta jatuh temponya.' },
                        ]"
                        :key="fitur.judul"
                        class="flex items-start gap-4 rounded-3xl border p-5 animate-fade-in-up
                               border-[rgb(var(--m3-color-outline-variant-rgb))]
                               bg-[rgb(var(--m3-color-surface-container-low-rgb))]"
                        :style="{ animationDelay: `${80 + i * 80}ms` }"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                            :class="{
                                'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]': fitur.chip === 'primary',
                                'bg-[rgb(var(--m3-color-secondary-container-rgb))] text-[rgb(var(--m3-color-on-secondary-container-rgb))]': fitur.chip === 'secondary',
                                'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]': fitur.chip === 'tertiary',
                            }"
                        >
                            <AppIcon :icon="fitur.icon" icon-class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold tracking-tight">{{ fitur.judul }}</h3>
                            <p class="mt-1 text-xs leading-relaxed text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ fitur.teks }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid w-full max-w-4xl grid-cols-1 gap-4 sm:grid-cols-2">
                    <div
                        v-for="(fitur, i) in [
                            { icon: 'message-circle', chip: 'tertiary', judul: 'Chat Pintar', teks: 'Ketik “bayar kopi 20rb” atau kirim struk — langsung jadi transaksi.' },
                            { icon: 'bot', chip: 'primary', judul: 'AI Asisten', teks: 'Ken-Chan belajar dari kebiasaanmu, saran kategori & dompet otomatis.' },
                        ]"
                        :key="fitur.judul"
                        class="flex items-start gap-4 rounded-3xl border p-5 animate-fade-in-up
                               border-[rgb(var(--m3-color-outline-variant-rgb))]
                               bg-[rgb(var(--m3-color-surface-container-low-rgb))]"
                        :style="{ animationDelay: `${320 + i * 80}ms` }"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                            :class="{
                                'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]': fitur.chip === 'primary',
                                'bg-[rgb(var(--m3-color-tertiary-container-rgb))] text-[rgb(var(--m3-color-on-tertiary-container-rgb))]': fitur.chip === 'tertiary',
                            }"
                        >
                            <AppIcon :icon="fitur.icon" icon-class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold tracking-tight">{{ fitur.judul }}</h3>
                            <p class="mt-1 text-xs leading-relaxed text-[rgb(var(--m3-color-on-surface-variant-rgb))]">{{ fitur.teks }}</p>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="mt-auto border-t py-6 text-center border-[rgb(var(--m3-color-outline-variant-rgb))]">
                <p class="text-2xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                    &copy; {{ new Date().getFullYear() }} Bendaharaku — Manajemen keuangan pribadi.
                </p>
            </footer>
        </div>
    </div>
</template>
