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
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>

    <Head title="Register" />

    <div
        class="antialiased selection:text-black relative flex justify-center min-h-screen bg-gray-800 text-white font-sans overflow-x-hidden">

        <div
            class="fixed top-[20%] left-[50%] -translate-x-1/2 w-[300px] h-[300px] bg-gray-800 blur-[120px] opacity-[0.15] rounded-full pointer-events-none z-0">
        </div>

        <div class="w-full max-w-md relative z-10 flex flex-col justify-center min-h-screen bg-gray-800 px-6 py-10">

            <div class="animate-slide-up w-full">
                <div class="text-center mb-10">
                    <ApplicationLogo class="w-20 h-20 rounded-xl mx-auto mb-5" />
                    <h1 class="text-3xl font-bold text-white tracking-tight">Buat Akun</h1>
                    <p class="text-2xs text-gray-500 uppercase tracking-widest mt-2 font-bold">Mulai kelola keuanganmu
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Nama
                            Lengkap</label>
                        <input type="text" v-model="form.name" required autofocus
                            class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-4 text-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 transition-all"
                            placeholder="John Doe" />
                        <div v-if="form.errors.name" class="text-2xs text-red-500 mt-1 block font-bold">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Email</label>
                        <input type="email" v-model="form.email" required
                            class="w-full bg-gradient-to-br from-gray-800 to-gray-900 border border-white/10 text-white rounded-xl p-4 text-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 transition-all"
                            placeholder="email@contoh.com" />
                        <div v-if="form.errors.email" class="text-2xs text-red-500 mt-1 block font-bold">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Password</label>
                        <input type="password" v-model="form.password" required
                            class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-4 text-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 transition-all"
                            placeholder="Minimal 8 karakter" />
                        <div v-if="form.errors.password" class="text-2xs text-red-500 mt-1 block font-bold">
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Konfirmasi
                            Password</label>
                        <input type="password" v-model="form.password_confirmation" required
                            class="w-full bg-gradient-to-br from-gray-800 to-gray-900 border border-white/10 text-white rounded-xl p-4 text-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 transition-all"
                            placeholder="Ulangi password" />
                        <div v-if="form.errors.password_confirmation"
                            class="text-2xs text-red-500 mt-1 block font-bold">
                            {{ form.errors.password_confirmation }}
                        </div>
                    </div>

                    <button type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing"
                        class="w-full bg-gradient-to-br from-purple-800 to-purple-500 text-white font-bold text-sm uppercase tracking-widest py-4 rounded-xl active:scale-95 transition-transform mt-6">
                        Daftar Sekarang
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
                    <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google"
                        class="w-5 h-5" />
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
@keyframes slide-up {
    from {
        transform: translateY(20px);
        opacity: 0;
    }

    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.animate-slide-up {
    animation: slide-up 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

::-webkit-scrollbar {
    width: 0px;
    background: transparent;
}
</style>
