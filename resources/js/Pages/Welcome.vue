<script setup>
import { Head, Link } from '@inertiajs/vue3';

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

    <div class="bg-gray-800 relative flex justify-center min-h-screen text-gray-900 font-sans antialiased">
        <div class="w-full max-w-md relative z-10 flex flex-col min-h-screen bg-gray-800 backdrop-blur-sm">

            <nav class="w-full p-5 flex justify-between items-center relative z-50">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-purple-500 flex items-center justify-center text-white font-bold">
                        B</div>
                    <span class="font-bold text-lg tracking-tight text-white">Bendaharaku</span>
                </div>
                <div class="flex items-center gap-3" v-if="canLogin">
                    <Link v-if="$page.props.auth?.user" href="/dashboard"
                        class="text-2xs font-bold bg-transparent-to-br from-gray-900 to-gray-800 border border-white/10 text-purple-400 px-4 py-2 rounded-xl hover:bg-gray-700 transition-all uppercase tracking-widest">
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link href="/login"
                            class="text-2xs font-bold text-gray-400 hover:text-white transition-colors uppercase tracking-widest">
                            Login
                        </Link>
                    </template>
                </div>
            </nav>

            <main class="flex-1 flex flex-col items-center px-5 pt-8 pb-10">

                <div class="text-center w-full animate-fade-in-up">
                    <h1
                        class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-gray-400 to-gray-200 tracking-tighter mb-4 leading-[1.1]">
                        Kendali Finansial <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-300">Di
                            Ujung
                            Jari</span>
                    </h1>

                    <p class="text-gray-400 text-2xs mb-8 leading-relaxed font-medium px-2">
                        Catat pemasukan harian, pantau saldo dompet, hingga lacak hutang teman. Semua dalam balutan
                        dark-mode
                        premium yang pas di kantong.
                    </p>

                    <div class="flex flex-col gap-3 w-full" v-if="canLogin">
                        <Link v-if="$page.props.auth?.user" href="/dashboard"
                            class="w-full bg-gradient-to-r from-gray-900 to-gray-800 text-gray-200 border border-white/10 font-bold text-2xs uppercase tracking-widest py-4 rounded-xl active:scale-95 transition-transform text-center">
                            Buka Aplikasi
                        </Link>
                        <template v-else>
                            <Link v-if="canRegister" href="/register"
                                class="w-full bg-gradient-to-r from-gray-800 to-gray-900 text-gray-200 border border-white/10 font-bold text-2xs uppercase tracking-widest py-4 rounded-xl active:scale-95 transition-transform text-center">
                                Buat Akun Baru
                            </Link>
                            <Link href="/login"
                                class="w-full bg-gradient-to-r from-gray-900 to-gray-800 border border-white/10 text-gray-200 font-bold text-2xs uppercase tracking-widest py-4 rounded-xl active:scale-95 transition-all text-center">
                                Sudah Punya Akun
                            </Link>
                        </template>
                    </div>
                </div>

                <div class="flex flex-col gap-4 mt-12 w-full">
                    <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl p-5 flex items-center gap-4 animate-fade-in-up delay-100 animate-float"
                        style="animation-delay: 0s;">
                        <div
                            class="w-12 h-12 shrink-0 bg-gray-900 rounded-xl flex items-center justify-center text-xl border border-white/10">
                            📊</div>
                        <div>
                            <h3 class="text-sm font-bold text-white mb-1 tracking-tight">Grafik Pintar</h3>
                            <p class="text-2xs text-gray-500 leading-relaxed">Pantau arus kas dengan diagram interaktif
                                bulanan.</p>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl p-5 flex items-center gap-4 animate-fade-in-up delay-200 animate-float"
                        style="animation-delay: 1s;">
                        <div
                            class="w-12 h-12 shrink-0 bg-gray-900 rounded-xl flex items-center justify-center text-xl border border-white/10">
                            💳</div>
                        <div>
                            <h3 class="text-sm font-bold text-white mb-1 tracking-tight">Multi Dompet</h3>
                            <p class="text-2xs text-gray-500 leading-relaxed">Pisahkan uang di BCA, Gopay, atau Uang
                                Tunai.</p>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl p-5 flex items-center gap-4 animate-fade-in-up delay-300 animate-float"
                        style="animation-delay: 2s;">
                        <div
                            class="w-12 h-12 shrink-0 bg-gray-900 rounded-xl flex items-center justify-center text-xl border border-white/10">
                            🤝</div>
                        <div>
                            <h3 class="text-sm font-bold text-white mb-1 tracking-tight">Lacak Hutang</h3>
                            <p class="text-2xs text-gray-500 leading-relaxed">Catat siapa saja yang ngutang beserta umur
                                hutangnya.
                            </p>
                        </div>
                    </div>
                </div>

                <footer class="mt-auto text-center animate-fade-in-up delay-300">
                    <p class="text-2xs text-gray-600">
                        &copy; {{ new Date().getFullYear() }} Bendaharaku V4.<br>Dibuat untuk Manajemen Keuangan.
                    </p>
                </footer>
            </main>
        </div>
    </div>
</template>
