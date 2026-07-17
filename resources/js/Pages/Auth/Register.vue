<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    // Validasi password match sebelum request ke server
    if (form.password && form.password_confirmation && form.password !== form.password_confirmation) {
        form.setError('password_confirmation', 'Password tidak cocok. Pastikan keduanya sama.');
        return;
    }

    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>

    <Head title="Daftar" />

    <div
        class="antialiased selection:text-black relative flex justify-center min-h-screen bg-gray-800 text-white font-sans overflow-x-hidden">

        <div class="w-full max-w-md relative z-10 flex flex-col justify-center min-h-screen bg-gray-800 px-6 py-10">

            <div class="animate-slide-up w-full">
                <div class="text-center mb-10">
                    <ApplicationLogo class="w-20 h-20 rounded-xl mx-auto mb-5" />
                    <h1 class="text-3xl font-bold text-white tracking-tight">Buat Akun</h1>
                    <p class="text-2xs text-gray-500 uppercase tracking-widest mt-2 font-bold">
                        Mulai kelola keuanganmu
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Nama Lengkap -->
                    <div>
                        <label for="reg-name"
                            class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
                            Nama Lengkap
                        </label>
                        <input
                            id="reg-name"
                            type="text"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                            :aria-invalid="!!form.errors.name"
                            aria-describedby="reg-name-error"
                            placeholder="John Doe"
                            class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border text-white rounded-xl p-4 text-sm focus:outline-none focus:ring-1 transition-all"
                            :class="form.errors.name ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-white/10 focus:border-purple-500 focus:ring-purple-500'" />
                        <div v-if="form.errors.name" id="reg-name-error" role="alert"
                            class="text-2xs text-red-400 mt-1 block font-bold">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="reg-email"
                            class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
                            Email
                        </label>
                        <input
                            id="reg-email"
                            type="email"
                            v-model="form.email"
                            required
                            autocomplete="email"
                            :aria-invalid="!!form.errors.email"
                            aria-describedby="reg-email-error"
                            placeholder="email@contoh.com"
                            class="w-full bg-gradient-to-br from-gray-800 to-gray-900 border text-white rounded-xl p-4 text-sm focus:outline-none focus:ring-1 transition-all"
                            :class="form.errors.email ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-white/10 focus:border-purple-500 focus:ring-purple-500'" />
                        <div v-if="form.errors.email" id="reg-email-error" role="alert"
                            class="text-2xs text-red-400 mt-1 block font-bold">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="reg-password"
                            class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
                            Password
                        </label>
                        <input
                            id="reg-password"
                            type="password"
                            v-model="form.password"
                            required
                            autocomplete="new-password"
                            :aria-invalid="!!form.errors.password"
                            aria-describedby="reg-password-error reg-password-hint"
                            placeholder="Minimal 8 karakter"
                            class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border text-white rounded-xl p-4 text-sm focus:outline-none focus:ring-1 transition-all"
                            :class="form.errors.password ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-white/10 focus:border-purple-500 focus:ring-purple-500'" />
                        <p id="reg-password-hint" class="text-2xs text-gray-600 mt-1 ml-1">Minimal 8 karakter</p>
                        <div v-if="form.errors.password" id="reg-password-error" role="alert"
                            class="text-2xs text-red-400 mt-1 block font-bold">
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <!-- Konfirmasi Password -->
                    <div>
                        <label for="reg-password-confirm"
                            class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
                            Konfirmasi Password
                        </label>
                        <input
                            id="reg-password-confirm"
                            type="password"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                            :aria-invalid="!!form.errors.password_confirmation"
                            aria-describedby="reg-password-confirm-error"
                            placeholder="Ulangi password"
                            class="w-full bg-gradient-to-br from-gray-800 to-gray-900 border text-white rounded-xl p-4 text-sm focus:outline-none focus:ring-1 transition-all"
                            :class="(form.errors.password_confirmation || (form.password_confirmation && form.password !== form.password_confirmation))
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                                : (form.password_confirmation && form.password === form.password_confirmation)
                                    ? 'border-green-500/50 focus:border-green-500 focus:ring-green-500'
                                    : 'border-white/10 focus:border-purple-500 focus:ring-purple-500'" />
                        <!-- Realtime mismatch hint (sebelum submit) -->
                        <p v-if="form.password_confirmation && form.password !== form.password_confirmation && !form.errors.password_confirmation"
                            class="text-2xs text-amber-400 mt-1 ml-1 font-bold">
                            ⚠ Password belum cocok
                        </p>
                        <div v-if="form.errors.password_confirmation" id="reg-password-confirm-error" role="alert"
                            class="text-2xs text-red-400 mt-1 block font-bold">
                            {{ form.errors.password_confirmation }}
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-gradient-to-br from-purple-800 to-purple-500 text-white font-bold text-sm uppercase tracking-widest py-4 rounded-xl active:scale-95 transition-all mt-6 flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed disabled:active:scale-100">
                        <svg v-if="form.processing" class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ form.processing ? 'Mendaftarkan...' : 'Daftar Sekarang' }}
                    </button>
                </form>

                <div class="relative flex items-center justify-center mt-8 mb-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-500"></div>
                    </div>
                    <div class="relative flex justify-center text-2xs uppercase font-bold tracking-widest">
                        <span class="bg-gray-800 px-4 text-gray-500">Atau</span>
                    </div>
                </div>

                <a :href="route('google.login')"
                    class="w-full flex items-center justify-center gap-3 bg-gradient-to-br from-gray-900 to-gray-800 text-white border border-white/10 font-bold text-2xs uppercase tracking-widest py-4 rounded-xl active:scale-95 transition-all hover:border-purple-500 hover:text-white">
                    <!-- Google 'G' logo inline SVG — tidak bergantung CDN eksternal -->
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Daftar dengan Google
                </a>

                <p class="text-center text-2xs font-bold text-gray-500 mt-10">
                    Sudah punya akun?
                    <Link :href="route('login')" class="text-purple-500 hover:text-white transition-colors">
                        Masuk di sini
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
