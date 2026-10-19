<script setup>
/**
 * M3ChatHeader — header chat M3 (paritas ChatHeader Default).
 * Glass level 3 sesuai design.md §2.3 (header boleh glassmorphism).
 * Status mengetik memakai role primary (bukan warna finansial).
 */
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import M3BotAvatar from './M3BotAvatar.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'

const props = defineProps({
    botName:   { type: String,  default: 'Ken-Chan' },
    botAvatar: { type: String,  default: null },
    isTyping:  { type: Boolean, default: false },
    status:    { type: String,  default: 'online' },
})

const { t } = useI18n()

const statusText = computed(() =>
    props.isTyping ? t('chat.typing') : t('chat.assistant')
)

function handleBack() {
    // Fresh GET /dashboard agar Dashboard tidak stale (sama dengan Default).
    try {
        router.visit(route('dashboard'), { preserveState: false, preserveScroll: false })
    } catch {
        window.location.href = route('dashboard')
    }
}
</script>

<template>
    <header class="m3-chat-header">
        <M3IconButton :aria-label="t('btn.back')" @click="handleBack">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </M3IconButton>

        <!-- Avatar + nama → Profil Bot (seluruh area dapat diklik) -->
        <Link
            :href="route('settings.ai.bot')"
            class="m3-chat-header__bot"
            :aria-label="t('chatBot.title')"
        >
            <M3BotAvatar :src="botAvatar" :name="botName" size="md" variant="bot" shape="rounded" :online="true" />

            <div class="m3-chat-header__info">
                <h1 class="m3-chat-header__name">{{ botName }}</h1>
                <p class="m3-chat-header__status" :class="{ 'm3-chat-header__status--typing': isTyping }">{{ statusText }}</p>
            </div>
        </Link>

        <M3IconButton
            :aria-label="t('settings.chat.hub.title')"
            :href="route('settings.chat.index')"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </M3IconButton>
    </header>
</template>

<style scoped>
.m3-chat-header {
    position: sticky;
    top: 0;
    z-index: 20;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-height: 3.5rem;
    padding: var(--m3-space-1) var(--m3-space-2);
    padding-top: calc(var(--m3-space-1) + env(safe-area-inset-top, 0px));
    background: color-mix(in srgb, var(--m3-color-surface-container) 92%, transparent);
    backdrop-filter: blur(var(--m3-blur-glass));
    -webkit-backdrop-filter: blur(var(--m3-blur-glass));
    border-bottom: 1px solid var(--m3-color-outline-variant);
}

.m3-chat-header__bot {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    flex: 1;
    min-width: 0;
    text-decoration: none;
    border-radius: var(--m3-radius-full);
}

.m3-chat-header__bot:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-chat-header__info {
    flex: 1;
    min-width: 0;
}

.m3-chat-header__name {
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    letter-spacing: var(--m3-type-title-medium-tracking);
    color: var(--m3-color-on-surface);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-chat-header__status {
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: color var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-chat-header__status--typing {
    color: var(--m3-color-primary);
}

@media (min-width: 1024px) {
    .m3-chat-header {
        min-height: 4rem;
        padding-left: var(--m3-space-4);
        padding-right: var(--m3-space-4);
    }
}
</style>
