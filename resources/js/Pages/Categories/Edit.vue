<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmojiPicker from '@/Components/EmojiPicker.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    category: Object,
    types: Array,
});

const form = useForm({
    category_name: props.category.category_name,
    type_id: props.category.type_id,
    icon: props.category.icon,
    icon_file: null,
    keyword: props.category.keyword,
});

const handleFileSelected = (file) => {
    form.icon_file = file;
    form.icon = '';
};

const submit = () => {
    // Laravel won't handle file uploads with PUT, so we use POST with _method=PUT
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(route('categories.update', props.category.id));
};

const destroy = () => {
    if (confirm('Yakin ingin menghapus kategori ini?')) {
        form.delete(route('categories.destroy', props.category.id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit Kategori" />

        <div class="p-5 pb-32 max-w-md mx-auto relative animate-fade-in-up">
            
            <header class="flex justify-between items-center mb-8 pt-4">
                <div>
                    <p class="text-xs text-gray-300 font-semibold mb-1 uppercase tracking-wider">Vault</p>
                    <h1 class="text-2xl font-bold text-white tracking-tight">Edit Kategori</h1>
                </div>
                <Link :href="route('categories.index')" class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-gray-400 active:scale-90 transition-all shadow-md hover:text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </Link>
            </header>

            <form @submit.prevent="submit" class="space-y-6">
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Tipe Kategori</label>
                    <div class="grid grid-cols-2 gap-2 p-1.5 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl shadow-inner">
                        <label v-for="type in types" :key="type.id" class="cursor-pointer">
                            <input type="radio" v-model="form.type_id" :value="type.id" class="hidden peer">
                            <div class="text-xs font-semibold py-3 text-center rounded-xl transition-all border border-transparent text-gray-400 peer-checked:bg-white/5 peer-checked:text-purple-400 peer-checked:border-white/10">
                                {{ type.name === 'Income' ? 'Pemasukan' : 'Pengeluaran' }}
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex gap-3 items-end">
                    <EmojiPicker v-model="form.icon" @file-selected="handleFileSelected" />

                    <div class="flex-1 flex flex-col justify-end">
                        <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Nama Kategori</label>
                        <div class="h-[60px] bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl px-5 flex items-center group focus-within:border-purple-500 focus-within:ring-1 focus-within:ring-purple-500 transition-all shadow-inner">
                            <input type="text" v-model="form.category_name" required placeholder="Contoh: Makan Siang..." 
                                class="w-full bg-transparent border-none text-white p-0 text-base font-medium placeholder-gray-600 focus:ring-0 focus:outline-none">
                        </div>
                    </div>
                </div>

                <div class="flex flex-col">
                    <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Keyword AI (Pisahkan Koma)</label>
                    <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-4 group focus-within:border-purple-500 focus-within:ring-1 focus-within:ring-purple-500 transition-all shadow-inner">
                        <input type="text" v-model="form.keyword" placeholder="Contoh: mcd, kfc, warkop, bensin..." 
                            class="w-full bg-transparent border-none text-white p-0 text-sm placeholder-gray-600 focus:ring-0 focus:outline-none">
                    </div>
                    <p class="text-xs text-gray-500 mt-2 ml-1 italic">* Digunakan untuk deteksi otomatis oleh sistem AI.</p>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" @click="destroy" class="flex-1 bg-red-950/30 border border-red-900/50 text-red-500 font-bold text-sm tracking-wide py-4 rounded-xl active:scale-95 transition-all">
                        Hapus
                    </button>
                    <button type="submit" :disabled="form.processing" class="flex-[2] bg-purple-500 text-[#121212] font-bold text-sm tracking-wide py-4 rounded-xl shadow-lg hover:-translate-y-0.5 active:scale-95 transition-all">
                        {{ form.processing ? 'Menyimpan...' : 'Update Kategori' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in-up { 0% { opacity: 0; transform: translateY(15px); } 100% { opacity: 1; transform: translateY(0); } }
.animate-fade-in-up { animation: fade-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>
