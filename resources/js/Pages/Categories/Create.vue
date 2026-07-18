<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmojiPicker from '@/Components/EmojiPicker.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    types: Array,
    defaultType: String,
});

const form = useForm({
    category_name: '',
    type_id: props.types.find(t => t.name === props.defaultType)?.id || props.types.find(t => t.name === 'Expense')?.id || '',
    icon: '📁',
    icon_file: null,
    keyword: '',
});

const handleFileSelected = (file) => {
    form.icon_file = file;
    form.icon = ''; // Clear text icon if file is selected
};

const submit = () => {
    form.post(route('categories.store'));
};

const goBack = () => {
    // Mengecek apakah ada riwayat halaman sebelumnya di browser
    if (window.history.length > 1) {
        window.history.back();
    } else {
        // Fallback jika user membuka link ini secara langsung (direct access)
        router.visit(route('transactions.create'));
    }
};
</script>

<template>
    <AuthenticatedLayout :fullWidth="true" :hideNav="true">

        <Head title="Tambah Kategori" />

        <div class="p-5 w-full lg:max-w-4xl mx-auto lg:px-8 relative animate-fade-in-up">

            <header class="flex justify-between items-center mb-8 pt-4">
                <div class="hidden lg:block">
                    <p class="text-2xs text-gray-300 font-semibold mb-1 uppercase tracking-wider">Vault</p>
                    <h1 class="text-2xl font-bold text-white tracking-tight">Tambah Kategori</h1>
                </div>
                <button type="button" @click="goBack"
                    class="w-10 h-10 rounded-full bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-gray-400 active:scale-90 transition-all shadow-md hover:text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </header>

            <form @submit.prevent="submit" class="space-y-6">

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Tipe Kategori</label>
                    <div
                        class="p-1.5 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl shadow-inner">
                        <div
                            class="text-xs font-semibold py-3 text-center rounded-xl bg-white/5 text-purple-500 border border-white/10">
                            {{ defaultType === 'Income' ? 'Pemasukan' : 'Pengeluaran' }}
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 items-end">
                    <EmojiPicker v-model="form.icon" @file-selected="handleFileSelected" />

                    <div class="flex-1 flex flex-col justify-end">
                        <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Nama Kategori</label>
                        <div
                            class="h-[60px] bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl px-5 flex items-center group focus-within:border-purple-500 focus-within:ring-1 focus-within:ring-purple-500 transition-all shadow-inner">
                            <input type="text" v-model="form.category_name" required
                                placeholder="Contoh: Makan Siang..."
                                class="w-full bg-transparent border-none text-white p-0 text-base font-medium placeholder-gray-600 focus:ring-0 focus:outline-none">
                        </div>
                    </div>
                </div>

                <div class="flex flex-col">
                    <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Keyword AI (Pisahkan Koma)</label>
                    <div
                        class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-4 group focus-within:border-purple-500 focus-within:ring-1 focus-within:ring-purple-500 transition-all shadow-inner">
                        <input type="text" v-model="form.keyword" placeholder="Contoh: mcd, kfc, warkop, bensin..."
                            class="w-full bg-transparent border-none text-white p-0 text-sm placeholder-gray-600 focus:ring-0 focus:outline-none">
                    </div>
                    <p class="text-2xs text-gray-500 mt-2 ml-1 italic">* Digunakan untuk deteksi otomatis oleh sistem
                        AI.</p>
                </div>

                <div class="pt-4">
                    <button type="submit" :disabled="form.processing"
                        class="w-full bg-linear-to-br from-purple-800 to-purple-600 text-white font-bold text-sm tracking-wide py-4 rounded-xl hover:-translate-y-0.5 active:scale-95 transition-all duration-200">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Kategori' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in-up {
    0% {
        opacity: 0;
        transform: translateY(15px);
    }

    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>