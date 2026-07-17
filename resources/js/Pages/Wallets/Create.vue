<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import EmojiPicker from '@/Components/EmojiPicker.vue';
import { computed } from 'vue';

const form = useForm({
    name: '',
    balance: '',
    icon: '💳',
    icon_file: null,
    keyword: '',
    group_type: 'Liquid',
    is_pinned: false,
});

const submit = () => {
    form.post(route('wallets.store'));
};

const setGroup = (group) => {
    form.group_type = group;
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
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head title="Tambah Dompet" />
        <div class="p-5 w-full lg:max-w-4xl mx-auto lg:px-8 relative animate-slide-up opacity-0"
            style="animation-delay: 50ms;">

            <header class="flex justify-between items-center mb-8 pt-4">
                <div>
                    <h1 class="text-2xl font-bold text-white tracking-tight">Tambah Dompet</h1>
                </div>
                <Link :href="route('dashboard')"
                    class="w-10 h-10 rounded-full bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-gray-400 active:scale-90 transition-all hover:text-white hover:border-white/50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </Link>
            </header>

            <div class="grid grid-cols-2 gap-2 mb-8 p-1.5 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl animate-slide-up opacity-0"
                style="animation-delay: 100ms;">
                <button type="button" @click="setGroup('Liquid')"
                    :class="form.group_type === 'Liquid' ? 'w-full text-2xs font-semibold py-3 rounded-xl bg-linear-to-br from-gray-800 to-gray-700 text-white shadow-sm transition-all border border-white/10' : 'w-full text-2xs font-semibold py-3 rounded-xl text-gray-400 hover:text-white transition-all'">
                    Liquid
                </button>
                <button type="button" @click="setGroup('Asset')"
                    :class="form.group_type === 'Asset' ? 'w-full text-2xs font-semibold py-3 rounded-xl bg-linear-to-br from-gray-800 to-gray-700 text-white shadow-sm transition-all border border-white/10' : 'w-full text-2xs font-semibold py-3 rounded-xl text-gray-400 hover:text-white transition-all'">
                    Investment
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <div class="flex flex-col animate-slide-up opacity-0 relative z-10" style="animation-delay: 150ms;">
                    <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Saldo Awal</label>
                    <div
                        class="h-[60px] bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl px-5 flex items-center group focus-within:border-purple-500 focus-within:ring-1 focus-within:ring-purple-500 transition-all">
                        <span class="text-base font-bold text-purple-500 mr-3 opacity-80">Rp</span>
                        <input type="text" inputmode="numeric" required placeholder="0" v-model="displayAmount"
                            class="w-full bg-transparent border-none text-white p-0 text-xl font-bold placeholder-gray-700 focus:ring-0 focus:outline-none caret-purple-500">
                    </div>
                    <div v-if="form.errors.balance" class="text-red-500 text-2xs mt-1">{{ form.errors.balance }}</div>
                </div>

                <div class="flex gap-5 items-end animate-slide-up opacity-0 relative z-50"
                    style="animation-delay: 200ms;">
                    <div class="flex-none">
                        <EmojiPicker v-model="form.icon" @file-selected="(file) => form.icon_file = file" />
                    </div>

                    <div class="flex-1 flex flex-col justify-end">
                        <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Nama Dompet</label>
                        <div
                            class="h-[60px] bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 rounded-xl px-5 flex items-center group focus-within:border-purple-500 focus-within:ring-1 focus-within:ring-purple-500 transition-all caret-purple-500">
                            <input type="text" v-model="form.name" required placeholder="Contoh: BCA Utama"
                                class="w-full bg-transparent border-none text-white p-0 text-base font-medium placeholder-gray-600 focus:ring-0 focus:outline-none">
                        </div>
                        <div v-if="form.errors.name" class="text-red-500 text-2xs mt-1">{{ form.errors.name }}</div>
                    </div>
                </div>

                <div class="flex flex-col animate-slide-up opacity-0 relative z-40" style="animation-delay: 250ms;">
                    <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Keyword AI (Pisahkan Koma)</label>
                    <div
                        class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-4 group focus-within:border-purple-500 focus-within:ring-1 focus-within:ring-purple-500 transition-all caret-purple-500">
                        <input type="text" v-model="form.keyword" placeholder="Contoh: BCA, Transfer, Mbanking..."
                            class="w-full bg-transparent border-none text-white p-0 text-sm placeholder-gray-600 focus:ring-0 focus:outline-none">
                    </div>
                    <p class="text-2xs text-gray-500 mt-2 ml-1 italic">* Digunakan untuk deteksi otomatis oleh sistem
                        AI.</p>
                </div>

                <div class="flex items-center justify-between bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 rounded-xl p-4 animate-slide-up opacity-0 relative z-35"
                    style="animation-delay: 275ms;">
                    <div>
                        <label class="block text-sm font-medium text-gray-300">Pin ke Dashboard</label>
                        <p class="text-2xs text-gray-500 mt-0.5">Tampilkan dompet ini di halaman utama</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.is_pinned" class="sr-only peer">
                        <div
                            class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-500">
                        </div>
                    </label>
                </div>

                <div class="pt-4 animate-slide-up opacity-0 relative z-30" style="animation-delay: 300ms;">
                    <button type="submit" :disabled="form.processing"
                        class="w-full bg-linear-to-br from-purple-800 to-purple-600 text-white font-bold text-sm tracking-wide py-4 rounded-xl hover:-translate-y-0.5 active:scale-95 transition-all duration-200">
                        Simpan Dompet
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
input:focus,
input:active {
    outline: none !important;
    box-shadow: none !important;
}

@keyframes slideUpFade {
    0% {
        opacity: 0;
        transform: translateY(20px);
    }

    100% {
        opacity: 1;
        transform: none;
    }
}

.animate-slide-up {
    animation: slideUpFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
