
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
            class="w-14 h-14 sm:w-[60px] sm:h-[60px] flex items-center justify-center rounded-xl bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] hover:border-[var(--color-brand-border)] transition-all overflow-hidden">
            <img v-if="hasCustomImage && previewUrl" :src="previewUrl" class="w-full h-full object-cover" alt="Icon" />
            <AppIcon v-else :icon="modelValue || defaultIcon" class="w-8 h-8 text-[var(--color-brand)]" />
        </button>

        <BaseModal :show="isOpen" maxWidth="adaptive" :showCloseBtn="false" @close="closeModal">
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <h3 class="text-sm font-black text-[var(--color-text-primary)] uppercase tracking-widest truncate">{{ t('iconPicker.title') }}</h3>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="openCropForUpload"
                            class="w-8 h-8 shrink-0 flex items-center justify-center rounded-full bg-[var(--color-surface-muted)]/50 border border-[var(--color-border-default)] text-[var(--color-text-muted)] hover:text-[var(--color-brand)] hover:border-[var(--color-brand-border)] active:scale-90 transition-all"
                            :title="t('iconPicker.upload')">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21zm16.5-13.5h-15" />
                                <circle cx="15.75" cy="7.5" r="1.5" />
                            </svg>
                        </button>
                        <button type="button"
                            class="w-8 h-8 shrink-0 flex items-center justify-center rounded-full bg-[var(--color-surface-muted)]/50 border border-[var(--color-border-default)] text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-surface-muted)] active:scale-90 transition-all"
                            aria-label="Tutup" @click="closeModal">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
            <div class="flex flex-col gap-4">
                <!-- Search bar -->
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[var(--color-text-muted)] pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" v-model="searchTerm" :placeholder="t('iconPicker.search')"
                        class="w-full h-11 sm:h-12 pl-12 pr-4 rounded-xl bg-[var(--color-surface-muted)]/50 border border-[var(--color-border-default)] text-[var(--color-text-primary)] text-sm placeholder:text-[var(--color-text-muted)] focus:outline-none focus:border-[var(--color-brand-border)] focus:ring-1 focus:ring-[var(--color-brand)]/20 transition-all" />
                </div>

                <!-- Category chips -->
                <div class="flex gap-2 overflow-x-auto scrollbar-none pb-1 -mx-1 px-1 snap-x">
                    <button v-for="tab in tabNames" :key="tab" @click="activeTab = tab"
                        class="shrink-0 snap-start px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all whitespace-nowrap"
                        :class="activeTab === tab
                            ? 'bg-[var(--color-brand-subtle)] text-[var(--color-brand)] border border-[var(--color-brand-border)]'
                            : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)] border border-transparent hover:bg-[var(--color-surface-muted)]'">
                        {{ t('iconPicker.tabs.' + tab) }}
                    </button>
                </div>

                <!-- Icon grid -->
                <div class="grid grid-cols-5 gap-2 max-h-[50vh] overflow-y-auto scrollbar-thin py-1">
                    <div v-for="icon in filteredIcons" :key="icon" @click="selectIcon(icon)"
                        class="aspect-square flex items-center justify-center cursor-pointer rounded-xl bg-[var(--color-surface-muted)]/50 border border-[var(--color-border-subtle)] hover:border-[var(--color-brand-border)] hover:bg-[var(--color-brand-subtle)] active:scale-90 transition-all">
                        <AppIcon :icon="icon" class="w-6 h-6 text-[var(--color-brand)]" />
                    </div>
                    <div v-if="filteredIcons.length === 0"
                        class="col-span-full flex flex-col items-center justify-center py-10 text-[var(--color-text-muted)]">
                        <AppIcon icon="search-x" class="w-8 h-8 mb-2 text-[var(--color-brand)]" />
                        <p class="text-xs font-bold tracking-wider">{{ t('iconPicker.notFound') }}</p>
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
