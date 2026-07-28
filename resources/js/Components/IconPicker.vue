
<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppIcon from '@/Components/AppIcon.vue';
import BaseModal from '@/Components/BaseModal.vue';
import ImageCropModal from '@/Components/ImageCropModal.vue';

const { t } = useI18n();

const props = defineProps({
    modelValue: String,
    id: String,
    defaultIcon: {
        type: String,
        default: 'wallet',
    },
});

const emit = defineEmits(['update:modelValue', 'file-selected']);

const isOpen = ref(false);
const showCropModal = ref(false);
const activeTab = ref('Finance');
const searchTerm = ref('');
const previewUrl = ref(null);
const hasCustomImage = ref(false);

const allIcons = {
    Finance: ['wallet', 'wallet-2', 'credit-card', 'coins', 'banknote', 'piggy-bank', 'land-plot', 'trending-up', 'trending-down', 'dollar-sign', 'circle-dollar-sign', 'hand-coins', 'receipt', 'gift', 'gem', 'safe'],
    Lifestyle: ['utensils', 'coffee', 'cup-soda', 'pizza', 'ice-cream', 'cake', 'apple', 'beef', 'shopping-bag', 'shopping-cart', 'shirt', 'footprints', 'gamepad-2', 'music', 'film', 'popcorn', 'beer', 'wine', 'candy', 'cookie'],
    Places: ['home', 'building-2', 'building', 'hotel', 'plane', 'car', 'bike', 'bus', 'train', 'ship', 'fuel', 'map-pin', 'store', 'globe', 'tree-pine', 'flower-2'],
    Tech: ['smartphone', 'laptop', 'monitor', 'wifi', 'zap', 'plug', 'briefcase', 'graduation-cap', 'book-open', 'pencil', 'calculator', 'calendar'],
    Animals: ['dog', 'cat', 'paw-print', 'fish', 'bird', 'snail', 'turtle', 'rabbit', 'rat', 'bee', 'butterfly', 'dragonfly', 'feather'],
    Misc: ['heart-pulse', 'pill', 'stethoscope', 'dumbbell', 'baby', 'sprout', 'leaf', 'sun', 'moon', 'clouds', 'umbrella', 'key', 'lock', 'wrench', 'hammer', 'trash-2', 'package', 'truck', 'handshake', 'file-text', 'folder', 'inbox', 'send', 'download', 'upload', 'plus', 'star', 'hearts', 'crown', 'medal', 'trophy', 'wand'],
};

const filteredIcons = computed(() => {
    if (searchTerm.value.trim()) {
        const lowerTerm = searchTerm.value.toLowerCase();
        return Object.values(allIcons).flat().filter(icon => icon.includes(lowerTerm));
    }
    return allIcons[activeTab.value];
});

const tabNames = computed(() => Object.keys(allIcons));

function openModal() {
    isOpen.value = true;
}

function closeModal() {
    isOpen.value = false;
    searchTerm.value = '';
}

function selectIcon(icon) {
    hasCustomImage.value = false;
    previewUrl.value = null;
    emit('update:modelValue', icon);
    closeModal();
}

function openCropForUpload() {
    showCropModal.value = true;
}

function handleCropped(blob) {
    const file = new File([blob], 'icon.webp', { type: blob.type });
    previewUrl.value = URL.createObjectURL(blob);
    hasCustomImage.value = true;
    emit('file-selected', file);
    showCropModal.value = false;
    closeModal();
}
</script>

<template>
    <div>
        <button type="button" @click="openModal"
            class="w-[60px] h-[60px] flex items-center justify-center rounded-xl bg-gray-900 border border-white/10 hover:border-purple-500/50 transition-all overflow-hidden">
            <img v-if="hasCustomImage && previewUrl" :src="previewUrl" class="w-full h-full object-cover" alt="Icon" />
            <AppIcon v-else :icon="modelValue || defaultIcon" class="w-8 h-8 text-purple-400" />
        </button>

        <BaseModal :show="isOpen" maxWidth="lg" :showCloseBtn="false" @close="closeModal">
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <h3 class="text-sm font-black text-white uppercase tracking-widest truncate">{{ t('iconPicker.title') }}</h3>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="openCropForUpload"
                            class="w-8 h-8 shrink-0 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-400 hover:text-purple-400 hover:border-purple-500/40 active:scale-90 transition-all"
                            :title="t('iconPicker.upload')">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21zm16.5-13.5h-15" />
                                <circle cx="15.75" cy="7.5" r="1.5" />
                            </svg>
                        </button>
                        <button type="button"
                            class="w-8 h-8 shrink-0 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:bg-white/10 active:scale-90 transition-all"
                            aria-label="Tutup" @click="closeModal">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
            <div class="flex flex-col gap-3">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" v-model="searchTerm" :placeholder="t('iconPicker.search')"
                        class="w-full h-10 pl-10 pr-4 rounded-xl bg-gray-800 border border-white/10 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-purple-500/50 focus:ring-1 focus:ring-purple-500/20 transition-all" />
                </div>

                <div class="flex gap-1 overflow-x-auto scrollbar-none pb-1 -mx-1 px-1">
                    <button v-for="tab in tabNames" :key="tab" @click="activeTab = tab"
                        class="shrink-0 px-3 py-1.5 rounded-lg text-2xs font-bold uppercase tracking-wider transition-all whitespace-nowrap"
                        :class="activeTab === tab
                            ? 'bg-purple-500/15 text-purple-400 border border-purple-500/30'
                            : 'text-gray-500 hover:text-gray-300 border border-transparent'">
                        {{ t('iconPicker.tabs.' + tab) }}
                    </button>
                </div>

                <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-1.5 max-h-72 overflow-y-auto scrollbar-thin py-1">
                    <div v-for="icon in filteredIcons" :key="icon" @click="selectIcon(icon)"
                        class="aspect-square flex items-center justify-center cursor-pointer rounded-xl bg-gray-800/50 border border-white/5 hover:border-purple-500/40 hover:bg-purple-500/5 transition-all">
                        <AppIcon :icon="icon" class="w-5 h-5 sm:w-6 sm:h-6 text-purple-400" />
                    </div>
                    <div v-if="filteredIcons.length === 0"
                        class="col-span-full flex flex-col items-center justify-center py-10 text-gray-500">
                        <AppIcon icon="search-x" class="w-8 h-8 mb-2 text-purple-400" />
                        <p class="text-2xs font-bold tracking-wider">{{ t('iconPicker.notFound') }}</p>
                    </div>
                </div>
            </div>
        </BaseModal>

        <ImageCropModal
            v-model="showCropModal"
            :title="t('iconPicker.cropTitle')"
            :aspectRatio="1"
            :circle="false"
            :maxSizeKb="500"
            outputFormat="image/webp"
            @cropped="handleCropped"
        />
    </div>
</template>
