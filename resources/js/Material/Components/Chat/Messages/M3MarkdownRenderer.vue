<script setup>
/**
 * M3MarkdownRenderer — render markdown aman (paritas MarkdownRenderer Default).
 * Parser tetap `useMarkdown`; hanya tipografi/warna yang memakai token M3.
 * Kelas `.m3-md` / `.m3-md-inline` dipakai M3ChatMessage untuk override di
 * bubble user.
 */
import { computed } from 'vue'
import { parseMarkdown } from '@/Composables/useMarkdown.js'

const props = defineProps({
    content: { type: String, default: '' },
    inline:  { type: Boolean, default: false },
})

const html = computed(() =>
    parseMarkdown(props.content, { inline: props.inline })
)
</script>

<template>
    <div
        v-if="!inline"
        class="m3-md"
        v-html="html"
    />
    <span
        v-else
        class="m3-md-inline"
        v-html="html"
    />
</template>

<style scoped>
.m3-md,
.m3-md-inline {
    font-size: var(--m3-type-body-medium-size);
    line-height: 1.6;
    color: var(--m3-color-on-surface);
    overflow-wrap: break-word;
}

.m3-md :deep(p) {
    margin-bottom: 0.45em;
    line-height: 1.6;
}

.m3-md :deep(p:last-child) {
    margin-bottom: 0;
}

.m3-md :deep(strong),
.m3-md :deep(b) {
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    color: var(--m3-color-on-surface);
}

.m3-md :deep(em),
.m3-md :deep(i) {
    font-style: italic;
}

.m3-md :deep(del),
.m3-md :deep(s) {
    text-decoration: line-through;
    color: var(--m3-color-on-surface-variant);
}

.m3-md :deep(code) {
    font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
    font-size: 0.82em;
    background: var(--m3-color-surface-container-lowest);
    color: var(--m3-color-primary);
    padding: 0.1em 0.35em;
    border-radius: var(--m3-radius-xs);
    border: 1px solid var(--m3-color-outline-variant);
}

.m3-md :deep(pre) {
    background: var(--m3-color-surface-container-lowest);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-sm);
    padding: 0.7em 1em;
    overflow-x: auto;
    margin: 0.5em 0;
}

.m3-md :deep(pre code) {
    background: transparent;
    border: none;
    padding: 0;
    color: var(--m3-color-on-surface);
    font-size: 0.85em;
}

.m3-md :deep(h1),
.m3-md :deep(h2),
.m3-md :deep(h3),
.m3-md :deep(h4) {
    font-weight: var(--m3-type-emphasized-body-large-weight);
    color: var(--m3-color-on-surface);
    line-height: 1.3;
    margin: 0.5em 0 0.25em;
}

.m3-md :deep(h1) { font-size: 1.1em; }
.m3-md :deep(h2) { font-size: 1em; }
.m3-md :deep(h3) { font-size: 0.95em; }
.m3-md :deep(h4) { font-size: 0.9em; color: var(--m3-color-on-surface-variant); }

.m3-md :deep(ul),
.m3-md :deep(ol) {
    padding-left: 1.35em;
    margin: 0.25em 0;
}

.m3-md :deep(li) {
    margin-bottom: 0.2em;
    line-height: 1.5;
}

.m3-md :deep(li > p) { margin-bottom: 0; }

.m3-md :deep(blockquote) {
    border-left: 3px solid var(--m3-color-primary);
    margin: 0.5em 0;
    padding: 0.3em 0.8em;
    color: var(--m3-color-on-surface-variant);
    background: color-mix(in srgb, var(--m3-color-primary) 8%, transparent);
    border-radius: 0 var(--m3-radius-sm) var(--m3-radius-sm) 0;
}

.m3-md :deep(blockquote p) { margin-bottom: 0; color: inherit; }

.m3-md :deep(hr) {
    border: none;
    border-top: 1px solid var(--m3-color-outline-variant);
    margin: 0.6em 0;
}

.m3-md :deep(a) {
    color: var(--m3-color-primary);
    text-decoration: underline;
    text-underline-offset: 2px;
}

.m3-md :deep(table) {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85em;
    margin: 0.5em 0;
}

.m3-md :deep(th),
.m3-md :deep(td) {
    border: 1px solid var(--m3-color-outline-variant);
    padding: 0.3em 0.6em;
    text-align: left;
}

.m3-md :deep(th) {
    background: var(--m3-color-surface-container-highest);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    color: var(--m3-color-on-surface-variant);
}
</style>
