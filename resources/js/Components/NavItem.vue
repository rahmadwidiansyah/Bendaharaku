<script setup>
/**
 * NavItem.vue
 *
 * Komponen navigasi reusable yang mendukung dua mode tampilan:
 *   - mobile: icon + label vertikal (BottomNav) dengan animasi Material 3
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
    const base = 'flex items-center gap-1 transition-all duration-300 group focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-brand)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--color-surface-base)] rounded-xl'

    if (props.isDesktop) {
        const desktopBase = 'flex-row w-full px-3 py-2.5'
        if (props.active) {
            return `${base} ${desktopBase} text-purple-400 bg-purple-500/10 border border-purple-500/20`
        }
        return `${base} ${desktopBase} text-gray-500 hover:text-gray-300 hover:bg-white/5 border border-transparent`
    }

    // Mobile: centered and full-height link
    return `${base} flex-col items-center justify-center flex-1 h-12 relative`
})

const iconWrapperClasses = computed(() => {
    if (props.isDesktop) return 'w-5 h-5 shrink-0'
    // Mobile active state: scale up and color; ikon digeser naik agar
    // tidak berdempet dengan label yang kini selalu tampil
    if (props.active) {
        return 'w-6 h-6 p-0.5 rounded-lg bg-purple-500/10 shrink-0 transform scale-110 text-purple-400 transition-all duration-300'
    }
    return 'w-6 h-6 shrink-0 transform -translate-y-1 text-gray-500 transition-all duration-300'
})

const labelClasses = computed(() => {
    if (props.isDesktop) {
        if (!props.sidebarOpen) return 'hidden'
        return 'text-2xs font-bold tracking-wider uppercase'
    }
    // Mobile: label selalu tampil di bawah ikon (paritas M3 navbar);
    // 11px — sebelumnya 9px + tersembunyi saat nonaktif (hasil audit mobile)
    return 'text-[11px] font-bold tracking-wider uppercase leading-none transition-all duration-300'
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
        <!-- Icon wrapper with lift-up animation on mobile active -->
        <span
            :class="[
                iconWrapperClasses,
                !isDesktop && active ? '-translate-y-1.5' : 'translate-y-0'
            ]"
            class="transition-all duration-300"
            aria-hidden="true"
        >
            <slot name="icon" />
        </span>

        <!-- Label selalu tampil (warna/weight berubah saat aktif) -->
        <span
            v-if="!isDesktop"
            :class="[
                labelClasses,
                active ? 'text-purple-400' : 'text-gray-500'
            ]"
            class="absolute bottom-0.5"
        >
            {{ label }}
        </span>
        <span v-else :class="labelClasses">{{ label }}</span>
    </Link>
</template>
