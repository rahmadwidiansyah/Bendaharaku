/**
 * chatIcons.js — Map emoji (legacy) → lucide-vue-next kebab names
 * untuk halaman /chat. Web chat HARUS pakai lucide, bukan emoji,
 * demi konsistensi design system & aksesibilitas.
 *
 * Jika input sudah berupa lucide name (kebab-case) → kembalikan langsung.
 * Jika emoji tidak dikenal → fallback lucide yang relevan.
 */

export const emojiToLucideMap = {
    // ── Transaction types
    '↑': 'trending-up',
    '↓': 'trending-down',
    '⇄': 'arrow-left-right',
    '🤝': 'handshake', // fallback jika tidak ada → hand-coins
    '•': 'circle-dot',
    // Debt / receivable alts
    '🟢': 'trending-up',
    '🔴': 'trending-down',
    '🔵': 'arrow-left-right',

    // ── Detail rows
    '📂': 'folder',
    '👛': 'wallet',
    '📥': 'wallet',
    '👤': 'user',
    '📅': 'calendar',

    // ── Status badges
    '×': 'x',
    '●': 'check-circle-2',
    '◐': 'clock-3',

    // ── Source badges
    '💬': 'message-circle',
    '📡': 'send',
    '📱': 'smartphone',
    '🎮': 'gamepad-2',
    '⚡': 'zap',
    '✏️': 'pencil',
    '🌐': 'globe',
    '✏️ ': 'pencil',
    '📂 ': 'folder-down',

    // ── Report / meta
    '🎯': 'target',
    '🤖': 'bot',
    '⏱': 'clock-3',
    '📊': 'bar-chart-3',
    '✅': 'check',
    '💳': 'credit-card',
    '💰': 'wallet',
    '💸': 'wallet',
    '💵': 'banknote',
    '🏷️': 'tag',
    '📈': 'trending-up',
    '📋': 'clipboard-list',
    '📄': 'file-text',
    '📉': 'line-chart',
    '❓': 'circle-help',
    '👋': 'hand',
    '⚙️': 'settings',
    '🏦': 'building-2',
    '🏪': 'store',
    '🍔': 'utensils',
    '🚗': 'car',
    '📚': 'book-open',
    '🏠': 'house',
    '🛒': 'shopping-cart',
    '☕': 'coffee',
    '🧴': 'droplets',
    '👕': 'shirt',
    '🛠️': 'wrench',
    '🎁': 'gift',
    '🚀': 'rocket',
    '🍃': 'leaf',
    '🔄': 'refresh-cw',
    '📤': 'upload',
    '🤑': 'hand-coins',
    '💳 ': 'credit-card',
    '✦': 'sparkles',
    // bullets fallback
    '·': 'dot',
}

const lucideAliases = {
    handshake: 'handshake', // if not found, fallback to hand-coins below
}

export function toLucide(icon) {
    if (!icon) return 'circle-help'
    const raw = String(icon).trim()
    if (!raw) return 'circle-help'
    // URL / file path → biarkan (akan dirender <img>)
    if (raw.startsWith('http') || raw.includes('.') || raw.includes('/')) return raw
    // sudah lucide (kebab lowercase dengan dash, tanpa spasi, bukan emoji)
    if (/^[a-z0-9-]+$/.test(raw) && raw.length > 1) {
        // cek alias handshake yang belum ada di lucide-vue-next (ada di v0.4xx? fallback)
        if (lucideAliases[raw]) return raw
        return raw
    }
    // emoji map
    if (emojiToLucideMap[raw]) return emojiToLucideMap[raw]
    // coba tanpa variation selector
    const stripped = raw.replace(/[\uFE0F\u200D]/g, '')
    if (emojiToLucideMap[stripped]) return emojiToLucideMap[stripped]
    // fallback aman
    return 'circle-help'
}

// khusus untuk lucide yang mungkin belum ada di versi terpasang — map ke alternatif
export const lucideFallback = {
    handshake: 'hand-coins',
    'gamepad-2': 'gamepad-2',
    'building-2': 'building-2',
}

export function safeLucide(name) {
    // lucide-vue-next export pascalCase; kita cek nanti di AppIcon fallback.
    // Jika tidak ada, pakai alternatif.
    if (lucideFallback[name]) {
        // untuk handshake kita prefer hand-coins
        if (name === 'handshake') return 'hand-coins'
    }
    return name
}
