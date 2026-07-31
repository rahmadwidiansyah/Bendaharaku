<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>

    <Head title="Log in" />

    <div
        class="antialiased selection:bg-gray-600 relative flex justify-center min-h-screen bg-gray-800 text-white font-sans overflow-x-hidden">

        <div class="w-full min-w-0 flex-1 relative z-10 flex flex-col justify-center min-h-screen bg-gray-800 px-4 sm:px-6 lg:px-8 py-10">

            <div class="animate-slide-up w-full max-w-md mx-auto">
                <div class="text-center mb-10">
                    <ApplicationLogo class="w-20 h-20 rounded-xl mx-auto mb-5" />
                    <h1 class="text-3xl font-bold text-white tracking-tight">Bendaharaku</h1>
                    <p class="text-2xs text-gray-500 uppercase tracking-widest mt-2 font-bold">Sign in to continue</p>
                </div>

                <div v-if="status" role="alert" class="mb-5 p-3 rounded-xl bg-green-500/10 text-green-300 border border-green-500/20 text-xs font-medium">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="login-email"
                            class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
                            Email
                        </label>
                        <input
                            id="login-email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="email"
                            :aria-invalid="!!form.errors.email"
                            aria-describedby="login-email-error"
                            class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border text-white rounded-xl p-4 text-sm focus:outline-none focus:ring-1 transition-all"
                            :class="form.errors.email ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-white/10 focus:border-purple-500 focus:ring-[#FCA5FF]'" />
                        <div v-if="form.errors.email" id="login-email-error" role="alert"
                            class="text-2xs text-red-400 mt-1 block font-bold">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <div>
                        <label for="login-password"
                            class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
                            Password
                        </label>
                        <input
                            id="login-password"
                            type="password"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            :aria-invalid="!!form.errors.password"
                            aria-describedby="login-password-error"
                            class="w-full bg-gradient-to-br from-gray-800 to-gray-900 border text-white rounded-xl p-4 text-sm focus:outline-none focus:ring-1 transition-all"
                            :class="form.errors.password ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-white/10 focus:border-purple-500 focus:ring-[#FCA5FF]'" />
                        <div v-if="form.errors.password" id="login-password-error" role="alert"
                            class="text-2xs text-red-400 mt-1 block font-bold">
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <div class="flex items-center justify-between px-1">
                        <label for="login-remember" class="flex items-center gap-2 cursor-pointer group">
                            <input
                                id="login-remember"
                                type="checkbox"
                                v-model="form.remember"
                                class="w-4 h-4 rounded bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-purple-500 focus:ring-0 focus:ring-offset-0 cursor-pointer" />
                            <span class="text-2xs font-bold text-gray-500 group-hover:text-gray-300 transition-colors">
                                Ingat Saya
                            </span>
                        </label>
                        <Link v-if="canResetPassword" :href="route('password.request')"
                            class="text-2xs font-bold text-purple-500 hover:text-white transition-colors">
                            Lupa Password?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-gradient-to-br from-brand-deep to-brand-soft text-white font-bold text-sm uppercase tracking-widest py-4 rounded-xl active:scale-95 transition-all mt-4 flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed disabled:active:scale-100">
                        <svg v-if="form.processing" class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ form.processing ? 'Memproses...' : 'Masuk' }}
                    </button>
                </form>

                <div class="relative flex items-center justify-center mt-8 mb-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-400"></div>
                    </div>
                    <div class="relative flex justify-center text-2xs uppercase font-bold tracking-widest">
                        <span class="bg-gray-800 px-4">Atau</span>
                    </div>
                </div>

                <a :href="route('google.login')"
                    class="w-full flex items-center justify-center gap-3 bg-gradient-to-br from-gray-900 to-gray-800 text-white border border-white/10 font-bold text-2xs uppercase tracking-widest py-4 rounded-xl active:scale-95 transition-all hover:border-[#FCA5FF] hover:text-white">
                    <!-- Google 'G' logo inline SVG — tidak bergantung CDN eksternal -->
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Lanjut dengan Google
                </a>

                <p class="text-center text-[11px] font-bold text-gray-500 mt-10">
                    Belum punya akun?
                    <Link :href="route('register')" class="text-purple-500 hover:text-white transition-colors">
                        Daftar di sini
                    </Link>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Fallback lokal — akan dihapus setelah animasi dipindahkan ke app.css */
@keyframes slide-up {
    from { transform: translateY(20px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}
.animate-slide-up {
    animation: slide-up 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}
::-webkit-scrollbar { width: 0; background: transparent; }
</style>
