<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PageContainer from '@/Components/PageContainer.vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>

    <Head title="Welcome" />

    <div class="bg-[var(--color-surface-base)] min-h-screen text-[var(--color-text-primary)] font-sans antialiased">
        <PageContainer class="flex flex-col min-h-screen">

            <nav class="w-full p-5 flex justify-between items-center relative z-50">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-[var(--color-brand)] flex items-center justify-center text-[var(--color-text-primary)] font-bold">
                        B</div>
                    <span class="font-bold text-lg tracking-tight text-[var(--color-text-primary)]">Bendaharaku</span>
                </div>
                <div class="flex items-center gap-3" v-if="canLogin">
                    <Link v-if="$page.props.auth?.user" href="/dashboard"
                        class="text-2xs font-bold bg-gradient-to-br from-[var(--color-surface-raised)] to-[var(--color-surface-muted)] border border-[var(--color-border-default)] text-[var(--color-brand)] px-4 py-2 rounded-xl hover:bg-[var(--color-surface-muted)] transition-all uppercase tracking-widest">
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link href="/login"
                            class="text-2xs font-bold text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors uppercase tracking-widest">
                            Login
                        </Link>
                    </template>
                </div>
            </nav>

            <main class="flex-1 flex flex-col items-center px-5 pt-8 pb-10 mx-auto w-full max-w-2xl">

                <div class="text-center w-full animate-fade-in-up">
                    <h1
                        class="text-4xl sm:text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-text-secondary)] to-[var(--color-text-primary)] tracking-tighter mb-4 leading-[1.1]">
                        Kendali Finansial <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-brand)] to-[var(--color-brand-hover)]">Di
                            Ujung
                            Jari</span>
                    </h1>

                    <p class="text-[var(--color-text-secondary)] text-sm mb-8 leading-relaxed font-medium px-2">
                        Catat pemasukan harian, pantau saldo dompet, hingga lacak hutang teman. Semua dalam balutan
                        dark-mode
                        premium yang pas di kantong.
                    </p>

                    <div class="flex flex-col gap-3 w-full" v-if="canLogin">
                        <Link v-if="$page.props.auth?.user" href="/dashboard"
                            class="w-full bg-gradient-to-r from-[var(--color-surface-raised)] to-[var(--color-surface-muted)] text-gray-200 border border-[var(--color-border-default)] font-bold text-2xs uppercase tracking-widest py-4 rounded-xl active:scale-95 transition-transform text-center">
                            Buka Aplikasi
                        </Link>
                        <template v-else>
                            <Link v-if="canRegister" href="/register"
                                class="w-full bg-gradient-to-r from-[var(--color-surface-muted)] to-[var(--color-surface-raised)] text-gray-200 border border-[var(--color-border-default)] font-bold text-2xs uppercase tracking-widest py-4 rounded-xl active:scale-95 transition-transform text-center">
                                Buat Akun Baru
                            </Link>
                            <Link href="/login"
                                class="w-full bg-gradient-to-r from-[var(--color-surface-raised)] to-[var(--color-surface-muted)] border border-[var(--color-border-default)] text-gray-200 font-bold text-2xs uppercase tracking-widest py-4 rounded-xl active:scale-95 transition-all text-center">
                                Sudah Punya Akun
                            </Link>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-12 w-full">
                    <div class="bg-gradient-to-br from-[var(--color-surface-raised)] to-[var(--color-surface-muted)] rounded-xl p-5 flex flex-col sm:flex-row items-center gap-4 animate-fade-in-up delay-100 animate-float"
                        style="animation-delay: 0s;">
                        <div
                            class="w-12 h-12 shrink-0 bg-[var(--color-surface-raised)] rounded-xl flex items-center justify-center text-xl border border-[var(--color-border-default)]">
                            📊</div>
                        <div class="text-center sm:text-left">
                            <h3 class="text-sm font-bold text-[var(--color-text-primary)] mb-1 tracking-tight">Grafik Pintar</h3>
                            <p class="text-2xs text-[var(--color-text-muted)] leading-relaxed">Pantau arus kas dengan diagram interaktif
                                bulanan.</p>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-[var(--color-surface-raised)] to-[var(--color-surface-muted)] rounded-xl p-5 flex flex-col sm:flex-row items-center gap-4 animate-fade-in-up delay-200 animate-float"
                        style="animation-delay: 1s;">
                        <div
                            class="w-12 h-12 shrink-0 bg-[var(--color-surface-raised)] rounded-xl flex items-center justify-center text-xl border border-[var(--color-border-default)]">
                            💳</div>
                        <div class="text-center sm:text-left">
                            <h3 class="text-sm font-bold text-[var(--color-text-primary)] mb-1 tracking-tight">Multi Dompet</h3>
                            <p class="text-2xs text-[var(--color-text-muted)] leading-relaxed">Pisahkan uang di BCA, Gopay, atau Uang
                                Tunai.</p>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-[var(--color-surface-raised)] to-[var(--color-surface-muted)] rounded-xl p-5 flex flex-col sm:flex-row items-center gap-4 animate-fade-in-up delay-300 animate-float"
                        style="animation-delay: 2s;">
                        <div
                            class="w-12 h-12 shrink-0 bg-[var(--color-surface-raised)] rounded-xl flex items-center justify-center text-xl border border-[var(--color-border-default)]">
                            🤝</div>
                        <div class="text-center sm:text-left">
                            <h3 class="text-sm font-bold text-[var(--color-text-primary)] mb-1 tracking-tight">Lacak Hutang</h3>
                            <p class="text-2xs text-[var(--color-text-muted)] leading-relaxed">Catat siapa saja yang ngutang beserta umur
                                hutangnya.
                            </p>
                        </div>
                    </div>
                </div>

                <footer class="mt-auto text-center animate-fade-in-up delay-300 pt-12">
                    <p class="text-2xs text-gray-600">
                        &copy; {{ new Date().getFullYear() }} Bendaharaku V4.<br>Dibuat untuk Manajemen Keuangan.
                    </p>
                </footer>
            </main>
        </PageContainer>
    </div>
</template>