/**
 * useMarkdown.js
 *
 * Composable untuk parsing Markdown → HTML aman di Web Chat.
 *
 * ── Prinsip ──────────────────────────────────────────────────────
 *   - Data asli TIDAK PERNAH dimodifikasi
 *   - Parsing hanya terjadi saat render di Web Chat
 *   - Telegram, API, export, audit tetap menerima Markdown mentah
 *   - Semua output di-sanitize DOMPurify (cegah XSS)
 *
 * ── Support ──────────────────────────────────────────────────────
 *   **bold**, *italic*, `inline code`, # heading, - list,
 *   1. numbered, > quote, ---, [link](url), table, ~~strikethrough~~
 *
 * ── Fallback ─────────────────────────────────────────────────────
 *   Jika parsing gagal → return teks asli (escaped) tanpa crash
 */

import { marked }    from 'marked'
import DOMPurify     from 'dompurify'

// ── Konfigurasi marked ────────────────────────────────────────────
marked.setOptions({
    // breaks: true → newline tunggal jadi <br> (seperti WhatsApp/Telegram)
    breaks: true,
    // gfm: true → GitHub Flavored Markdown (table, strikethrough, dll)
    gfm: true,
})

// ── Konfigurasi DOMPurify ─────────────────────────────────────────
// Whitelist tag dan atribut yang aman untuk chat bubble
const PURIFY_CONFIG = {
    ALLOWED_TAGS: [
        'p', 'br', 'b', 'strong', 'i', 'em', 's', 'del',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li',
        'blockquote',
        'pre', 'code',
        'hr',
        'a',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'span',
    ],
    ALLOWED_ATTR: [
        'href', 'target', 'rel',
        'class',
    ],
    // Cegah javascript: URL di link
    ALLOWED_URI_REGEXP: /^(?:https?|mailto):/i,
    // Paksa semua link target="_blank" + rel="noopener"
    ADD_ATTR: ['target'],
    FORCE_BODY: false,
}

// Tambahkan hook: semua <a> → target=_blank dan rel=noopener noreferrer
if (typeof DOMPurify.addHook === 'function') {
    DOMPurify.addHook('afterSanitizeAttributes', (node) => {
        if (node.tagName === 'A') {
            node.setAttribute('target', '_blank')
            node.setAttribute('rel', 'noopener noreferrer')
        }
    })
}

/**
 * Parse satu string Markdown menjadi HTML yang aman.
 *
 * @param {string} markdown  - Teks Markdown asli
 * @param {object} options   - Opsi override
 * @param {boolean} options.inline - Jika true, gunakan parseInline (no <p> wrapper)
 * @returns {string}         - HTML aman siap di-v-html
 */
export function parseMarkdown(markdown, { inline = false } = {}) {
    if (!markdown || typeof markdown !== 'string') return ''

    try {
        // Parse: inline mode untuk teks pendek 1 baris, block untuk multi-line
        const rawHtml = inline
            ? marked.parseInline(markdown)
            : marked.parse(markdown)

        // Sanitize
        return DOMPurify.sanitize(rawHtml, PURIFY_CONFIG)
    } catch (err) {
        // Fallback: escape teks asli, tampilkan as-is tanpa crash
        console.warn('[useMarkdown] parse error, falling back to plain text', err)
        return escapeHtml(markdown)
    }
}

/**
 * Escape HTML entities — dipakai sebagai fallback saat parser gagal.
 */
function escapeHtml(str) {
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
}

/**
 * Vue composable — kembalikan fungsi render reaktif.
 * Gunakan ini di dalam <script setup> komponen.
 *
 * @example
 *   const { renderMarkdown } = useMarkdown()
 *   const html = renderMarkdown(text)         // block
 *   const html = renderMarkdown(text, true)   // inline
 */
export function useMarkdown() {
    const renderMarkdown = (text, inline = false) =>
        parseMarkdown(text, { inline })

    return { renderMarkdown }
}
