<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import ImageCropModal from '@/Components/ImageCropModal.vue';
import Avatar from '@/Components/Avatar.vue';
import { useI18n } from 'vue-i18n';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { showToast } = useToast();

interface Props {
  user: {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    avatar_url?: string | null;
    whatsapp_number?: string | null;
    telegram_id?: string | null;
    google_id?: string | null;
  };
  status?: string;
}

const props = defineProps<Props>();

// ── Avatar helpers ─────────────────────────────────────────────────
const isUrl = (url: string) => url && (url.startsWith('http://') || url.startsWith('https://'));

const currentAvatar = computed(() => {
  const av = props.user?.avatar_url ?? props.user?.avatar;
  if (!av) return null;
  if (isUrl(av)) return av;
  return `/storage/${av}`;
});

const avatarSrc = computed(() => previewUrl.value || currentAvatar.value);

// ── Form ───────────────────────────────────────────────────────────
const form = useForm({
  name: props.user.name,
  email: props.user.email,
  whatsapp_number: props.user.whatsapp_number || '',
  telegram_id: props.user.telegram_id || '',
  avatar_file: null as any,
  _method: 'PATCH',
});

const previewUrl = ref<string | null>(null);
const showCropModal = ref(false);
const showPhotoMenu = ref(false);
const photoMenuRef = ref<HTMLElement | null>(null);
const saving = ref(false);

// ── Crop handler ───────────────────────────────────────────────────
const openCropModal = () => {
  showPhotoMenu.value = false;
  showCropModal.value = true;
};

const handleCropped = (blob: Blob) => {
  const file = new File([blob], 'avatar.webp', { type: blob.type });
  form.avatar_file = file;
  previewUrl.value = URL.createObjectURL(blob);
  form.post(route('settings.account.profile.update'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      previewUrl.value = null;
      form.avatar_file = null;
      showToast(t('toast.updated'), 'success');
    },
  });
};

const removeAvatar = () => {
  showPhotoMenu.value = false;
  form.avatar_file = new File([], '');
  form.post(route('settings.account.profile.update'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      previewUrl.value = null;
      form.avatar_file = null;
      showToast(t('toast.updated'), 'success');
    },
  });
};

const togglePhotoMenu = () => {
  showPhotoMenu.value = !showPhotoMenu.value;
};

const closePhotoMenu = (e: MouseEvent) => {
  if (photoMenuRef.value && !photoMenuRef.value.contains(e.target as Node)) {
    showPhotoMenu.value = false;
  }
};

onMounted(() => document.addEventListener('click', closePhotoMenu));
onUnmounted(() => document.removeEventListener('click', closePhotoMenu));

// ── Submit ─────────────────────────────────────────────────────────
const handleSave = () => {
  form.post(route('settings.account.profile.update'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      previewUrl.value = null;
      form.avatar_file = null;
      showToast(t('toast.updated'), 'success');
    },
  });
};


