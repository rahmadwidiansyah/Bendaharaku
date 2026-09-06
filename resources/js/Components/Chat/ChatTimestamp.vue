<script setup>
/**
 * ChatTimestamp.vue
 *
 * Komponen reusable untuk render timestamp pesan.
 * Format: HH:mm (24 jam) dengan locale id-ID.
 *
 * Usage:
 *   <ChatTimestamp :datetime="message.created_at" class="text-[var(--color-text-primary)]/40" />
 */

import { computed } from 'vue'

const props = defineProps({
    datetime: { type: String, default: null },
    locale:   { type: String, default: 'id-ID' },
})

const timeLabel = computed(() => {
    if (!props.datetime) return ''
    try {
        return new Date(props.datetime).toLocaleTimeString(props.locale, {
            hour: '2-digit', minute: '2-digit', hour12: false,
        })
    } catch { return '' }
})
</script>

<template>
    <time
        v-if="timeLabel"
        :datetime="datetime"
        class="text-2xs select-none tabular-nums"
        v-bind="$attrs"
    >{{ timeLabel }}</time>
</template>
