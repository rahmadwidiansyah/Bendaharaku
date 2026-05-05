<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    startDate: String,
    endDate: String,
    action: String,
});

const showModal = ref(false);

const form = useForm({
    start_date: props.startDate,
    end_date: props.endDate,
});

const toggleModal = () => {
    showModal.value = !showModal.value;
};

const setQuickDate = (type) => {
    const today = new Date();
    let start, end;

    // FIX: Format tanggal secara manual dengan padStart agar tetap menggunakan Local Timezone
    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    if (type === 'thisYear') {
        start = new Date(today.getFullYear(), 0, 1);
        end = new Date(today.getFullYear(), 11, 31);
    } else if (type === 'thisMonth') {
        start = new Date(today.getFullYear(), today.getMonth(), 1);
        end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    } else if (type === 'lastMonth') {
        start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        end = new Date(today.getFullYear(), today.getMonth(), 0);
    }

    form.start_date = formatDate(start);
    form.end_date = formatDate(end);
};

const submit = () => {
    form.get(props.action, {
        preserveState: true,
        onSuccess: () => {
            showModal.value = false;
        },
    });
};
</script>

<template>
    <div>
        <button type="button" @click="toggleModal" class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-gray-400 hover:text-white rounded-xl px-4 flex items-center justify-center active:scale-95 transition-all relative h-[48px] z-30">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            <span v-if="startDate !== new Date().toISOString().split('T')[0].substring(0, 8) + '01'" class="absolute top-2 right-3 w-2 h-2 bg-purple-500 rounded-full"></span>
        </button>

        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[9999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-6" @click.self="toggleModal">
                <div class="w-full max-w-sm mx-auto bg-gradient-to-b from-gray-900 to-gray-800 rounded-2xl border border-white/10 p-6 relative z-[9999] animate-pop-in shadow-2xl">
                    
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-sm font-black text-white uppercase tracking-widest">Rentang Waktu</h3>
                        <button type="button" @click="toggleModal" class="w-8 h-8 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-400 active:scale-90 transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    
                    <form @submit.prevent="submit" class="space-y-5">
                        <div class="grid grid-cols-2 gap-3 text-left">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-purple-500 uppercase tracking-widest pl-1">Dari</label>
                                <input type="date" v-model="form.start_date" class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-3 text-xs" style="color-scheme: dark;">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-purple-500 uppercase tracking-widest pl-1">Sampai</label>
                                <input type="date" v-model="form.end_date" class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-3 text-xs" style="color-scheme: dark;">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-2 pt-2">
                            <button type="button" @click="setQuickDate('thisYear')" class="bg-gradient-to-br from-gray-900 to-gray-800 text-xs font-bold text-gray-400 py-3 rounded-xl border border-white/10 uppercase hover:text-white transition-colors">Tahun Ini</button>
                            <button type="button" @click="setQuickDate('thisMonth')" class="bg-gradient-to-br from-gray-900 to-gray-800 text-xs font-bold text-gray-400 py-3 rounded-xl border border-white/10 uppercase hover:text-white transition-colors">Bulan Ini</button>
                            <button type="button" @click="setQuickDate('lastMonth')" class="bg-gradient-to-br from-gray-900 to-gray-800 text-xs font-bold text-gray-400 py-3 rounded-xl border border-white/10 uppercase hover:text-white transition-colors">Bulan Lalu</button>
                        </div>
                        
                        <button type="submit" :disabled="form.processing" class="w-full bg-gradient-to-br from-purple-800 to-purple-500 text-white font-black text-xs uppercase py-3.5 rounded-xl active:scale-95 transition-all mt-2 shadow-lg shadow-purple-500/20">
                            Terapkan Filter
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
@keyframes pop-in { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
.animate-pop-in { animation: pop-in 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
</style>
