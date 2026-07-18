<script setup>
/**
 * BotAvatar.vue
 *
 * Avatar universal dengan fallback cascade:
 *   1. Foto profil (src prop)
 *   2. DiceBear generated avatar (variant='user' only)
 *   3. Initials text
 *
 * DiceBear API: https://api.dicebear.com/9.x/{style}/svg?seed={seed}
 * Style 'initials' menghasilkan avatar berwarna konsisten berdasarkan nama.
 * Style 'avataaars' menghasilkan ilustrasi karakter.
 * Kita pakai 'thumbs' — clean, modern, konsisten untuk user.
 *
 * Usage:
 *   <BotAvatar :src="botAvatar" :name="botName" size="sm" variant="bot" />
 *   <BotAvatar :src="userAvatar" :name="userName" size="sm" variant="user" />
 */

import { ref, computed } from 'vue'
import { useBotAvatar }  from '@/Composables/useBotAvatar.js'

const props = defineProps({
    src:     { type: String,  default: null },
    name:    { type: String,  default: '' },
    /** 'sm' = 24px (bubble), 'md' = 32px (header), 'lg' = 72px (empty state) */
    size:    { type: String,  default: 'sm' },
    /** 'bot' — purple initials (tidak pakai DiceBear) | 'user' — DiceBear fallback */
    variant: { type: String,  default: 'bot' },
    /** Tampilkan online indicator dot */
    online:  { type: Boolean, default: false },
    /** Shape: 'circle' | 'rounded' */
    shape:   { type: String,  default: 'circle' },
})

const { avatarFailed, initials, onAvatarError } = useBotAvatar(() => props.name)

// DiceBear fallback (hanya untuk variant=user, foto profil gagal/tidak ada)
const diceAvatarFailed = ref(false)

/**
 * DiceBear URL dengan style 'thumbs' — avatarnya clean, bulat, modern.
 * seed = nama user → konsisten per orang.
 * backgroundColor = warna sesuai design system.
 */
const diceAvatarUrl = computed(() => {
    if (props.variant !== 'user') return null
    const seed = encodeURIComponent((props.name || 'user').trim())
    // Style 'thumbs' — mirip Notion avatar, clean, no face complexity
    return `https://api.dicebear.com/9.x/thumbs/svg?seed=${seed}&backgroundType=gradientLinear&backgroundColor=6b21a8,7c3aed&shapeColor=ffffff&eyes=variant1W12,variant2W14,variant3W12,variant4W10&mouth=variant1,variant2,variant3,variant4`
})

// Apakah tampilkan DiceBear: variant user, foto tidak ada/gagal, DiceBear belum gagal
const showDiceAvatar = computed(() =>
    props.variant === 'user' &&
    (!props.src || avatarFailed.value) &&
    !diceAvatarFailed.value &&
    diceAvatarUrl.value !== null
)

// Apakah tampilkan foto asli
const showPhoto = computed(() =>
    props.src && !avatarFailed.value
)

// ── Style maps ────────────────────────────────────────────────────
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
    user: 'text-gray-300',
}

const bgColor = {
    bot:  'bg-gray-800',
    user: 'bg-gray-700',
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
                'overflow-hidden border border-white/10 flex items-center justify-center',
                sizeClass[size] ?? sizeClass.sm,
                shapeClass[shape] ?? shapeClass.circle,
                bgColor[variant] ?? bgColor.bot,
            ]"
            :aria-label="name"
        >
            <!-- 1. Foto profil -->
            <img
                v-if="showPhoto"
                :src="src"
                :alt="name"
                class="w-full h-full object-cover"
                @error="onAvatarError"
                loading="lazy"
                decoding="async"
            />

            <!-- 2. DiceBear (hanya user variant) -->
            <img
                v-else-if="showDiceAvatar"
                :src="diceAvatarUrl"
                :alt="name"
                class="w-full h-full object-cover"
                @error="diceAvatarFailed = true"
                loading="lazy"
                decoding="async"
            />

            <!-- 3. Initials fallback terakhir -->
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
