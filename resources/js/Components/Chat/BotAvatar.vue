<script setup>
/**
 * BotAvatar.vue
 *
 * Avatar kecil untuk bubble bot / typing indicator.
 * Fallback ke initials jika gambar gagal load.
 *
 * Usage:
 *   <BotAvatar :src="botAvatar" :name="botName" size="sm" />
 *   <BotAvatar :src="userAvatar" :name="userName" size="sm" variant="user" />
 */

import { useBotAvatar } from '@/Composables/useBotAvatar.js'

const props = defineProps({
    src:     { type: String,  default: null },
    name:    { type: String,  default: '' },
    /** 'sm' = 24px (bubble), 'md' = 32px (header), 'lg' = 72px (empty state) */
    size:    { type: String,  default: 'sm' },
    /** 'bot' (purple initials) | 'user' (gray initials) */
    variant: { type: String,  default: 'bot' },
    /** Tampilkan online indicator dot */
    online:  { type: Boolean, default: false },
    /** Shape: 'circle' | 'rounded' */
    shape:   { type: String,  default: 'circle' },
})

const { avatarFailed, initials, onAvatarError } = useBotAvatar(() => props.name)

const sizeClass = {
    sm: 'w-6 h-6',
    md: 'w-8 h-8',
    lg: 'w-[72px] h-[72px]',
}

const shapeClass = {
    circle:  'rounded-full',
    rounded: 'rounded-xl',
}

const initialsSize = {
    sm: 'text-2xs font-black',
    md: 'text-xs font-black',
    lg: 'text-2xl font-black',
}

const initialsColor = {
    bot:  'text-purple-400',
    user: 'text-gray-400',
}

const onlineDotSize = {
    sm: 'w-2 h-2 border',
    md: 'w-2.5 h-2.5 border-2',
    lg: 'w-3 h-3 border-2',
}
</script>

<template>
    <div class="relative shrink-0 inline-flex">
        <div
            :class="[
                'overflow-hidden bg-gray-800 border border-white/10 flex items-center justify-center',
                sizeClass[size] ?? sizeClass.sm,
                shapeClass[shape] ?? shapeClass.circle,
            ]"
            :aria-label="name"
        >
            <img
                v-if="src && !avatarFailed"
                :src="src"
                :alt="name"
                class="w-full h-full object-cover"
                @error="onAvatarError"
            />
            <span
                v-else
                :class="[
                    'select-none',
                    initialsSize[size] ?? initialsSize.sm,
                    initialsColor[variant] ?? initialsColor.bot,
                ]"
            >{{ initials }}</span>
        </div>

        <!-- Online dot -->
        <span
            v-if="online"
            :class="[
                'absolute -bottom-0.5 -right-0.5 rounded-full bg-emerald-500 border-gray-950',
                onlineDotSize[size] ?? onlineDotSize.sm,
            ]"
            aria-hidden="true"
        ></span>
    </div>
</template>
