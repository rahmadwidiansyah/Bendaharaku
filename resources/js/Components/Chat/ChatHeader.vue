<script setup>
/**
 * ChatHeader.vue
 *
 * Sticky header halaman Chat.
 * Berisi: back button (mobile), avatar bot, nama + status, settings link.
 * Pakai BotAvatar untuk konsistensi visual.
 */

import { computed } from 'vue'
import { Link }     from '@inertiajs/vue3'
import BotAvatar    from '@/Components/Chat/BotAvatar.vue'

const props = defineProps({
    botName:   { type: String,  default: 'Ken-Chan' },
    botAvatar: { type: String,  default: null },
    isTyping:  { type: Boolean, default: false },
    status:    { type: String,  default: 'online' },
})

const statusText = computed(() =>
    props.isTyping ? 'Sedang mengetik...' : 'AI Financial Assistant'
)
</script>

<template>
    <!--
        sticky top-0: tetap di atas saat scroll
        padding-top via style: hormati safe area (notch, Dynamic Island, Android cutout)
        min-height 56px setelah safe area
    -->
    <header
        class="sticky top-0 z-20 bg-gray-950/95 backdrop-blur-xl border-b border-white/6"
        style="padding-top: env(safe-area-inset-top, 0px);"
    >
    <div class="flex items-center gap-3 px-4 h-14">

        <!-- Back button (mobile only) -->
        <Link
            :href="route('dashboard')"
            class="lg:hidden w-8 h-8 shrink-0 flex items-center justify-center rounded-xl text-gray-500 hover:text-white hover:bg-white/6 transition-colors"
            aria-label="Kembali ke Dashboard"
        >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </Link>

        <!-- Bot avatar + online dot -->
        <BotAvatar
            :src="botAvatar"
            :name="botName"
            size="md"
            variant="bot"
            shape="rounded"
            :online="true"
        />

        <!-- Bot info -->
        <div class="flex-1 min-w-0">
            <h1 class="text-sm font-bold text-white leading-tight truncate">{{ botName }}</h1>
            <p :class="[
                'text-2xs leading-tight transition-colors duration-200 truncate',
                isTyping ? 'text-emerald-400' : 'text-gray-600'
            ]">{{ statusText }}</p>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-0.5 shrink-0">
            <Link
                :href="route('settings.ai.bot')"
                class="w-8 h-8 flex items-center justify-center rounded-xl text-gray-600 hover:text-white hover:bg-white/6 transition-colors"
                aria-label="Pengaturan Bot"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </Link>
        </div>
    </div>
    </header>
</template>
