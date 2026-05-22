<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    modelValue: String,
    id: { type: String, default: 'icon' },
    defaultEmoji: { type: String, default: '💳' },
});

const emit = defineEmits(['update:modelValue', 'file-selected']);

const showModal = ref(false);
const activeCategory = ref('finance');
const previewUrl = ref(null);

const emojiData = [
    { id: 'finance', icon: '💰', list: ['💳','💰','🏦','💵','🪙','🏧','💸','💎','📈','🧾','🧧'] },
    { id: 'smileys', icon: '😀', list: ['😀','😂','🥰','😎','🤔','😭','😡','🤯','😴','🤮','🤡','👻','👽','🤖'] },
    { id: 'food', icon: '🍔', list: ['🍎','🍔','🍕','☕','🍺','🍦','🥩','🍙','🍱','🍰'] },
    { id: 'transport', icon: '🚗', list: ['🚗','🛵','🚲','✈️','🚀','🛳️','⛽','🚆','🚜'] },
    { id: 'places', icon: '🏠', list: ['🏠','🏢','🏥','🏪','🏫','🏖️','⛺','🎡','⛩️'] },
    { id: 'objects', icon: '💡', list: ['📱','💻','🛍️','🎁','🔑','🔓','💊','🛒','📸','🎮','🔧'] },
    { id: 'animals', icon: '🐾', list: ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🦁', '🐸', '🐢', '🐍', '🦄', '🐝', '🦋'] },
    { 
        id: 'images', 
        icon: '🖼️', 
        type: 'custom',
        list: [
            '/storage/icons/wallets/bca.png',
            '/storage/icons/wallets/bni.webp',
            '/storage/icons/wallets/bri.svg',
            '/storage/icons/wallets/mandiri.png',
            '/storage/icons/wallets/gopay2.webp',
            '/storage/icons/wallets/dana.png'
            '/storage/icons/wallets/linkaja.png',
            '/storage/icons/wallets/ocbc.png',
            '/storage/icons/wallets/neobank.jpeg',
            '/storage/icons/wallets/blue.webp',
            '/storage/icons/wallets/byond.png',
            '/storage/icons/wallets/bsi.png',
            '/storage/icons/wallets/wonder.webp',
            '/storage/icons/wallets/cimb.png',
            '/storage/icons/wallets/btn.webp',
            '/storage/icons/wallets/livin.png',
            '/storage/icons/wallets/brimo.png',
            '/storage/icons/wallets/jagobank.webp',
            '/storage/icons/wallets/seabank.webp',
            '/storage/icons/wallets/shopeepay.webp',
            '/storage/icons/wallets/ovo.webp',  
        ]
    } 
];

const toggleModal = () => {
    showModal.value = !showModal.value;
};

const selectEmoji = (emoji) => {
    emit('update:modelValue', emoji);
    previewUrl.value = null;
    showModal.value = false;
};

const selectCustom = (path) => {
    emit('update:modelValue', path);
    previewUrl.value = path.startsWith('http') ? path : `/storage/${path}`;
    showModal.value = false;
};

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        previewUrl.value = URL.createObjectURL(file);
        emit('file-selected', file);
        showModal.value = false;
    }
};

const isImage = (val) => {
    return val?.includes('.') || val?.includes('/');
};

const getImageUrl = (val) => {
    if (previewUrl.value) return previewUrl.value;
    if (val?.startsWith('http')) return val;
    return `/storage/${val}`;
};
</script>

<template>
    <div class="shrink-0 flex flex-col justify-end">
        <label class="block text-sm font-medium text-gray-300 mb-2 ml-1 text-center">Ikon</label>
        
        <button type="button" @click="toggleModal"
            class="w-[60px] h-[60px] bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl flex items-center justify-center text-3xl hover:border-purple-500 focus:border-purple-500 transition-all active:scale-95 shadow-inner overflow-hidden relative">
            
            <img v-if="isImage(modelValue) || previewUrl" :src="getImageUrl(modelValue)" class="absolute inset-0 w-full h-full object-contain p-1">
            <span v-else>{{ modelValue || defaultEmoji }}</span>
        </button>
        
        <!-- MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-[9999] bg-transparent flex items-center justify-center p-4" @click.self="toggleModal">
            <div class="relative w-full max-w-sm bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 shadow-2xl flex flex-col overflow-hidden max-h-[70vh] animate-pop-in">
                
                <div class="px-5 py-4 border-b border-white/10 flex justify-between items-center bg-transparent">
                    <h3 class="text-sm font-semibold text-white uppercase tracking-widest">Pilih Ikon</h3>
                    <button type="button" @click="toggleModal" class="text-gray-400 hover:text-white p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="p-5 overflow-y-auto no-scrollbar flex-1 bg-gray-900">
                    <div v-if="emojiData.find(c => c.id === activeCategory)?.type === 'custom'" class="grid grid-cols-3 gap-3">
                        <button type="button" @click="$refs.fileInput.click()" class="aspect-square bg-gray-800 border-2 border-white/10 hover:border-purple-500 rounded-xl flex flex-col items-center justify-center text-gray-400 hover:text-purple-500 transition-all active:scale-95">
                            <svg class="w-8 h-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            <span class="text-[9px] font-bold uppercase tracking-widest">Upload</span>
                        </button>
                        <input type="file" ref="fileInput" @change="handleFileUpload" class="hidden" accept="image/*">
                        

                    </div>
                    <div v-else class="grid grid-cols-6 gap-3">
                        <button v-for="emoji in emojiData.find(c => c.id === activeCategory).list" :key="emoji" type="button" @click="selectEmoji(emoji)" class="text-2xl p-2 hover:bg-gray-800 rounded-xl transition-all active:scale-75 flex items-center justify-center hover:scale-110">
                            {{ emoji }}
                        </button>
                    </div>
                </div>

                <div class="p-2 border-t border-white/10 bg-gray-900 flex justify-around items-center">
                    <button v-for="cat in emojiData" :key="cat.id" type="button" @click="activeCategory = cat.id" :class="['p-2 rounded-xl text-xl transition-all opacity-50 hover:opacity-100', activeCategory === cat.id ? 'bg-gray-800 opacity-100 shadow-inner' : '']">
                        {{ cat.icon }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes pop-in { 0% { transform: scale(0.95); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
.animate-pop-in { animation: pop-in 0.2s ease-out forwards; }
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
