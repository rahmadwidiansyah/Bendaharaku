<script setup>
/**
 * MessageText.vue
 *
 * Teks dari AI — dirender sebagai Markdown.
 * Raw text TIDAK diubah, MarkdownRenderer hanya bekerja saat tampil di Web.
 */
import MarkdownRenderer from './MarkdownRenderer.vue'

defineProps({
    component: { type: Object, required: true },
})
</script>

<template>
    <MarkdownRenderer
        :content="component.text ?? ''"
        :class="component.bold ? '[&_strong]:text-white [&_b]:text-white font-semibold' : ''"
    />
</template>
