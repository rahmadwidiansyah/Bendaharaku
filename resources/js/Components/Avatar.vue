<script setup>
/**
 * Avatar.vue
 *
 * Komponen avatar user yang reaktif dengan fallback otomatis.
 * Menggantikan pola DOM manipulation manual (document.createElement)
 * yang sebelumnya ada di Dashboard.vue.
 *
 * Urutan fallback:
 *   1. Gambar dari src (avatar URL)
 *   2. Inisial nama (jika gambar gagal load)
 *
 * Props:
 *   src     — URL gambar avatar (opsional)
 *   name    — Nama user untuk fallback inisial & alt text (wajib)
 *   size    — Ukuran avatar: 'sm' | 'md' | 'lg' (default: 'md')
 *   ring    — Tampilkan ring border brand (default: false)
 *
 * Emits:
 *   error   — Dipancarkan saat gambar gagal load
 */

import { ref, computed } from 'vue'

const props = defineProps({
    src: {
        type: String,
        default: null,
    },
    name: {
        type: String,
        required: true,
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    ring: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['error'])

const imgFailed = ref(false)

const onImgError = () => {
    imgFailed.value = true
    emit('error')
}

// Ambil maksimal 2 inisial dari nama
const initials = computed(() => {
    return props.name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((word) => word.charAt(0).toUpperCase())
        .join('')
})

const sizeClasses = computed(() => ({
    sm: 'w-8 h-8 text-xs',
    md: 'w-10 h-10 text-sm',
    lg: 'w-12 h-12 text-base',
}[props.size]))

const ringClasses = computed(() =>
    props.ring
        ? 'border-2 border-purple-500 p-0.5'
        : ''
)

const wrapperClasses = computed(() =>
    `relative rounded-full shrink-0 bg-gray-900 overflow-hidden ${sizeClasses.value} ${ringClasses.value}`
)
</script>

<template>
    <div :class="wrapperClasses" :aria-label="name" role="img">
        <!-- Gambar avatar -->
        <img
            v-if="src && !imgFailed"
            :src="src"
            :alt="name"
            class="w-full h-full object-cover rounded-full"
            @error="onImgError"
        />

        <!-- Fallback inisial — tampil jika tidak ada src atau gambar gagal load -->
        <span
            v-else
            class="absolute inset-0 flex items-center justify-center rounded-full bg-purple-900/80 text-white font-black select-none"
            aria-hidden="true"
        >
            {{ initials }}
        </span>
    </div>
</template>
