<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    botName:   { type: String, default: 'Ken-Chan' },
    botAvatar: { type: String, default: null },
    isTyping:  { type: Boolean, default: false },
    status:    { type: String, default: 'online' }, // 'online' | 'offline' | 'typing'
})

const avatarFailed = ref(false)

const initials = computed(() =>
    props.botName.trim().split(/\s+/).slice(0, 2).map((w) => w[0].toUpperCase()).join('')
)

const statusText = computed(() => {
    if (props.isTyping) return 'Sedang mengetik...'
    return 'AI Financial Assistant'
})
</script>

<template>
    <header class="sticky top-0 z-20 flex items-center gap-3 px-4 py-3 bg-gray-900/95 backdrop-blur-xl border-b border-white/8">

        <!-- Back button (mobile) -->
        <Link
            :href="route('settings.index')"
            class="lg:hidden w-8 h-8 shrink-0 flex items-center justify-center rounded-xl text-gray-400 hover:text-white hover:bg-white/8 transition-colors -ml-1"
            aria-label="Kembali"
        >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </Link>

        <!-- Bot avatar -->
        <div class="relative shrink-0">
            <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-800 border border-white/10 flex items-center justify-center">
                <img v-if="botAvatar && !avatarFailed" :src="botAvatar" :alt="botName" class="w-full h-full object-cover" @error="avatarFailed = true" />
                <span v-else class="text-xs font-black text-purple-400 select-none">{{ initials }}</span>
            </div>
            <!-- Online indicator -->
            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-gray-900" aria-hidden="true"></span>
        </div>

        <!-- Bot info -->
        <div class="flex-1 min-w-0">
            <h1 class="text-sm font-bold text-white leading-tight truncate">{{ botName }}</h1>
            <p :class="[
                'text-2xs leading-tight transition-colors duration-200',
                isTyping ? 'text-emerald-400' : 'text-gray-500'
            ]">
                {{ statusText }}
            </p>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-1 shrink-0">
            <!-- Bot profile settings -->
            <Link
                :href="route('settings.chat.bot-profile')"
                class="w-8 h-8 flex items-center justify-center rounded-xl text-gray-500 hover:text-white hover:bg-white/8 transition-colors"
                aria-label="Pengaturan Bot"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </Link>
        </div>
    </header>
</template>
