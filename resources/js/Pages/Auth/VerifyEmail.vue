<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>

    <Head title="Verifikasi Email" />

    <div
        class="antialiased selection:bg-[#FCA5FF] selection:text-black relative flex justify-center min-h-screen bg-[#121212] text-white font-sans overflow-x-hidden">

        <div
            class="fixed top-[20%] left-[50%] -translate-x-1/2 w-[300px] h-[300px] bg-[#FCA5FF] blur-[120px] opacity-[0.15] rounded-full pointer-events-none z-0">
        </div>

        <div
            class="w-full min-w-0 flex-1 relative z-10 flex flex-col justify-center min-h-screen border-x border-[#262626]/50 bg-[#121212]/80 backdrop-blur-xl px-4 sm:px-6 lg:px-8 py-10">

            <div class="animate-slide-up w-full max-w-md mx-auto">
                <div class="text-center mb-8">
                    <ApplicationLogo class="w-20 h-20 rounded-xl mx-auto mb-5" />
                    <h1 class="text-3xl font-bold text-white tracking-tight">Cek Email Anda</h1>
                    <p class="text-2xs text-gray-500 uppercase tracking-widest mt-2 font-bold">Verifikasi untuk
                        melanjutkan</p>
                </div>

                <p class="text-[11px] text-gray-400 text-center mb-6 leading-relaxed px-2">
                    Terima kasih telah mendaftar! Sebelum memulai, bisakah Anda memverifikasi alamat email Anda dengan
                    mengklik tautan yang baru saja kami kirimkan? Jika tidak menerimanya, kami akan mengirim ulang.
                </p>

                <div v-if="verificationLinkSent"
                    class="mb-6 p-4 rounded-xl bg-[#1A1A1A] border border-green-500/30 text-[11px] font-bold text-green-400 text-center shadow-sm">
                    Link verifikasi baru telah dikirim ke alamat email yang Anda daftarkan.
                </div>

                <div class="space-y-4 mt-8">
                    <form @submit.prevent="submit">
                        <button type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing"
                            class="w-full bg-[#FCA5FF] text-[#121212] font-bold text-sm uppercase tracking-widest py-4 rounded-xl shadow-[0_0_20px_rgba(252,165,255,0.2)] active:scale-95 transition-transform">
                            Kirim Ulang Email
                        </button>
                    </form>

                    <form @submit.prevent="useForm().post(route('logout'))" class="text-center mt-6">
                        <button type="submit"
                            class="text-[11px] font-bold text-gray-500 hover:text-white transition-colors uppercase tracking-widest">
                            Keluar Akun
                        </button>
                    </form>
                </div>
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
