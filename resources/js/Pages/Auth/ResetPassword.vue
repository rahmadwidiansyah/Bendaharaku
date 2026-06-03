<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>

    <Head title="Reset Password" />

    <div
        class="antialiased selection:bg-[#FCA5FF] selection:text-black relative flex justify-center min-h-screen bg-[#121212] text-white font-sans overflow-x-hidden">

        <div
            class="fixed top-[20%] left-[50%] -translate-x-1/2 w-[300px] h-[300px] bg-[#FCA5FF] blur-[120px] opacity-[0.15] rounded-full pointer-events-none z-0">
        </div>

        <div
            class="w-full max-w-md relative z-10 flex flex-col justify-center min-h-screen border-x border-[#262626]/50 bg-[#121212]/80 backdrop-blur-xl px-6 py-10">

            <div class="animate-slide-up w-full">
                <div class="text-center mb-10">
                    <ApplicationLogo class="w-20 h-20 rounded-xl mx-auto mb-5" />
                    <h1 class="text-3xl font-bold text-white tracking-tight">Reset Password</h1>
                    <p class="text-2xs text-gray-500 uppercase tracking-widest mt-2 font-bold">Buat sandi baru Anda</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label
                            class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Email</label>
                        <input type="email" v-model="form.email" required autocomplete="username"
                            class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-xl p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all shadow-inner" />
                        <div v-if="form.errors.email" class="text-2xs text-red-500 mt-1 block font-bold">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Password
                            Baru</label>
                        <input type="password" v-model="form.password" required autocomplete="new-password" autofocus
                            class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-xl p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all shadow-inner" />
                        <div v-if="form.errors.password" class="text-2xs text-red-500 mt-1 block font-bold">
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Konfirmasi
                            Password</label>
                        <input type="password" v-model="form.password_confirmation" required autocomplete="new-password"
                            class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-xl p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all shadow-inner" />
                        <div v-if="form.errors.password_confirmation"
                            class="text-2xs text-red-500 mt-1 block font-bold">
                            {{ form.errors.password_confirmation }}
                        </div>
                    </div>

                    <button type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing"
                        class="w-full bg-[#FCA5FF] text-[#121212] font-bold text-sm uppercase tracking-widest py-4 rounded-xl shadow-[0_0_20px_rgba(252,165,255,0.2)] active:scale-95 transition-transform mt-6">
                        Simpan Password
                    </button>
                </form>
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
