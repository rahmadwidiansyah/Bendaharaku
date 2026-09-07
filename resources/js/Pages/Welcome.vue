<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import Button from '@/Components/Button.vue';

defineProps({ canLogin: Boolean, canRegister: Boolean });
</script>

<template>
    <Head title="Welcome" />
    <div class="bg-[var(--color-surface-base)] min-h-screen text-[var(--color-text-primary)] font-sans antialiased">
        <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 flex flex-col min-h-screen">
            <nav class="w-full py-4 sm:py-5 flex justify-between items-center">
                <div class="flex items-center gap-2.5">
                    <img src="/icons/logo.png" alt="Bendaharaku" class="w-8 h-8 rounded-lg object-contain bg-white p-1 border border-[var(--color-border-default)]" />
                    <span class="font-bold text-base tracking-tight text-[var(--color-text-primary)]">Bendaharaku</span>
                </div>
                <div class="flex items-center gap-2" v-if="canLogin">
                    <Link v-if="$page.props.auth?.user" href="/dashboard">
                        <Button variant="secondary" size="sm">Dashboard</Button>
                    </Link>
                    <template v-else>
                        <Link href="/login"><Button variant="ghost" size="sm">Masuk</Button></Link>
                        <Link v-if="canRegister" href="/register" class="hidden sm:inline-flex"><Button variant="primary" size="sm">Daftar</Button></Link>
                    </template>
                </div>
            </nav>

            <main class="flex-1 flex flex-col items-center justify-center py-10 sm:py-16">
                <div class="text-center w-full max-w-2xl animate-fade-in-up">
                    <h1 class="fluid-text-3xl font-bold tracking-tight text-[var(--color-text-primary)] leading-[1.05] mb-4">
                        Kendali Finansial <span class="text-[var(--color-brand)]">di Ujung Jari</span>
                    </h1>
                    <p class="text-sm sm:text-base text-[var(--color-text-secondary)] leading-relaxed max-w-[52ch] mx-auto mb-8">
                        Catat pemasukan harian, pantau saldo dompet, dan lacak hutang-piutang — cepat, aman, terstruktur.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center w-full max-w-md mx-auto" v-if="canLogin">
                        <template v-if="$page.props.auth?.user">
                            <Link href="/dashboard" class="w-full sm:w-auto flex-1"><Button variant="primary" size="lg" fullWidth>Buka Aplikasi</Button></Link>
                        </template>
                        <template v-else>
                            <Link v-if="canRegister" href="/register" class="w-full sm:w-auto flex-1"><Button variant="primary" size="lg" fullWidth>Buat Akun Baru</Button></Link>
                            <Link href="/login" class="w-full sm:w-auto flex-1"><Button variant="secondary" size="lg" fullWidth>Sudah Punya Akun</Button></Link>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-12 sm:mt-16 w-full max-w-4xl">
                    <div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl p-5 flex gap-4 items-start animate-fade-in-up" style="animation-delay: 80ms">
                        <div class="w-10 h-10 rounded-xl bg-[var(--color-brand-subtle)] border border-[var(--color-brand-border)] flex items-center justify-center shrink-0">
                            <AppIcon icon="bar-chart-3" iconClass="w-5 h-5 text-[var(--color-brand)]" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-[var(--color-text-primary)] tracking-tight">Grafik Pintar</h3>
                            <p class="text-xs text-[var(--color-text-muted)] leading-relaxed mt-1">Pantau arus kas dengan diagram bulanan interaktif.</p>
                        </div>
                    </div>
                    <div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl p-5 flex gap-4 items-start animate-fade-in-up" style="animation-delay: 160ms">
                        <div class="w-10 h-10 rounded-xl bg-[var(--color-brand-subtle)] border border-[var(--color-brand-border)] flex items-center justify-center shrink-0">
                            <AppIcon icon="wallet" iconClass="w-5 h-5 text-[var(--color-brand)]" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-[var(--color-text-primary)] tracking-tight">Multi Dompet</h3>
                            <p class="text-xs text-[var(--color-text-muted)] leading-relaxed mt-1">Pisahkan uang di BCA, GoPay, atau tunai.</p>
                        </div>
                    </div>
                    <div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl p-5 flex gap-4 items-start animate-fade-in-up" style="animation-delay: 240ms">
                        <div class="w-10 h-10 rounded-xl bg-[var(--color-brand-subtle)] border border-[var(--color-brand-border)] flex items-center justify-center shrink-0">
                            <AppIcon icon="handshake" iconClass="w-5 h-5 text-[var(--color-brand)]" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-[var(--color-text-primary)] tracking-tight">Lacak Hutang</h3>
                            <p class="text-xs text-[var(--color-text-muted)] leading-relaxed mt-1">Catat siapa berutang beserta jatuh temponya.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 w-full max-w-4xl">
                    <div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl p-5 flex gap-4 items-start animate-fade-in-up" style="animation-delay: 320ms">
                        <div class="w-10 h-10 rounded-xl bg-[var(--color-brand-subtle)] border border-[var(--color-brand-border)] flex items-center justify-center shrink-0">
                            <AppIcon icon="message-circle" iconClass="w-5 h-5 text-[var(--color-brand)]" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-[var(--color-text-primary)] tracking-tight">Chat Pintar</h3>
                            <p class="text-xs text-[var(--color-text-muted)] leading-relaxed mt-1">Ketik “bayar kopi 20rb” atau kirim struk — langsung jadi transaksi.</p>
                        </div>
                    </div>
                    <div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl p-5 flex gap-4 items-start animate-fade-in-up" style="animation-delay: 400ms">
                        <div class="w-10 h-10 rounded-xl bg-[var(--color-brand-subtle)] border border-[var(--color-brand-border)] flex items-center justify-center shrink-0">
                            <AppIcon icon="bot" iconClass="w-5 h-5 text-[var(--color-brand)]" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-[var(--color-text-primary)] tracking-tight">AI Asisten</h3>
                            <p class="text-xs text-[var(--color-text-muted)] leading-relaxed mt-1">Ken-Chan belajar dari kebiasaanmu, saran kategori & dompet otomatis.</p>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="py-6 text-center border-t border-[var(--color-border-subtle)] mt-auto">
                <p class="text-2xs text-[var(--color-text-muted)]">&copy; {{ new Date().getFullYear() }} Bendaharaku — Manajemen keuangan pribadi.</p>
            </footer>
        </div>
    </div>
</template>
