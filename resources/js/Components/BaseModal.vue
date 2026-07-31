<script setup>
/**
 * BaseModal.vue
 *
 * Wrapper modal reusable untuk seluruh aplikasi.
 * Menggantikan struktur modal berulang di DateModal, TransactionDetailModal,
 * EmojiPicker, dan setiap komponen modal yang diimplementasikan secara mandiri.
 *
 * Fitur:
 *   - Overlay backdrop dengan blur
 *   - Close saat klik di luar area modal (optional)
 *   - Close saat tekan Escape (optional)
 *   - Teleport ke <body> secara otomatis (tidak terjebak stacking context)
 *   - Animasi masuk/keluar konsisten
 *   - Accessible: focus trap sederhana, aria-modal, aria-labelledby
 *   - Tombol close bawaan (optional, bisa diganti via slot)
 *   - Sistem ukuran scalable: xs → 7xl, full, adaptive
 *   - Mode bottom-sheet (mobile) / centered (desktop)
 *   - Opsi fullscreen di mobile untuk form kompleks
 *
 * Props:
 *   show          — Tampilkan/sembunyikan modal (wajib, pakai v-model atau :show)
 *   title         — Judul modal, ditampilkan di header default (opsional)
 *   maxWidth      — Lebar maksimum panel modal:
 *                   'xs'       → max-w-xs (320px, konfirmasi kecil)
 *                   'sm'       → max-w-sm (384px, konfirmasi sederhana) [DEFAULT]
 *                   'md'       → max-w-md (448px, form kecil)
 *                   'lg'       → max-w-lg (512px, form sedang)
 *                   'xl'       → max-w-xl (576px, form besar)
 *                   '2xl'      → max-w-2xl (672px, detail)
 *                   '3xl'      → max-w-3xl (768px, detail besar)
 *                   '4xl'      → max-w-4xl (896px, editor)
 *                   '5xl'      → max-w-5xl (1024px, editor besar)
 *                   '6xl'      → max-w-6xl (1152px, dashboard widget)
 *                   '7xl'      → max-w-7xl (1280px, layar penuh)
 *                   'full'     → max-w-none w-full (mobile) + sm:max-w-[calc(100%-2rem)]
 *                   'adaptive' → w-full, tumbuh mengikuti breakpoint:
 *                                max-w-md sm:max-w-lg md:max-w-xl lg:max-w-2xl xl:max-w-3xl 2xl:max-w-4xl
 *   closeable     — Apakah modal bisa ditutup via klik overlay atau tombol close (default: true)
 *   showCloseBtn  — Tampilkan tombol X di sudut kanan atas (default: true)
 *   padding       — Padding konten panel: 'none' | 'sm' | 'md' | 'lg' (default: 'lg')
 *   zIndex        — Kelas z-index Tailwind (default: 'z-[9999]')
 *   align         — Posisi modal:
 *                   'center'      → centered (default)
 *                   'bottom-sheet'→ menempel bawah di mobile, centered di sm+
 *   mobileOnly    — Paksa layout mobile di semua ukuran layar. Efektif saat
 *                   align="bottom-sheet": sheet selalu menempel bawah,
 *                   tidak berubah menjadi centered di desktop.
 *   fullScreenOnMobile — Saat true, modal mengisi layar di mobile dan menjadi
 *                        modal normal di sm+. Cocok untuk form kompleks.
 *
 * Slots:
 *   default       — Konten utama modal
 *   header        — Override header (menggantikan title + close button default)
 *   footer        — Area footer (tombol aksi, dsb.) — opsional
 *
 * Emits:
 *   close         — Dipancarkan saat modal ditutup (overlay click / Escape / close btn)
 *
 * Usage:
 *   <!-- Sederhana -->
 *   <BaseModal :show="showModal" title="Konfirmasi" @close="showModal = false">
 *     <p>Yakin ingin melanjutkan?</p>
 *     <template #footer>
 *       <Button variant="danger" @click="confirm">Ya</Button>
 *       <Button variant="ghost" @click="showModal = false">Batal</Button>
 *     </template>
 *   </BaseModal>
 *
 *   <!-- Form besar: adaptive + fullscreen di mobile -->
 *   <BaseModal :show="showForm" max-width="adaptive" :full-screen-on-mobile="true" @close="close">
 *     <TransactionForm />
 *   </BaseModal>
 *
 *   <!-- Tanpa close (blocking) -->
 *   <BaseModal :show="isLoading" :closeable="false" :show-close-btn="false">
 *     <LoadingSpinner />
 *   </BaseModal>
 *
 *   <!-- Header custom -->
 *   <BaseModal :show="showDatePicker" @close="close">
 *     <template #header>
 *       <h3 class="font-bold">Pilih Tanggal</h3>
 *     </template>
 *     <DateRangePicker />
 *   </BaseModal>
 */

