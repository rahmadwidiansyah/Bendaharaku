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
        class="markdown-body text-sm leading-relaxed text-text-secondary break-words"
        v-html="html"
    />
    <!-- Inline: untuk teks pendek dalam konteks lain -->
    <span
        v-else
        class="markdown-inline text-sm text-text-secondary break-words"
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
    color: var(--color-text-primary);
}
.markdown-body :deep(em),
.markdown-body :deep(i) {
    font-style: italic;
    color: var(--color-text-secondary);
}
.markdown-body :deep(del),
.markdown-body :deep(s) {
    text-decoration: line-through;
    color: var(--color-text-muted);
}

/* Inline code */
.markdown-body :deep(code) {
    font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
    font-size: 0.82em;
    background: var(--color-brand-subtle);
    color: var(--color-brand);
    padding: 0.1em 0.35em;
    border-radius: 4px;
    border: 1px solid var(--color-brand-border);
}

/* Code block */
.markdown-body :deep(pre) {
    background: var(--color-surface-muted);
    border: 1px solid var(--color-border-default);
    border-radius: 8px;
    padding: 0.7em 1em;
    overflow-x: auto;
    margin: 0.5em 0;
}
.markdown-body :deep(pre code) {
    background: transparent;
    border: none;
    padding: 0;
    color: var(--color-text-primary);
    font-size: 0.85em;
}

/* Headings */
.markdown-body :deep(h1),
.markdown-body :deep(h2),
.markdown-body :deep(h3),
.markdown-body :deep(h4) {
    font-weight: 700;
    color: var(--color-text-primary);
    line-height: 1.3;
    margin: 0.5em 0 0.25em;
}
.markdown-body :deep(h1) { font-size: 1.1em; }
.markdown-body :deep(h2) { font-size: 1.0em; }
.markdown-body :deep(h3) { font-size: 0.95em; }
.markdown-body :deep(h4) { font-size: 0.9em; color: var(--color-text-secondary); }

/* Lists */
.markdown-body :deep(ul),
.markdown-body :deep(ol) {
    padding-left: 1.35em;
    margin: 0.25em 0;
}
.markdown-body :deep(li) {
    margin-bottom: 0.2em;
    color: var(--color-text-secondary);
    line-height: 1.5;
}
.markdown-body :deep(li > p) { margin-bottom: 0; }

/* Blockquote */
.markdown-body :deep(blockquote) {
    border-left: 3px solid var(--color-brand-border);
    margin: 0.5em 0;
    padding: 0.3em 0.8em;
    color: var(--color-text-secondary);
    background: var(--color-brand-subtle);
    border-radius: 0 6px 6px 0;
}
.markdown-body :deep(blockquote p) { margin-bottom: 0; color: inherit; }

/* Horizontal rule */
.markdown-body :deep(hr) {
    border: none;
    border-top: 1px solid var(--color-border-default);
    margin: 0.6em 0;
}

/* Links */
.markdown-body :deep(a) {
    color: var(--color-brand);
    text-decoration: underline;
    text-underline-offset: 2px;
}
.markdown-body :deep(a:hover) { color: var(--color-brand-hover); }

/* Tables */
.markdown-body :deep(table) {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85em;
    margin: 0.5em 0;
}
.markdown-body :deep(th),
.markdown-body :deep(td) {
    border: 1px solid var(--color-border-default);
    padding: 0.3em 0.6em;
    text-align: left;
}
.markdown-body :deep(th) {
    background: var(--color-border-subtle);
    font-weight: 600;
    color: var(--color-text-secondary);
}
</style>
