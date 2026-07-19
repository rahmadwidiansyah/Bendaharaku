<script setup>
/**
 * MarkdownRenderer.vue
 *
 * Render Markdown menjadi HTML aman untuk Web Chat.
 * Data asli TIDAK diubah — ini hanya presentation layer.
 * Fallback ke plain text jika parse gagal.
 *
 * Props:
 *   content — string Markdown asli
 *   inline  — true = inline span (untuk teks 1 baris, no <p> wrapper)
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
    <!-- Block: untuk multi-line, paragraph, heading, list, dll -->
    <div
        v-if="!inline"
        class="markdown-body text-sm leading-relaxed text-gray-200 break-words"
        v-html="html"
    />
    <!-- Inline: untuk teks pendek dalam konteks lain -->
    <span
        v-else
        class="markdown-inline text-sm text-gray-200 break-words"
        v-html="html"
    />
</template>

<style scoped>
/* ── Block Markdown Typography ─────────────────────────────────── */
.markdown-body :deep(p) {
    margin-bottom: 0.45em;
    line-height: 1.65;
}
.markdown-body :deep(p:last-child) {
    margin-bottom: 0;
}

/* Bold & Italic */
.markdown-body :deep(strong),
.markdown-body :deep(b) {
    font-weight: 700;
    color: #fff;
}
.markdown-body :deep(em),
.markdown-body :deep(i) {
    font-style: italic;
    color: #d1d5db;
}
.markdown-body :deep(del),
.markdown-body :deep(s) {
    text-decoration: line-through;
    color: #6b7280;
}

/* Inline code */
.markdown-body :deep(code) {
    font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
    font-size: 0.82em;
    background: rgba(168, 85, 247, 0.12);
    color: #c084fc;
    padding: 0.1em 0.35em;
    border-radius: 4px;
    border: 1px solid rgba(168, 85, 247, 0.2);
}

/* Code block */
.markdown-body :deep(pre) {
    background: rgba(0, 0, 0, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    padding: 0.7em 1em;
    overflow-x: auto;
    margin: 0.5em 0;
}
.markdown-body :deep(pre code) {
    background: transparent;
    border: none;
    padding: 0;
    color: #e2e8f0;
    font-size: 0.85em;
}

/* Headings */
.markdown-body :deep(h1),
.markdown-body :deep(h2),
.markdown-body :deep(h3),
.markdown-body :deep(h4) {
    font-weight: 700;
    color: #fff;
    line-height: 1.3;
    margin: 0.5em 0 0.25em;
}
.markdown-body :deep(h1) { font-size: 1.1em; }
.markdown-body :deep(h2) { font-size: 1.0em; }
.markdown-body :deep(h3) { font-size: 0.95em; }
.markdown-body :deep(h4) { font-size: 0.9em; color: #d1d5db; }

/* Lists */
.markdown-body :deep(ul),
.markdown-body :deep(ol) {
    padding-left: 1.35em;
    margin: 0.25em 0;
}
.markdown-body :deep(li) {
    margin-bottom: 0.2em;
    color: #d1d5db;
    line-height: 1.5;
}
.markdown-body :deep(li > p) { margin-bottom: 0; }

/* Blockquote */
.markdown-body :deep(blockquote) {
    border-left: 3px solid rgba(168, 85, 247, 0.5);
    margin: 0.5em 0;
    padding: 0.3em 0.8em;
    color: #9ca3af;
    background: rgba(168, 85, 247, 0.06);
    border-radius: 0 6px 6px 0;
}
.markdown-body :deep(blockquote p) { margin-bottom: 0; color: inherit; }

/* Horizontal rule */
.markdown-body :deep(hr) {
    border: none;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    margin: 0.6em 0;
}

/* Links */
.markdown-body :deep(a) {
    color: #a78bfa;
    text-decoration: underline;
    text-underline-offset: 2px;
}
.markdown-body :deep(a:hover) { color: #c4b5fd; }

/* Tables */
.markdown-body :deep(table) {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85em;
    margin: 0.5em 0;
}
.markdown-body :deep(th),
.markdown-body :deep(td) {
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 0.3em 0.6em;
    text-align: left;
}
.markdown-body :deep(th) {
    background: rgba(255, 255, 255, 0.05);
    font-weight: 600;
    color: #e2e8f0;
}
</style>
