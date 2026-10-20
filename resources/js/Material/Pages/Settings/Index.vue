<script setup>
/**
 * Material/Pages/Settings/Index.vue — hub pengaturan (tema M3).
 *
 * Enam seksi mengikuti struktur Settings Default yang dikelompokkan ulang:
 * Akun, Aplikasi, Keuangan, AI, Privasi, Sistem. Semua tujuan memakai route
 * existing; kartu user di atas menjadi pintu cepat ke Profil.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import M3SettingsSection from '@/Material/Components/Settings/M3SettingsSection.vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const page = usePage()
const user = computed(() => page.props.auth?.user ?? {})

const isUrl = (url) => url && (url.startsWith('http://') || url.startsWith('https://'))
const avatarUrl = computed(() => {
    const avatar = user.value?.avatar_url ?? user.value?.avatar
    if (!avatar) return ''
    if (isUrl(avatar)) return avatar
    return avatar.includes('/') ? `/storage/${avatar}` : ''
})
const initials = computed(() => {
    const name = user.value?.name ?? ''
    if (!name) return '?'
    return name.split(' ').map((w) => w[0]).filter(Boolean).slice(0, 2).join('').toUpperCase()
})

const sections = computed(() => [
    {
        id: 'account',
        title: t('settings.index.section.account'),
        items: [
            {
                route: 'settings.account.profile',
                label: t('settings.account.profile.title'),
                description: t('settings.account.profile.description'),
                paths: ['M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z', 'M4.5 19.5a7.5 7.5 0 0115 0'],
            },
            {
                route: 'settings.account.security',
                label: t('settings.account.security.title'),
                description: t('settings.account.security.description'),
                paths: ['M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z'],
            },
            {
                route: 'settings.account.preferences',
                label: t('settings.account.preferences.title'),
                description: t('settings.account.preferences.description'),
                paths: ['M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75'],
            },
        ],
    },
    {
        id: 'application',
        title: t('settings.application.title'),
        items: [
            {
                route: 'settings.application.appearance',
                label: t('settings.application.appearance.title'),
                description: t('settings.application.appearance.description'),
                paths: ['M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z'],
            },
            {
                route: 'settings.application.notifications',
                label: t('settings.application.notifications.title'),
                description: t('settings.application.notifications.description'),
                paths: ['M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0'],
            },
        ],
    },
    {
        id: 'finance',
        title: t('settings.index.section.finance'),
        items: [
            {
                route: 'settings.finance.logic',
                label: t('settings.finance.defaults.transaction_logic.title'),
                description: t('settings.finance.defaults.transaction_logic.description'),
                paths: ['M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z'],
            },
            {
                route: 'settings.finance.budget',
                label: t('settings.finance.budget.title'),
                description: t('settings.finance.budget.description'),
                paths: ['M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941'],
            },
        ],
    },
    {
        id: 'ai',
        title: t('settings.index.section.ai'),
        items: [
            {
                route: 'settings.ai.models',
                label: t('settings.ai.models.title'),
                description: t('settings.ai.models.description'),
                paths: ['M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-10.5h9v7.5h-9v-7.5z'],
            },
            {
                route: 'settings.ai.bot',
                label: t('settings.ai.bot.title'),
                description: t('settings.ai.bot.description'),
                paths: ['M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            },
            {
                route: 'settings.ai.memory',
                label: t('settings.ai.memory.title'),
                description: t('settings.ai.memory.description'),
                paths: ['M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125M20.25 6.375c0-2.278-3.694-4.125-8.25-4.125s-8.25 1.847-8.25 4.125m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125'],
            },
            {
                route: 'settings.ai.integrations',
                label: t('settings.ai.integrations.title'),
                description: t('settings.ai.integrations.description'),
                paths: ['M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z'],
            },
        ],
    },
    {
        id: 'privacy',
        title: t('settings.index.section.privacy'),
        items: [
            {
                route: 'settings.privacy.settings',
                label: t('settings.privacy.settings.title'),
                description: t('settings.privacy.settings.description'),
                paths: ['M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
            },
            {
                route: 'settings.privacy.data',
                label: t('settings.privacy.data.title'),
                description: t('settings.privacy.data.description'),
                paths: ['M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3'],
            },
            {
                route: 'settings.privacy.logs',
                label: t('settings.privacy.logs.title'),
                description: t('settings.privacy.logs.description'),
                paths: ['M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            },
            {
                route: 'settings.privacy.danger',
                label: t('settings.privacy.danger.title'),
                description: t('settings.privacy.danger.description'),
                paths: ['M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
                danger: true,
            },
        ],
    },
    {
        id: 'system',
        title: t('settings.system.title'),
        items: [
            {
                route: 'settings.system.about',
                label: t('settings.system.about.title'),
                description: t('settings.system.about.description'),
                paths: ['M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z'],
            },
        ],
    },
])

const chevron = 'M8.25 4.5l7.5 7.5-7.5 7.5'
const handleLogout = () => router.post(route('logout'))
</script>

<template>
    <M3Layout :title="$t('settings.title')">
        <Head :title="$t('settings.title')" />

        <M3SettingsPage :title="$t('settings.title')" :subtitle="$t('settings.subtitle')" width="list">
            <M3Card variant="outlined" class="m3-settings-user">
                <div class="m3-settings-user__avatar">
                    <img v-if="avatarUrl" :src="avatarUrl" alt="" class="m3-settings-user__avatar-img" />
                    <span v-else aria-hidden="true">{{ initials }}</span>
                </div>

                <div class="m3-settings-user__body">
                    <p class="m3-settings-user__name">{{ user.name || '—' }}</p>
                    <p class="m3-settings-user__email">{{ user.email || '—' }}</p>
                    <div v-if="user.whatsapp_number || user.telegram_id || user.google_id" class="m3-settings-user__badges">
                        <span v-if="user.whatsapp_number" class="m3-settings-user__badge">WhatsApp</span>
                        <span v-if="user.telegram_id" class="m3-settings-user__badge">Telegram</span>
                        <span v-if="user.google_id" class="m3-settings-user__badge">Google</span>
                    </div>
                </div>

                <M3IconButton
                    :href="route('settings.account.profile')"
                    :aria-label="$t('settings.account.profile.title')"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.862 4.487zm0 0L19.5 7.125" />
                    </svg>
                </M3IconButton>
            </M3Card>

            <M3SettingsSection
                v-for="section in sections"
                :key="section.id"
                :title="section.title"
            >
                <M3Card variant="outlined" padding="none" class="m3-settings-hub__card">
                    <M3ListItem
                        v-for="(item, index) in section.items"
                        :key="item.route"
                        :href="route(item.route)"
                        :divider="index < section.items.length - 1"
                        :class="item.danger ? 'm3-settings-hub__item--danger' : ''"
                    >
                        <template #icon>
                            <span :class="['m3-settings-hub__ico', item.danger ? 'm3-settings-hub__ico--danger' : '']">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path
                                        v-for="(path, pathIndex) in item.paths"
                                        :key="pathIndex"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        :d="path"
                                    />
                                </svg>
                            </span>
                        </template>
                        {{ item.label }}
                        <template #supporting>{{ item.description }}</template>
                        <template #trailing>
                            <svg class="m3-settings-hub__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="chevron" />
                            </svg>
                        </template>
                    </M3ListItem>
                </M3Card>
            </M3SettingsSection>

            <button type="button" class="m3-settings-logout" @click="handleLogout">
                <span class="m3-settings-logout__ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                    </svg>
                </span>
                <span>{{ $t('profile.logout') }}</span>
            </button>
        </M3SettingsPage>
    </M3Layout>
</template>

<style scoped>
.m3-settings-user {
    display: flex;
    align-items: center;
    gap: var(--m3-space-4);
}

.m3-settings-user__avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 4rem;
    height: 4rem;
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    overflow: hidden;
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
    font-size: var(--m3-type-title-large-size);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    letter-spacing: var(--m3-type-title-large-tracking);
}

.m3-settings-user__avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.m3-settings-user__body {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-0-5);
    flex: 1;
    min-width: 0;
}

.m3-settings-user__name {
    margin: 0;
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    letter-spacing: var(--m3-type-title-medium-tracking);
    color: var(--m3-color-on-surface);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-settings-user__email {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-settings-user__badges {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-1);
    margin-top: var(--m3-space-1);
}

.m3-settings-user__badge {
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
}

.m3-settings-hub__card {
    overflow: hidden;
}

.m3-settings-hub__ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-settings-hub__ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-settings-hub__ico--danger {
    background: var(--m3-color-error-container);
    color: var(--m3-color-on-error-container);
}

.m3-settings-hub__chevron {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    color: var(--m3-color-on-surface-variant);
}

.m3-settings-logout {
    display: flex;
    align-items: center;
    gap: var(--m3-space-4);
    width: 100%;
    padding: var(--m3-space-3) var(--m3-space-4);
    border: 1px solid var(--m3-color-error);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-error-container);
    color: var(--m3-color-on-error-container);
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
    font-weight: var(--m3-type-emphasized-body-large-weight);
    text-align: start;
    cursor: pointer;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-settings-logout:hover {
    opacity: 0.9;
}

.m3-settings-logout:focus-visible {
    outline: 2px solid var(--m3-color-error);
    outline-offset: 2px;
}

.m3-settings-logout__ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-error);
    color: var(--m3-color-on-error);
}

.m3-settings-logout__ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}
</style>
