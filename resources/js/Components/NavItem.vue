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
        const desktopBase = 'flex-row w-full px-3 py-2.5'
        if (props.active) {
            return `${base} ${desktopBase} text-purple-400 bg-purple-500/10 border border-purple-500/20`
        }
        return `${base} ${desktopBase} text-gray-500 hover:text-gray-300 hover:bg-white/5 border border-transparent`
    }

    // Mobile: responsive layout
    // xs-sm: center only icon, expanded on sm+
    const mobileBase = 'flex-col items-center justify-center flex-1 py-1.5 sm:px-1 sm:py-1.5 px-0.5'
    if (props.active) {
        return `${base} ${mobileBase} text-purple-400`
    }
    return `${base} ${mobileBase} text-gray-500 hover:text-gray-400`
})

const iconWrapperClasses = computed(() => {
    if (props.isDesktop) return 'w-5 h-5 shrink-0'
    // Mobile: icon dengan bubble kecil saat aktif, konsisten ukurannya
    if (props.active) {
        return 'w-6 h-6 p-1 rounded-lg bg-purple-500/15 shrink-0'
    }
    return 'w-6 h-6 shrink-0'
})

const iconClasses = computed(() => {
    if (props.isDesktop) return 'w-5 h-5 shrink-0 transition-transform group-hover:scale-110'
    return 'w-full h-full transition-transform group-hover:scale-110'
})

const labelClasses = computed(() => {
    if (props.isDesktop) {
        // Sidebar collapsed → sembunyikan
        if (!props.sidebarOpen) return 'hidden'
        return 'text-2xs font-bold tracking-wider uppercase'
    }
    // Mobile: label selalu tampil agar nav item jelas (penting untuk UX HP standar 360-414px)
    return 'text-[10px] font-bold tracking-wide uppercase mt-0.5 leading-none whitespace-nowrap'
})
</script>

<template>
    <Link
        :href="href"
        :class="linkClasses"
        :aria-current="active ? 'page' : undefined"
        :aria-label="label"
        :title="label"
    >
        <!-- Icon wrapper — ukuran konsisten, bubble aktif di mobile -->
        <span :class="iconWrapperClasses" aria-hidden="true">
            <slot name="icon" />
        </span>

        <!-- Label — selalu tampil di mobile maupun desktop -->
        <span :class="labelClasses">{{ label }}</span>
    </Link>
</template>
