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

        <div class="w-full max-w-md relative z-10 flex flex-col justify-center min-h-screen bg-gray-800 px-6 py-10">

            <div class="animate-slide-up w-full">
                <div class="text-center mb-10">
                    <ApplicationLogo class="w-20 h-20 rounded-xl mx-auto mb-5" />
                    <h1 class="text-3xl font-bold text-white tracking-tight">Bendaharaku</h1>
                    <p class="text-2xs text-gray-500 uppercase tracking-widest mt-2 font-bold">Sign in to continue</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label
                            class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Email</label>
                        <input type="email" v-model="form.email" required autofocus
                            class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-4 text-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all" />
                        <div v-if="form.errors.email" class="text-2xs text-red-500 mt-1 block font-bold">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Password</label>
                        <input type="password" v-model="form.password" required
                            class="w-full bg-gradient-to-br from-gray-800 to-gray-900 border border-white/10 text-white rounded-xl p-4 text-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all" />
                        <div v-if="form.errors.password" class="text-2xs text-red-500 mt-1 block font-bold">
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <div class="flex items-center justify-between px-1">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" v-model="form.remember"
                                class="w-4 h-4 rounded bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-purple-500 focus:ring-0 focus:ring-offset-0 cursor-pointer" />
                            <span
                                class="text-2xs font-bold text-gray-500 group-hover:text-gray-300 transition-colors">Ingat
                                Saya</span>
                        </label>
                        <Link v-if="canResetPassword" :href="route('password.request')"
                            class="text-2xs font-bold text-purple-500 hover:text-white transition-colors">
                            Lupa Password?
                        </Link>
                    </div>

                    <button type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing"
                        class="w-full bg-gradient-to-br from-purple-800 to-purple-500 text-white font-bold text-sm uppercase tracking-widest py-4 rounded-xl active:scale-95 transition-transform mt-4">
                        Masuk
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
                    <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google"
                        class="w-5 h-5" />
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