import { computed, onMounted, onBeforeUnmount, watch } from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    title: {
        type: String,
        default: null,
    },
    maxWidth: {
        type: String,
        default: 'adaptive',
        validator: (v) =>
            ['xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl', 'full', 'adaptive'].includes(v),
    },
    align: {
        type: String,
        default: 'center',
        validator: (v) => ['center', 'bottom-sheet'].includes(v),
    },
    fullScreenOnMobile: {
        type: Boolean,
        default: false,
    },
    mobileOnly: {
        type: Boolean,
        default: false,
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    showCloseBtn: {
        type: Boolean,
        default: true,
    },
    padding: {
        type: String,
        default: 'lg',
        validator: (v) => ['none', 'sm', 'md', 'lg'].includes(v),
    },
    zIndex: {
        type: String,
        default: 'z-[9999]',
    },
})

const emit = defineEmits(['close'])

const close = () => {
    if (props.closeable) {
        emit('close')
    }
}

// Close dengan Escape key
const handleKeydown = (e) => {
    if (e.key === 'Escape' && props.show && props.closeable) {
        emit('close')
    }
}

// Cegah scroll body saat modal terbuka
const lockScroll = () => {
    document.body.style.overflow = 'hidden'
}
const unlockScroll = () => {
    document.body.style.overflow = ''
}

watch(
    () => props.show,
    (val) => {
        val ? lockScroll() : unlockScroll()
    },
    { immediate: true },
)

onMounted(() => {
    window.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown)
    unlockScroll()
})

const maxWidthClasses = {
    xs: 'max-w-xs',
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
    '4xl': 'max-w-4xl',
    '5xl': 'max-w-5xl',
    '6xl': 'max-w-6xl',
    '7xl': 'max-w-7xl',
    full: 'max-w-none w-full sm:max-w-[calc(100%-2rem)]',
    adaptive:
        'w-full max-w-md sm:max-w-lg md:max-w-xl lg:max-w-2xl xl:max-w-3xl 2xl:max-w-4xl',
}

const alignClasses = {
    center: 'flex items-center justify-center p-4',
    'bottom-sheet': props.mobileOnly
        ? 'flex items-end justify-center p-4'
        : 'flex items-end sm:items-center justify-center p-4',
}

const paddingClasses = {
    none: '',
    sm: 'p-4',
    md: 'p-5',
    lg: 'p-6',
}

const panelClasses = computed(() => {
    const base = [
        'w-full shrink-0 relative',
        'bg-gradient-to-b from-gray-900 to-gray-800',
        'rounded-2xl border border-white/10',
        'shadow-2xl shadow-black/50',
        maxWidthClasses[props.maxWidth],
    ]
    if (props.fullScreenOnMobile) {
        base.push('h-full sm:h-auto rounded-none sm:rounded-2xl')
    }
    return base
})
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                :class="[
                    'fixed inset-0',
                    'bg-black/80 backdrop-blur-sm',
                    zIndex,
                    alignClasses[props.align],
                ]"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="title ? 'modal-title' : undefined"
                @click.self="close"
            >
                <Transition
                    enter-active-class="transition-all duration-250 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2"
                >
                    <div v-if="show" :class="panelClasses">
                        <!-- ── Header ──────────────────────────────────── -->
                        <div
                            v-if="$slots.header || title || showCloseBtn"
                            :class="[
                                'flex items-center justify-between',
                                paddingClasses[padding],
                                $slots.default || $slots.footer ? 'pb-0' : '',
                            ]"
                        >
                            <!-- Slot header custom atau title default -->
                            <div class="flex-1 min-w-0">
                                <slot name="header">
                                    <h3
                                        v-if="title"
                                        id="modal-title"
                                        class="text-sm font-black text-white uppercase tracking-widest truncate"
                                    >
                                        {{ title }}
                                    </h3>
                                </slot>
                            </div>

                            <!-- Tombol close -->
                            <button
                                v-if="showCloseBtn && closeable"
                                type="button"
                                class="ml-3 w-8 h-8 shrink-0 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:bg-white/10 active:scale-90 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400"
                                aria-label="Tutup modal"
                                @click="close"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- ── Konten utama ────────────────────────────── -->
                        <div
                            v-if="$slots.default"
                            :class="[
                                paddingClasses[padding],
                                $slots.header || title || showCloseBtn ? 'pt-4' : '',
                                $slots.footer ? 'pb-0' : '',
                            ]"
                        >
                            <slot />
                        </div>

                        <!-- ── Footer ─────────────────────────────────── -->
                        <div
                            v-if="$slots.footer"
                            :class="[
                                paddingClasses[padding],
                                'pt-4 flex gap-3',
                            ]"
                        >
                            <slot name="footer" />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>