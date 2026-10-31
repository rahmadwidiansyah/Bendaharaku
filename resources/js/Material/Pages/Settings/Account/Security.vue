<script setup>
/**
 * Material/Pages/Settings/Account/Security.vue — keamanan akun (tema M3).
 *
 * Paritas perilaku halaman Default: ubah password via password.update dan
 * tampilkan sesi login aktif dari props currentSession/otherSessions.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3TextField from '@/Material/Components/M3TextField.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import M3SettingsSection from '@/Material/Components/Settings/M3SettingsSection.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
    currentSession: { type: Object, default: null },
    otherSessions: { type: Array, default: () => [] },
})

const { t, locale } = useI18n()
const { showToast } = useToast()

const sessions = computed(() => {
    const all = [...(props.otherSessions ?? [])]
    if (props.currentSession) {
        const idx = all.findIndex((s) => s.ip === props.currentSession.ip)
        if (idx >= 0) {
            if (new Date(props.currentSession.last_activity) >= new Date(all[idx].last_activity)) {
                all[idx] = { ...props.currentSession, is_current: true }
            }
        } else {
            all.push({ ...props.currentSession, is_current: true })
        }
    }
    const map = new Map()
    for (const session of all) {
        const existing = map.get(session.ip)
        if (!existing || new Date(session.last_activity) >= new Date(existing.last_activity)) {
            map.set(session.ip, session)
        }
    }
    return Array.from(map.values()).sort(
        (a, b) => new Date(b.last_activity).getTime() - new Date(a.last_activity).getTime(),
    )
})

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

function parseBrowser(userAgent) {
    if (!userAgent) return 'Unknown'
    if (userAgent.includes('Edg')) return 'Edge'
    if (userAgent.includes('Chrome')) return 'Chrome'
    if (userAgent.includes('Firefox')) return 'Firefox'
    if (userAgent.includes('Safari')) return 'Safari'
    return 'Browser'
}

function formatActivity(timestamp) {
    if (!timestamp) return ''
    const date = new Date(timestamp)
    if (Number.isNaN(date.getTime())) return timestamp
    return date.toLocaleString(locale.value === 'en' ? 'en-US' : 'id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    })
}

function handleUpdatePassword() {
    if (form.password !== form.password_confirmation) {
        showToast('Passwords do not match.', 'error')
        return
    }

    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            showToast(t('profile.passwordUpdated'), 'success')
        },
        onError: () => {
            showToast(
                form.errors.current_password
                    || form.errors.password
                    || form.errors.password_confirmation
                    || t('errors.generic'),
                'error',
            )
        },
    })
}
</script>

<template>
    <M3Layout :title="t('settings.account.security.title')">
        <Head :title="t('settings.account.security.title')" />

        <M3SettingsPage
            :title="t('settings.account.security.title')"
            :subtitle="t('settings.account.security.description')"
            back-route="settings.index"
            width="content"
            shell
        >
            <div class="m3-settings-cols">
            <M3SettingsSection :title="t('settings.account.security.password.title')">
                <M3Card variant="outlined" padding="lg" class="m3-security__form">
                    <form class="m3-security__form-fields" @submit.prevent="handleUpdatePassword">
                        <M3TextField
                            v-model="form.current_password"
                            type="password"
                            :label="t('profile.currentPassword')"
                            autocomplete="current-password"
                            :error="form.errors.current_password || ''"
                        />
                        <M3TextField
                            v-model="form.password"
                            type="password"
                            :label="t('profile.newPassword')"
                            autocomplete="new-password"
                            :error="form.errors.password || ''"
                        />
                        <M3TextField
                            v-model="form.password_confirmation"
                            type="password"
                            :label="t('profile.confirmPassword')"
                            autocomplete="new-password"
                            :error="form.errors.password_confirmation || ''"
                        />
                        <div class="m3-security__actions">
                            <M3Button type="submit" :disabled="form.processing">
                                {{ form.processing ? t('common.saving') : t('settings.account.security.password.change_button') }}
                            </M3Button>
                        </div>
                    </form>
                </M3Card>
            </M3SettingsSection>

            <M3SettingsSection :title="t('settings.account.security.login_activity.title')">
                <M3Card variant="outlined" padding="none" class="m3-security__sessions">
                    <M3ListItem
                        v-for="(session, index) in sessions"
                        :key="session.ip + index"
                        :divider="index < sessions.length - 1"
                    >
                        <template #icon>
                            <span class="m3-security__session-ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                                </svg>
                            </span>
                        </template>
                        {{ parseBrowser(session.user_agent) }}
                        <template #supporting>
                            {{ session.ip }}<template v-if="session.last_activity"> · {{ formatActivity(session.last_activity) }}</template>
                        </template>
                        <template v-if="session.is_current" #trailing>
                            <span class="m3-security__current">{{ t('settings.account.security.login_activity.current') }}</span>
                        </template>
                    </M3ListItem>
                    <div v-if="sessions.length === 0" class="m3-security__empty">{{ t('common.noData') }}</div>
                </M3Card>
            </M3SettingsSection>
            </div>
        </M3SettingsPage>
    </M3Layout>
</template>

<style scoped>
.m3-security__form {
}

.m3-security__form-fields {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
}

.m3-security__actions {
    display: flex;
    padding-top: var(--m3-space-1);
}

.m3-security__sessions {
    overflow: hidden;
}

.m3-security__session-ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-security__session-ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-security__current {
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
    white-space: nowrap;
}

.m3-security__empty {
    padding: var(--m3-space-4);
    text-align: center;
    font-size: var(--m3-type-body-medium-size);
    color: var(--m3-color-on-surface-variant);
}
</style>
