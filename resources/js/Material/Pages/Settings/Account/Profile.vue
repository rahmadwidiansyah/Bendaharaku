<script setup>
/**
 * Material/Pages/Settings/Account/Profile.vue — profil akun (tema M3).
 *
 * Paritas perilaku Pages/Settings/Account/Profile.vue: form PATCH via POST +
 * forceFormData, crop avatar WebP/JPEG, hapus avatar, dan status koneksi Google.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3TextField from '@/Material/Components/M3TextField.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3ImageCropModal from '@/Material/Components/M3ImageCropModal.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import M3SettingsSection from '@/Material/Components/Settings/M3SettingsSection.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
    user: { type: Object, required: true },
    status: { type: String, default: '' },
})

const { t } = useI18n()
const { showToast } = useToast()

const isUrl = (url) => url && (url.startsWith('http://') || url.startsWith('https://'))
const currentAvatar = computed(() => {
    const avatar = props.user?.avatar_url ?? props.user?.avatar
    if (!avatar) return ''
    return isUrl(avatar) ? avatar : `/storage/${avatar}`
})

const previewUrl = ref('')
const avatarSrc = computed(() => previewUrl.value || currentAvatar.value)
const avatarInitial = computed(() => (props.user?.name || 'U').charAt(0).toUpperCase())

const showPhotoMenu = ref(false)
const showCropModal = ref(false)

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    whatsapp_number: props.user.whatsapp_number || '',
    telegram_id: props.user.telegram_id || '',
    avatar_file: null,
    _method: 'PATCH',
})

const submitOptions = {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
        previewUrl.value = ''
        form.avatar_file = null
        showToast(t('toast.updated'), 'success')
    },
}

const handleSave = () => form.post(route('settings.account.profile.update'), submitOptions)

function openCropModal() {
    showPhotoMenu.value = false
    showCropModal.value = true
}

function handleCropped(blob) {
    const file = new File([blob], 'avatar.webp', { type: blob.type })
    form.avatar_file = file
    previewUrl.value = URL.createObjectURL(blob)
    form.post(route('settings.account.profile.update'), submitOptions)
}

function removeAvatar() {
    showPhotoMenu.value = false
    form.avatar_file = new File([], '')
    form.post(route('settings.account.profile.update'), submitOptions)
}
</script>

<template>
    <M3Layout :title="t('settings.account.profile.title')">
        <Head :title="t('settings.account.profile.title')" />

        <M3SettingsPage
            :title="t('settings.account.profile.title')"
            :subtitle="t('settings.account.profile.description')"
            back-route="settings.index"
        >
            <form class="m3-profile" @submit.prevent="handleSave">
                <div class="m3-profile__hero">
                    <button
                        type="button"
                        class="m3-profile__avatar"
                        :aria-label="t('profile.choosePhoto')"
                        @click="showPhotoMenu = true"
                    >
                        <img v-if="avatarSrc" :src="avatarSrc" alt="" class="m3-profile__avatar-img" />
                        <span v-else aria-hidden="true">{{ avatarInitial }}</span>
                    </button>
                    <p class="m3-profile__name">{{ user.name }}</p>
                    <p class="m3-profile__email">{{ user.email }}</p>
                    <p v-if="form.errors.avatar_file" class="m3-profile__error">{{ form.errors.avatar_file }}</p>
                </div>

                <M3SettingsSection :title="t('settings.account.profile.title')">
                    <M3Card variant="outlined" padding="lg" class="m3-profile__form-card">
                        <M3TextField
                            v-model="form.name"
                            :label="t('profile.name')"
                            :error="form.errors.name || ''"
                            autocomplete="name"
                        />
                        <M3TextField
                            v-model="form.email"
                            type="email"
                            :label="t('profile.email')"
                            :error="form.errors.email || ''"
                            autocomplete="email"
                        />
                    </M3Card>
                </M3SettingsSection>

                <M3SettingsSection :title="t('profile.socialConnections')">
                    <M3Card variant="outlined" padding="lg" class="m3-profile__form-card">
                        <p class="m3-profile__hint">{{ t('profile.socialConnectionsDesc') }}</p>
                        <M3TextField
                            v-model="form.whatsapp_number"
                            :label="t('profile.whatsapp')"
                            placeholder="0812xxxx"
                            inputmode="tel"
                            :error="form.errors.whatsapp_number || ''"
                        />
                        <M3TextField
                            v-model="form.telegram_id"
                            :label="t('profile.telegram')"
                            placeholder="@username"
                            :error="form.errors.telegram_id || ''"
                        />
                    </M3Card>
                </M3SettingsSection>

                <M3SettingsSection :title="t('profile.google.label')">
                    <M3Card variant="outlined" padding="lg">
                        <div v-if="!user.google_id" class="m3-profile__google">
                            <p class="m3-profile__hint">{{ t('profile.google.connect') }}</p>
                            <M3Button :href="route('google.login')" variant="tonal">
                                <template #leading>
                                    <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="" />
                                </template>
                                {{ t('profile.google.connect') }}
                            </M3Button>
                        </div>
                        <div v-else class="m3-profile__connected">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ t('profile.google.connected') }}</span>
                        </div>
                    </M3Card>
                </M3SettingsSection>

                <div class="m3-profile__actions">
                    <M3Button type="submit" :disabled="form.processing">
                        {{ form.processing ? t('common.saving') : t('profile.updateProfile') }}
                    </M3Button>
                </div>
            </form>
        </M3SettingsPage>

        <M3Modal
            :show="showPhotoMenu"
            :title="t('profile.choosePhoto')"
            max-width="sm"
            mobile-variant="sheet"
            @close="showPhotoMenu = false"
        >
            <div class="m3-profile__photo-menu">
                <button type="button" class="m3-profile__photo-action" @click="openCropModal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                    </svg>
                    {{ t('profile.choosePhoto') }}
                </button>
                <button
                    v-if="currentAvatar || previewUrl"
                    type="button"
                    class="m3-profile__photo-action m3-profile__photo-action--danger"
                    @click="removeAvatar"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                    {{ t('profile.removeAvatar') }}
                </button>
            </div>
        </M3Modal>

        <M3ImageCropModal
            v-model="showCropModal"
            :title="t('profile.choosePhoto')"
            :aspect-ratio="1"
            :circle="true"
            :max-size-kb="800"
            output-format="image/webp"
            @cropped="handleCropped"
        />
    </M3Layout>
</template>

<style scoped>
.m3-profile {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-6);
    max-width: var(--m3-size-list-max);
    width: 100%;
    margin: 0 auto;
}

.m3-profile__hero {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-1);
    text-align: center;
}

.m3-profile__avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 6rem;
    height: 6rem;
    margin-bottom: var(--m3-space-2);
    border: none;
    border-radius: var(--m3-radius-full);
    overflow: hidden;
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
    font-size: var(--m3-type-headline-medium-size);
    font-weight: var(--m3-type-emphasized-headline-medium-weight);
    cursor: pointer;
    transition: transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-profile__avatar:active {
    transform: scale(0.96);
}

.m3-profile__avatar:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-profile__avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.m3-profile__name {
    margin: 0;
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    color: var(--m3-color-on-surface);
}

.m3-profile__email {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-profile__error {
    margin: var(--m3-space-1) 0 0;
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-error);
}

.m3-profile__form-card {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
}

.m3-profile__hint {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-profile__google {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    align-items: flex-start;
}

.m3-profile__google img {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-profile__connected {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-3) var(--m3-space-4);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-label-large-weight);
}

.m3-profile__connected svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-profile__actions {
    display: flex;
}

.m3-profile__photo-menu {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-profile__photo-action {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    width: 100%;
    min-height: var(--m3-size-touch-target-lg);
    padding: var(--m3-space-3) var(--m3-space-4);
    border: none;
    border-radius: var(--m3-radius-md);
    background: transparent;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-body-large-size);
    text-align: start;
    cursor: pointer;
}

.m3-profile__photo-action:hover {
    background: var(--m3-color-surface-container-highest);
}

.m3-profile__photo-action svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-profile__photo-action--danger {
    color: var(--m3-color-error);
}
</style>