</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.account.profile.title')" />

    <SettingsLayout
      :title="t('settings.account.profile.title')"
      :description="t('settings.account.profile.description')"
    >
      <form @submit.prevent="handleSave" class="space-y-3 sm:space-y-5">

        <!-- ── AVATAR ──────────────────────────────────────────────── -->
        <div class="flex flex-col items-center py-2 sm:py-3">
          <div ref="photoMenuRef" class="relative">
            <button type="button" @click="togglePhotoMenu" class="outline-none">
              <Avatar
                :src="avatarSrc"
                :name="user.name ?? 'U'"
                class="w-28 h-28 sm:w-36 sm:h-36 rounded-full ring-2 ring-purple-500/30 shadow-lg shadow-purple-900/20 transition-all hover:ring-purple-500/70 active:scale-95"
              />
            </button>
            <div
              v-if="showPhotoMenu"
              class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-44 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-xl shadow-xl shadow-black/50 overflow-hidden z-10"
            >
              <button type="button" @click="openCropModal" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-[var(--color-text-primary)] hover:bg-white/10 transition-colors">
                <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ t('profile.choosePhoto') }}
              </button>
              <button v-if="currentAvatar || previewUrl" type="button" @click="removeAvatar" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-red-400 hover:bg-white/10 transition-colors border-t border-white/5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                {{ t('profile.removePhoto') }}
              </button>
            </div>
          </div>
          <p class="mt-3 text-sm font-semibold text-[var(--color-text-primary)]">{{ user.name }}</p>
          <p class="text-xs text-[var(--color-text-secondary)]">{{ user.email }}</p>
          <p v-if="form.errors.avatar_file" class="mt-2 text-2xs text-red-400 font-semibold">{{ form.errors.avatar_file }}</p>
        </div>

        <!-- ── IDENTITY ────────────────────────────────────────────── -->
        <SettingsCard
          :title="t('settings.account.profile.title')"
          :description="t('settings.account.profile.description')"
        >
          <div class="space-y-3">
            <div>
              <label class="block text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest mb-1 ml-1">
                {{ t('profile.name') }}
              </label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full px-3 py-1.5 sm:px-4 sm:py-2.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg sm:rounded-xl text-[var(--color-text-primary)] placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-[var(--color-brand)]/30 transition-all text-sm"
                :placeholder="t('profile.name')"
              />
              <p v-if="form.errors.name" class="mt-1 text-2xs text-red-400 font-semibold ml-1">{{ form.errors.name }}</p>
            </div>

            <div>
              <label class="block text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest mb-1 ml-1">
                {{ t('profile.email') }}
              </label>
              <input
                v-model="form.email"
                type="email"
                required
                class="w-full px-3 py-1.5 sm:px-4 sm:py-2.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg sm:rounded-xl text-[var(--color-text-primary)] placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-[var(--color-brand)]/30 transition-all text-sm"
                :placeholder="t('profile.email')"
              />
              <p v-if="form.errors.email" class="mt-1.5 text-2xs text-red-400 font-semibold ml-1">{{ form.errors.email }}</p>
            </div>
          </div>
        </SettingsCard>

        <!-- ── SOCIAL ──────────────────────────────────────────────── -->
        <SettingsCard
          :title="t('profile.socialConnections')"
          :description="t('profile.socialConnectionsDesc')"
        >
          <div class="space-y-3">
            <!-- WhatsApp -->
            <div>
              <label class="flex items-center gap-2 text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest mb-1 ml-1">
                <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                  <path d="M12.04 2C6.474 2 1.997 6.477 1.997 12.043c0 1.843.478 3.58 1.375 5.098L2 22l4.996-1.31C8.465 21.54 10.218 22 12.04 22c5.566 0 10.043-4.477 10.043-10.043C22.083 6.477 17.606 2 12.04 2z"/>
                </svg>
                {{ t('profile.whatsapp') }}
              </label>
              <input
                v-model="form.whatsapp_number"
                type="text"
                placeholder="0812xxxx"
                class="w-full px-3 py-1.5 sm:px-4 sm:py-2.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg sm:rounded-xl text-[var(--color-text-primary)] placeholder-gray-500 focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500/20 transition-all text-sm"
              />
              <p v-if="form.errors.whatsapp_number" class="mt-1 text-2xs text-red-400 font-semibold ml-1">{{ form.errors.whatsapp_number }}</p>
            </div>

            <!-- Telegram -->
            <div>
              <label class="flex items-center gap-2 text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest mb-1 ml-1">
                <svg class="w-3 h-3 text-sky-400" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                </svg>
                {{ t('profile.telegram') }}
              </label>
              <input
                v-model="form.telegram_id"
                type="text"
                placeholder="@username"
                class="w-full px-3 py-1.5 sm:px-4 sm:py-2.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg sm:rounded-xl text-[var(--color-text-primary)] placeholder-gray-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 transition-all text-sm"
              />
              <p v-if="form.errors.telegram_id" class="mt-1.5 text-2xs text-red-400 font-semibold ml-1">{{ form.errors.telegram_id }}</p>
            </div>
          </div>
        </SettingsCard>

        <!-- ── GOOGLE ────────────────────────────────────────────────── -->
        <SettingsCard
          :title="t('profile.google.label')"
          :description="t('profile.google.connect')"
        >
          <div v-if="!user.google_id">
            <a
              :href="route('google.login')"
              class="inline-flex items-center gap-2 px-3 py-2 sm:px-5 sm:py-3 bg-[var(--color-surface-muted)] hover:bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] hover:border-white/20 text-[var(--color-text-primary)] text-xs font-bold uppercase tracking-wider rounded-lg sm:rounded-xl transition-all"
            >
              <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
              {{ t('profile.google.connect') }}
            </a>
          </div>
          <div v-else class="flex items-center gap-2 px-3 py-2.5 sm:px-4 sm:py-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg sm:rounded-xl">
            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span class="text-xs sm:text-sm font-semibold text-emerald-400">{{ t('profile.google.connected') }}</span>
          </div>
        </SettingsCard>

        <!-- ── SAVE ─────────────────────────────────────────────────── -->
        <div class="flex items-center gap-4 pt-1 sm:pt-2">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 sm:px-6 sm:py-3 bg-[var(--color-brand)] hover:bg-[var(--color-brand-hover)] disabled:bg-[var(--color-surface-muted)] disabled:cursor-not-allowed text-[var(--color-text-primary)] rounded-lg sm:rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-lg shadow-purple-900/20"
          >
            <span v-if="form.processing" class="flex items-center gap-2">
              <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ t('common.saving') }}
            </span>
            <span v-else>{{ t('profile.updateProfile') }}</span>
          </button>
        </div>

      </form>
    </SettingsLayout>

    <!-- Image Crop Modal -->
    <ImageCropModal
      v-model="showCropModal"
      :title="t('profile.choosePhoto')"
      :aspectRatio="1"
      :circle="true"
      :maxSizeKb="800"
      outputFormat="image/webp"
      @cropped="handleCropped"
    />
  </AuthenticatedLayout>
</template>
