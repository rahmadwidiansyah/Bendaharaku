<script setup>
/**
 * M3BotAvatar — avatar M3 (paritas BotAvatar Default).
 *
 * Fallback cascade identik: foto → DiceBear (variant user) → initials.
 * Warna memakai role M3 (bukan token finansial Default).
 */
import { ref, computed } from 'vue'
import { useBotAvatar } from '@/Composables/useBotAvatar.js'

const props = defineProps({
    src:     { type: String,  default: null },
    name:    { type: String,  default: '' },
    /** 'sm' = 1.5rem (bubble), 'md' = 2rem (header), 'lg' = 4.5rem (empty state) */
    size:    { type: String,  default: 'sm' },
    /** 'bot' | 'user' */
    variant: { type: String,  default: 'bot' },
    online:  { type: Boolean, default: false },
    shape:   { type: String,  default: 'circle' },
})

const { avatarFailed, initials, onAvatarError } = useBotAvatar(() => props.name)

const diceAvatarFailed = ref(false)

const diceAvatarUrl = computed(() => {
    if (props.variant !== 'user') return null
    const seed = encodeURIComponent((props.name || 'user').trim())
    return `https://api.dicebear.com/9.x/thumbs/svg?seed=${seed}&backgroundType=gradientLinear&backgroundColor=6b21a8,7c3aed&shapeColor=ffffff&eyes=variant1W12,variant2W14,variant3W12,variant4W10&mouth=variant1,variant2,variant3,variant4`
})

const showDiceAvatar = computed(() =>
    props.variant === 'user' &&
    (!props.src || avatarFailed.value) &&
    !diceAvatarFailed.value &&
    diceAvatarUrl.value !== null
)

const showPhoto = computed(() => props.src && !avatarFailed.value)
</script>

<template>
    <div class="m3-avatar" :class="[`m3-avatar--${size}`]">
        <div class="m3-avatar__box" :class="[`m3-avatar__box--${variant}`, shape === 'rounded' ? 'm3-avatar__box--rounded' : '']" :aria-label="name">
            <img
                v-if="showPhoto"
                :src="src"
                :alt="name"
                class="m3-avatar__img"
                loading="lazy"
                decoding="async"
                @error="onAvatarError"
            />
            <img
                v-else-if="showDiceAvatar"
                :src="diceAvatarUrl"
                :alt="name"
                class="m3-avatar__img"
                loading="lazy"
                decoding="async"
                @error="diceAvatarFailed = true"
            />
            <span v-else class="m3-avatar__initials" :class="`m3-avatar__initials--${size}`">{{ initials }}</span>
        </div>

        <span v-if="online" class="m3-avatar__dot" :class="`m3-avatar__dot--${size}`" aria-hidden="true" />
    </div>
</template>

<style scoped>
.m3-avatar {
    position: relative;
    display: inline-flex;
    flex-shrink: 0;
}

.m3-avatar--sm { width: 1.5rem; height: 1.5rem; }
.m3-avatar--md { width: 2rem; height: 2rem; }
.m3-avatar--lg { width: 4.5rem; height: 4.5rem; }

.m3-avatar__box {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: var(--m3-radius-full);
    border: 1px solid var(--m3-color-outline-variant);
    background: var(--m3-color-surface-container-highest);
    color: var(--m3-color-on-surface-variant);
}

.m3-avatar__box--bot {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-avatar__box--rounded {
    border-radius: var(--m3-radius-lg);
}

.m3-avatar__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.m3-avatar__initials {
    user-select: none;
    font-weight: var(--m3-type-emphasized-label-large-weight);
    line-height: 1;
}

.m3-avatar__initials--sm { font-size: var(--m3-type-label-small-size); }
.m3-avatar__initials--md { font-size: var(--m3-type-label-large-size); }
.m3-avatar__initials--lg { font-size: var(--m3-type-headline-small-size); }

.m3-avatar__dot {
    position: absolute;
    bottom: -0.125rem;
    right: -0.125rem;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-primary);
    border: 2px solid var(--m3-color-surface);
}

.m3-avatar__dot--sm { width: 0.5rem; height: 0.5rem; border-width: 1px; }
.m3-avatar__dot--md { width: 0.625rem; height: 0.625rem; }
.m3-avatar__dot--lg { width: 0.75rem; height: 0.75rem; }
</style>
