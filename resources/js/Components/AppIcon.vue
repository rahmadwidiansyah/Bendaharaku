
<script setup>
import { computed, ref, shallowRef } from 'vue';
import * as lucideIcons from 'lucide-vue-next';
import { detectIconType, isImageIcon, resolveImageUrl, kebabToPascal } from '@/Composables/useIcon.js';

const props = defineProps({
    icon: {
        type: String,
        default: null,
    },
    class: {
        type: String,
        default: 'w-6 h-6',
    },
    imgClass: {
        type: String,
        default: '',
    },
    fallback: {
        type: String,
        default: 'circle-help',
    },
});

const iconType = computed(() => detectIconType(props.icon));
const hasError = ref(false);

const effectiveIcon = computed(() => {
    return (hasError.value || !props.icon) ? props.fallback : props.icon;
});

const effectiveIconType = computed(() => detectIconType(effectiveIcon.value));

const isImage = computed(() => {
    return !hasError.value && isImageIcon(props.icon);
});

const imageUrl = computed(() => {
    return resolveImageUrl(props.icon, iconType.value);
});

const lucideComponent = computed(() => {
    if (effectiveIconType.value !== 'lucide') return null;
    const componentName = kebabToPascal(effectiveIcon.value);
    return lucideIcons[componentName] || lucideIcons[kebabToPascal(props.fallback)];
});

const renderAsText = computed(() => {
    const type = effectiveIconType.value;
    return type === 'emoji' || type === 'none' || (type === 'lucide' && !lucideComponent.value);
});

const fallbackText = computed(() => {
    if (effectiveIcon.value?.length === 1) {
        return effectiveIcon.value;
    }
    // Fallback to first letter if fallback is a word
    const fallbackType = detectIconType(props.fallback);
    if(fallbackType === 'lucide') return null; // It will be rendered as icon
    return props.fallback?.substring(0, 1) || '?';
});

const handleError = () => {
    hasError.value = true;
};
</script>

<template>
    <img
        v-if="isImage"
        :src="imageUrl"
        :class="[props.class, imgClass]"
        @error="handleError"
        alt="Icon"
    />
    <component
        v-else-if="lucideComponent"
        :is="lucideComponent"
        :class="props.class"
        aria-hidden="true"
    />
    <span v-else-if="renderAsText" :class="props.class">
        {{ effectiveIcon.length > 1 ? fallbackText : effectiveIcon }}
    </span>
</template>
