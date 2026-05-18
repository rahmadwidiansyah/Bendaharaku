<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import EmojiPicker from '@/Components/EmojiPicker.vue';
import { computed } from 'vue';

const props = defineProps({
    wallet: Object,
});

const form = useForm({
    name: props.wallet.name,
    balance: props.wallet.balance,
    icon: props.wallet.icon || '💳',
    icon_file: null,
    keyword: props.wallet.keyword || '',
    _method: 'PUT'
});

const submit = () => {
    form.post(route('wallets.update', props.wallet.id));
};

const displayAmount = computed({
    get() {
        return form.balance ? new Intl.NumberFormat('id-ID').format(form.balance) : '';
    },
    set(val) {
        const num = String(val).replace(/\D/g, '');
        form.balance = num;
    }
});

const deleteForm = useForm({});
const deleteWallet = () => {
    if (confirm('Yakin ingin menghapus dompet ini?')) {
        deleteForm.delete(route('wallets.destroy', props.wallet.id));
    }
};
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">
        <Head title="Edit Dompet" />
        <div class="p-5 pb-32 w-full lg:max-w-4xl mx-auto lg:px-8 relative animate-slide-up opacity-0" style="animation-delay: 50ms;">
            
            <header class="flex justify-between items-center mb-8 pt-4">
                <div>
                    <h1 class="text-2xl font-bold text-white tracking-tight">Edit Dompet</h1>
                </div>
                <Link :href="route('wallets.show', wallet.id)" class="w-10 h-10 rounded-full bg-[#1A1A1A] border border-[#333] flex items-center justify-center text-gray-400 active:scale-90 transition-all shadow-md hover:text-white hover:border-gray-500">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </Link>
            </header>

            <form @submit.prevent="submit" class="space-y-6">
                
                <div class="flex flex-col animate-slide-up opacity-0 relative z-10" style="animation-delay: 150ms;">
                    <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Saldo Saat Ini</label>
                    <div class="h-[60px] bg-[#1A1A1A] border border-[#333] rounded-xl px-5 flex items-center group focus-within:border-[#FCA5FF] focus-within:ring-1 focus-within:ring-[#FCA5FF] transition-all shadow-inner">
                        <span class="text-base font-bold text-[#FCA5FF] mr-3 opacity-80">Rp</span>
                        <input type="text" inputmode="numeric" required v-model="displayAmount"
                            class="w-full bg-transparent border-none text-white p-0 text-xl font-bold placeholder-gray-600 focus:ring-0 focus:outline-none caret-[#FCA5FF]">
                    </div>
                    <div v-if="form.errors.balance" class="text-red-500 text-xs mt-1">{{ form.errors.balance }}</div>
                    <p class="text-xs text-gray-500 mt-2 ml-1 italic">* Ubah manual jika ada selisih saldo.</p>
                </div>

                <div class="flex gap-3 items-end animate-slide-up opacity-0 relative z-50" style="animation-delay: 200ms;">
                    <div class="flex-none">
                        <EmojiPicker v-model="form.icon" @file-selected="(file) => form.icon_file = file" :defaultEmoji="wallet.icon || '💳'" />
                    </div>

                    <div class="flex-1 flex flex-col justify-end">
                        <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Nama Dompet</label>
                        <div class="h-[60px] bg-[#1A1A1A] border border-[#333] rounded-xl px-5 flex items-center group focus-within:border-[#FCA5FF] focus-within:ring-1 focus-within:ring-[#FCA5FF] transition-all shadow-inner">
                            <input type="text" v-model="form.name" required placeholder="Contoh: BCA Utama" 
                                class="w-full bg-transparent border-none text-white p-0 text-base font-medium focus:ring-0 focus:outline-none">
                        </div>
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>
                </div>

                <div class="flex flex-col animate-slide-up opacity-0 relative z-40" style="animation-delay: 250ms;">
                    <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Keyword AI (Pisahkan Koma)</label>
                    <div class="bg-[#1A1A1A] border border-[#333] rounded-xl p-4 group focus-within:border-[#FCA5FF] focus-within:ring-1 focus-within:ring-[#FCA5FF] transition-all">
                        <input type="text" v-model="form.keyword" placeholder="Contoh: bca, transfer, mbanking..." 
                            class="w-full bg-transparent border-none text-white p-0 text-sm placeholder-gray-600 focus:ring-0 focus:outline-none">
                    </div>
                    <div v-if="form.errors.keyword" class="text-red-500 text-xs mt-1">{{ form.errors.keyword }}</div>
                </div>

                <div class="pt-4 space-y-3 animate-slide-up opacity-0 relative z-30" style="animation-delay: 300ms;">
                    <button type="submit" :disabled="form.processing" class="w-full bg-[#FCA5FF] text-[#121212] font-bold text-sm tracking-wide py-4 rounded-xl shadow-[0_0_20px_rgba(252,165,255,0.15)] hover:shadow-[0_0_25px_rgba(252,165,255,0.3)] hover:-translate-y-0.5 active:scale-95 transition-all duration-200">
                        Update Dompet
                    </button>
                </div>
            </form>

            <form @submit.prevent="deleteWallet" class="mt-4 animate-slide-up opacity-0 relative z-20" style="animation-delay: 350ms;">
                <button type="submit" :disabled="deleteForm.processing" class="w-full bg-transparent border border-red-500/20 text-red-500/70 font-medium text-xs uppercase tracking-widest py-4 rounded-xl hover:bg-red-500/10 active:scale-95 transition-all">
                    Hapus Dompet
                </button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
input:focus, input:active { outline: none !important; box-shadow: none !important; }

@keyframes slideUpFade {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: none; } 
}
.animate-slide-up {
    animation: slideUpFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
