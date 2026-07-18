/**
 * useChatCommands.js
 *
 * Composable untuk command registry di Web Chat.
 *
 * Commands diambil dari props Inertia (sudah diisi saat server render),
 * sehingga tidak ada extra HTTP request saat halaman pertama kali dibuka.
 * Refresh via GET /chat/commands tersedia jika diperlukan.
 *
 * Tanggung jawab:
 * - Menyimpan daftar commands (reaktif)
 * - State CommandSheet (bottom sheet)
 * - Filter commands per category untuk UI grouping
 * - Handle pemilihan command → insert ke textarea
 */

import { ref, computed } from 'vue'
import axios from 'axios'

export function useChatCommands(initialCommands = []) {
    // ── State ─────────────────────────────────────────────────────
    const commands      = ref([...initialCommands])
    const isSheetOpen   = ref(false)
    const isRefreshing  = ref(false)

    // ── Computed ──────────────────────────────────────────────────

    /**
     * Commands dikelompokkan per category untuk tampilan di sheet.
     * Format: { general: [...], finance: [...], report: [...], settings: [...] }
     */
    const commandsByCategory = computed(() => {
        const groups = {}
        for (const cmd of commands.value) {
            const cat = cmd.category ?? 'general'
            if (!groups[cat]) groups[cat] = []
            groups[cat].push(cmd)
        }
        return groups
    })

    /**
     * Label display per category (untuk heading di CommandSheet).
     */
    const categoryLabels = {
        general:  'Umum',
        finance:  'Keuangan',
        report:   'Laporan',
        settings: 'Pengaturan',
    }

    /**
     * Urutan category yang diinginkan di UI.
     */
    const categoryOrder = ['general', 'finance', 'report', 'settings']

    /**
     * Categories yang ada (urutan tetap sesuai categoryOrder).
     */
    const categories = computed(() =>
        categoryOrder.filter((cat) => commandsByCategory.value[cat]?.length > 0)
    )

    // ── Actions ───────────────────────────────────────────────────

    function openSheet() {
        isSheetOpen.value = true
    }

    function closeSheet() {
        isSheetOpen.value = false
    }

    /**
     * Refresh daftar command dari server (opsional, setelah update).
     */
    async function refreshCommands() {
        if (isRefreshing.value) return

        isRefreshing.value = true
        try {
            const { data } = await axios.get(route('chat.commands'))
            if (data.commands) {
                commands.value = data.commands
            }
        } catch (err) {
            console.warn('useChatCommands: refresh failed', err)
        } finally {
            isRefreshing.value = false
        }
    }

    /**
     * User memilih command dari sheet.
     * Kembalikan command string (misalnya '/saldo') ke pemanggil
     * agar bisa dimasukkan ke textarea.
     *
     * @param {object} cmd - Command object dari registry
     * @returns {string} - Command string dengan trailing space untuk UX yang nyaman
     */
    function selectCommand(cmd) {
        closeSheet()
        return cmd.command + ' '
    }

    return {
        // State
        commands,
        isSheetOpen,
        isRefreshing,

        // Computed
        commandsByCategory,
        categoryLabels,
        categories,

        // Actions
        openSheet,
        closeSheet,
        refreshCommands,
        selectCommand,
    }
}
