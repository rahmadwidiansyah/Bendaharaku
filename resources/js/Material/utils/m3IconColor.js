/**
 * m3IconColor — warna ikon kategori/dompet untuk UI M3.
 *
 * Mengapa tidak memakai getCategoryIconColor/getWalletIconColor langsung:
 * helper Default mengembalikan utilitas Tailwind mentah (`text-gray-500`,
 * `text-white`, `text-purple-400`) yang (a) bukan token M3, (b) `text-white`
 * menjadi tidak terbaca saat M3 memakai skema light. Util ini membaca ref
 * bersama `categoryIconColored` (sumber kebenaran tetap satu — di useIcon.js)
 * lalu memetakan ke kelas M3 bertoken. Hue semantik sama dengan Default.
 */
import { categoryIconColored } from '@/Composables/useIcon.js';

const TYPE_CLASS = {
    Income: 'm3-ico--income',
    Expense: 'm3-ico--expense',
    Transfer: 'm3-ico--transfer',
    Debt: 'm3-ico--debt',
    Receivable: 'm3-ico--receivable',
};

export function getM3CategoryIconClass(typeName) {
    if (!categoryIconColored.value) return 'm3-ico--plain';
    return TYPE_CLASS[typeName] || 'm3-ico--plain';
}

export function getM3WalletIconClass() {
    if (!categoryIconColored.value) return 'm3-ico--plain';
    return 'm3-ico--brand';
}
