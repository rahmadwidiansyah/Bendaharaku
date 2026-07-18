/**
 * useClipboard.js
 *
 * Composable untuk copy teks ke clipboard dengan feedback state.
 * Dipakai di: MessageToolbar, TransactionDetailModal.
 *
 * Usage:
 *   const { copied, copy } = useClipboard()
 *   await copy('teks yang dicopy')
 *
 *   const { copied, copy } = useClipboard({ timeout: 1500 })
 */

import { ref } from 'vue'

/**
 * @param {{ timeout?: number }} options
 * @returns {{ copied: Ref<boolean>, copy: (text: string) => Promise<void> }}
 */
export function useClipboard({ timeout = 2000 } = {}) {
    const copied = ref(false)

    /**
     * Salin teks ke clipboard.
     * Fallback ke execCommand('copy') untuk browser lama / non-HTTPS.
     * @param {string} text
     */
    async function copy(text) {
        if (!text) return

        try {
            await navigator.clipboard.writeText(text)
        } catch {
            // Fallback: textarea trick
            const el = document.createElement('textarea')
            el.value = text
            el.style.cssText = 'position:fixed;opacity:0;pointer-events:none;'
            document.body.appendChild(el)
            el.select()
            document.execCommand('copy')
            document.body.removeChild(el)
        }

        copied.value = true
        setTimeout(() => {
            copied.value = false
        }, timeout)
    }

    return {
        copied,
        copy,
    }
}
