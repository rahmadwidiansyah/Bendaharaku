<script setup>
/**
 * NotificationButton.vue
 *
 * Tombol ikon notifikasi dengan badge merah untuk jumlah notif belum dibaca.
 * Saat ini belum ada sistem notifikasi backend → badge hanya ditampilkan
 * jika prop `count` diberikan (future-ready).
 *
 * Props:
 *   count — Jumlah notifikasi belum dibaca (0 = tidak tampilkan badge)
 */

const props = defineProps({
    count: {
        type: Number,
        default: 0,
    },
})

const emit = defineEmits(['click'])

const badgeLabel = (n) => n > 99 ? '99+' : String(n)
</script>

<template>
    <button
        type="button"
        class="relative flex items-center justify-center w-9 h-9 rounded-2xl bg-white/5 hover:bg-white/10 active:scale-90 transition-all duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900"
        :aria-label="count > 0 ? `Notifikasi, ${count} belum dibaca` : 'Notifikasi'"
        @click="emit('click', $event)"
    >
        <!-- Bell icon -->
        <svg
            class="w-[18px] h-[18px] text-gray-300"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
            />
        </svg>

        <!-- Badge merah -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 scale-50"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-50"
        >
            <span
                v-if="count > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-0.5 rounded-full bg-red-500 text-white text-[9px] font-black flex items-center justify-center leading-none border border-gray-900"
                aria-hidden="true"
            >
                {{ badgeLabel(count) }}
            </span>
        </Transition>
    </button>
</template>
