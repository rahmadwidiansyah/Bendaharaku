<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Lupa Password" />

    <div class="antialiased selection:bg-[#FCA5FF] selection:text-black relative flex justify-center min-h-screen bg-[#121212] text-white font-sans overflow-x-hidden">
        
        <div class="fixed top-[20%] left-[50%] -translate-x-1/2 w-[300px] h-[300px] bg-[#FCA5FF] blur-[120px] opacity-[0.15] rounded-full pointer-events-none z-0"></div>

        <div class="w-full max-w-md relative z-10 flex flex-col justify-center min-h-screen border-x border-[#262626]/50 bg-[#121212]/80 backdrop-blur-xl px-6 py-10">
            
            <div class="animate-slide-up w-full">
                <div class="text-center mb-8">
                    <ApplicationLogo class="w-20 h-20 rounded-xl mx-auto mb-5" />
                    <h1 class="text-3xl font-bold text-white tracking-tight">Lupa Password?</h1>
                    <p class="text-xs text-gray-500 uppercase tracking-widest mt-2 font-bold">Kami bantu meresetnya</p>
                </div>

                <p class="text-[11px] text-gray-400 text-center mb-6 leading-relaxed px-2">
                    Tidak masalah. Beri tahu kami alamat email Anda, dan kami akan mengirimi Anda tautan untuk menyetel ulang kata sandi.
                </p>

                <div v-if="status" class="mb-6 p-4 rounded-xl bg-[#1A1A1A] border border-green-500/30 text-[11px] font-bold text-green-400 text-center shadow-sm">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Email Anda</label>
                        <input
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-xl p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all shadow-inner"
                        />
                        <div v-if="form.errors.email" class="text-xs text-red-500 mt-1 block font-bold">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <button
                        type="submit"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        class="w-full bg-[#FCA5FF] text-[#121212] font-bold text-sm uppercase tracking-widest py-4 rounded-xl shadow-[0_0_20px_rgba(252,165,255,0.2)] active:scale-95 transition-transform mt-4"
                    >
                        Kirim Link Reset
                    </button>
                </form>

                <p class="text-center text-[11px] font-bold text-gray-500 mt-8">
                    Ingat password Anda? 
                    <Link :href="route('login')" class="text-[#FCA5FF] hover:text-white transition-colors">
                        Masuk di sini
                    </Link>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes slide-up { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.animate-slide-up { animation: slide-up 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
::-webkit-scrollbar { width: 0px; background: transparent; }
</style>
