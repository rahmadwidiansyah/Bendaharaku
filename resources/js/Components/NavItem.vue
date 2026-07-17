<script setup>
/**
 * NavItem.vue
 *
 * Komponen navigasi reusable yang mendukung dua mode tampilan:
 *   - mobile: icon + label vertikal (BottomNav)
 *   - desktop: icon + label horizontal (Sidebar)
 *
 * Props:
 *   href         — URL tujuan (wajib)
 *   label        — Teks label navigasi (wajib)
 *   active       — Apakah item ini sedang aktif
 *   isDesktop    — Apakah sedang dalam mode desktop/sidebar
 *   sidebarOpen  — Apakah sidebar sedang terbuka (hanya relevan di desktop)
 *
 * Slots:
 *   icon         — SVG icon yang ditampilkan
 *
 * Accessibility:
 *   - aria-current="page" saat aktif
 *   - aria-label dari prop label
 */

import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    isDesktop: {
        type: Boolean,
        default: false,
    },
    sidebarOpen: {
        type: Boolean,
        default: true,
    },
})

const linkClasses = computed(() => {
    const base = 'flex items-center gap-1 transition-all duration-200 group focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900 rounded-xl'

    if (props.isDesktop) {
        // Desktop sidebar mode
        const desktopBase = 'flex-row w-full px-3 py-3'
        if (props.active) {
            return `${base} ${desktopBase} text-purple-500 bg-purple-500/10 border border-purple-500/30`
        }
        return `${base} ${desktopBase} text-gray-500 hover:text-gray-300 hover:bg-gray-800 border border-transparent`
    }

    // Mobile bottom nav mode
    const mobileBase = 'flex-col'
    if (props.active) {
        return `${base} ${mobileBase} text-purple-500 scale-105`
    }
    return `${base} ${mobileBase} text-gray-500 hover:text-gray-300`
})

const iconClasses = computed(() => {
    return 'w-6 h-6 shrink-0 transition-transform group-hover:scale-110'
})

const labelClasses = computed(() => {
    const base = 'text-2xs font-bold tracking-wider uppercase'
    // Di desktop sidebar yang sedang di-collapse, sembunyikan label
    if (props.isDesktop && !props.sidebarOpen) {
        return `${base} hidden`
    }
    return base
})
</script>

<template>
    <Link
        :href="href"
        :class="linkClasses"
        :aria-current="active ? 'page' : undefined"
        :aria-label="label"
    >
        <!-- Icon slot — wrapper div meneruskan ukuran ke SVG di dalamnya -->
        <span :class="iconClasses" aria-hidden="true">
            <slot name="icon" />
        </span>
        <!--
            Catatan: SVG yang dipass ke slot #icon HARUS berukuran w-full h-full
            atau mengikuti ukuran parent. Contoh di BottomNav sudah tidak menggunakan
            v-html — SVG dirender langsung sebagai node Vue yang aman.
        -->

        <!-- Label -->
        <span :class="labelClasses">{{ label }}</span>
    </Link>
</template>
