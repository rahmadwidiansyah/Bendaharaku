<script setup>
import { onMounted, nextTick } from 'vue';

const props = defineProps({
    adSlot: {
        type: String,
        required: true
    },
    adFormat: {
        type: String,
        default: 'auto'
    },
    fullWidthResponsive: {
        type: Boolean,
        default: true
    }
});

onMounted(() => {
    // Memberikan sedikit waktu agar DOM benar-benar siap
    nextTick(() => {
        try {
            (window.adsbygoogle = window.adsbygoogle || []).push({});
        } catch (e) {
            console.error('AdSense error:', e);
        }
    });
});
</script>

<template>
    <div class="my-4 sm:my-6 overflow-hidden rounded-xl border border-[var(--color-border-subtle)] bg-[var(--color-surface-raised)]/50 min-h-[100px] flex items-center justify-center">
        <!-- Google Ad Unit -->
        <ins class="adsbygoogle"
             style="display:block"
             data-ad-client="ca-pub-1031540480419431"
             :data-ad-slot="adSlot"
             :data-ad-format="adFormat"
             :data-full-width-responsive="fullWidthResponsive ? 'true' : 'false'"></ins>
    </div>
</template>

<style scoped>
/* Opsional: gaya tambahan untuk container iklan */
.adsbygoogle {
    width: 100%;
}
</style>
