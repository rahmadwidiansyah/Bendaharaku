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
import { buildSettingsSections } from '@/Material/utils/settingsSections.js'
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

const sections = computed(() => buildSettingsSections(t))
const chevron = 'M8.25 4.5l7.5 7.5-7.5 7.5'
const handleLogout = () => router.post(route('logout'))
</script>

<template>
    <M3Layout :title="$t('settings.title')">
        <Head :title="$t('settings.title')" />

        <M3SettingsPage
        :title="$t('settings.title')"
        :subtitle="$t('settings.subtitle')"
        width="content"
        shell
    >
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

            <div class="m3-settings-hub__sections">
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
            </div>

            <div class="m3-settings-hub__footer">
                <button type="button" class="m3-settings-logout" @click="handleLogout">
                <span class="m3-settings-logout__ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                    </svg>
                </span>
                    <span>{{ $t('profile.logout') }}</span>
                </button>
            </div>
        </M3SettingsPage>
    </M3Layout>
</template>

<style scoped>
/* Pakai lebar laptop untuk menampilkan grup pengaturan berdampingan. */
.m3-settings-hub__sections {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-6);
}

@media (min-width: 64rem) {
    /* Di dalam shell, kolom konten 56rem — grid kategori 2 kolom sejak 1024px */
    .m3-settings-hub__sections {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: start;
        gap: var(--m3-space-5) var(--m3-space-6);
    }
}

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

.m3-settings-hub__footer {
    display: flex;
    justify-content: flex-start;
}

/* Sign Out kompak — tidak bersaing dengan kategori pengaturan (audit hub). */
.m3-settings-logout {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-2) var(--m3-space-4) var(--m3-space-2) var(--m3-space-2);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-error);
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-emphasized-label-large-weight);
    text-align: start;
    cursor: pointer;
    transition: background var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-settings-logout:hover {
    background: var(--m3-color-error-container);
    color: var(--m3-color-on-error-container);
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
    width: 1.75rem;
    height: 1.75rem;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-error-container);
    color: var(--m3-color-on-error-container);
}

.m3-settings-logout__ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}
</style>
