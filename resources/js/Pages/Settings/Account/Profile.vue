<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import ImageCropModal from '@/Components/ImageCropModal.vue';
import { useI18n } from 'vue-i18n';
import { ref, computed } from 'vue';

const { t } = useI18n();

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
const saving = ref(false);

// ── Crop handler ───────────────────────────────────────────────────
const openCropModal = () => {
  showCropModal.value = true;
};

const handleCropped = (blob: Blob) => {
  // Create a File from the blob so Inertia can upload it
  const file = new File([blob], 'avatar.webp', { type: blob.type });
  form.avatar_file = file;
  previewUrl.value = URL.createObjectURL(blob);
};

// ── Submit ─────────────────────────────────────────────────────────
const handleSave = () => {
  form.post(route('settings.account.profile.update'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      previewUrl.value = null;
      form.avatar_file = null;
    },
  });
};

const breadcrumbs = [
  { label: t('settings.title'), href: route('settings.index') },
  { label: t('settings.account.title') },
  { label: t('settings.account.profile.title') },
];
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.account.profile.title')" />

    <SettingsLayout
      :title="t('settings.account.profile.title')"
      :description="t('settings.account.profile.description')"
      :breadcrumbs="breadcrumbs"
    >
      <!-- Success Banner -->
      <div v-if="status === 'profile-updated'" class="mb-4 p-4 bg-green-500/20 border border-green-500/50 rounded-xl">
        <p class="text-sm text-green-400 font-semibold">✓ {{ t('toast.updated') }}</p>
      </div>

      <form @submit.prevent="handleSave" class="space-y-5">

        <!-- ── AVATAR CARD ─────────────────────────────────────────── -->
        <SettingsCard
          :title="t('profile.choosePhoto', 'Profile Photo')"
          :description="t('settings.ai.bot.avatar.hint', 'Square image recommended. JPG, PNG or WebP.')"
        >
          <div class="flex flex-col sm:flex-row items-center gap-6">
            <!-- Avatar Preview -->
            <div class="relative shrink-0 group cursor-pointer" @click="openCropModal">
              <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-purple-500/30 shadow-lg shadow-purple-900/20 transition-all group-hover:border-purple-500/70">
                <img :src="avatarSrc" :alt="user.name" class="w-full h-full object-cover" />
              </div>
              <!-- Hover overlay -->
              <div class="absolute inset-0 rounded-full bg-black/60 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 backdrop-blur-sm">
                <svg class="w-6 h-6 text-white mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="text-[9px] text-white font-bold uppercase tracking-widest">{{ t('profile.choosePhoto', 'Change') }}</span>
              </div>

              <!-- Changed indicator -->
              <div v-if="previewUrl" class="absolute -top-1 -right-1 w-5 h-5 bg-purple-500 rounded-full border-2 border-gray-900 flex items-center justify-center">
                <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </div>
            </div>

            <!-- Upload instructions -->
            <div class="flex-1 space-y-3 text-center sm:text-left">
              <div>
                <p class="text-sm font-semibold text-white">{{ user.name }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ user.email }}</p>
              </div>
              <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
                <button
                  type="button"
                  @click="openCropModal"
                  class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-colors"
                >
                  {{ t('profile.choosePhoto', 'Choose Photo') }}
                </button>
                <span v-if="previewUrl" class="inline-flex items-center text-2xs text-purple-400 font-semibold">
                  ✓ {{ t('profile.newPhotoSelected', 'New photo selected — save to apply') }}
                </span>
              </div>
              <p class="text-2xs text-gray-500">{{ t('settings.ai.bot.avatar.hint', 'Recommended: square, max 2MB.') }}</p>
              <p v-if="form.errors.avatar_file" class="text-2xs text-red-400 font-semibold">{{ form.errors.avatar_file }}</p>
            </div>
          </div>
        </SettingsCard>

        <!-- ── IDENTITY CARD ───────────────────────────────────────── -->
        <SettingsCard
          :title="t('settings.account.profile.title', 'Personal Information')"
          :description="t('settings.account.profile.description', 'Your name, email and contact info')"
        >
          <div class="space-y-4">
            <!-- Name -->
            <div>
              <label class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
                {{ t('profile.name') }}
              </label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500/30 transition-all"
                :placeholder="t('profile.name')"
              />
              <p v-if="form.errors.name" class="mt-1.5 text-2xs text-red-400 font-semibold ml-1">{{ form.errors.name }}</p>
            </div>

            <!-- Email -->
            <div>
              <label class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
                {{ t('profile.email') }}
              </label>
              <input
                v-model="form.email"
                type="email"
                required
                class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500/30 transition-all"
                :placeholder="t('profile.email')"
              />
              <p v-if="form.errors.email" class="mt-1.5 text-2xs text-red-400 font-semibold ml-1">{{ form.errors.email }}</p>
            </div>
          </div>
        </SettingsCard>

        <!-- ── SOCIAL / CONTACTS CARD ──────────────────────────────── -->
        <SettingsCard
          :title="t('profile.socialConnections', 'Social & Messaging')"
          :description="t('profile.socialConnectionsDesc', 'Connect messaging apps for AI integration')"
        >
          <div class="space-y-4">
            <!-- WhatsApp -->
            <div>
              <label class="flex items-center gap-2 text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
                <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                  <path d="M12.04 2C6.474 2 1.997 6.477 1.997 12.043c0 1.843.478 3.58 1.375 5.098L2 22l4.996-1.31C8.465 21.54 10.218 22 12.04 22c5.566 0 10.043-4.477 10.043-10.043C22.083 6.477 17.606 2 12.04 2z"/>
                </svg>
                WhatsApp
              </label>
              <input
                v-model="form.whatsapp_number"
                type="text"
                placeholder="0812xxxx"
                class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500/20 transition-all"
              />
              <p v-if="form.errors.whatsapp_number" class="mt-1.5 text-2xs text-red-400 font-semibold ml-1">{{ form.errors.whatsapp_number }}</p>
            </div>

            <!-- Telegram -->
            <div>
              <label class="flex items-center gap-2 text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
                <svg class="w-3.5 h-3.5 text-sky-400" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                </svg>
                Telegram
              </label>
              <input
                v-model="form.telegram_id"
                type="text"
                placeholder="@username"
                class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 transition-all"
              />
              <p v-if="form.errors.telegram_id" class="mt-1.5 text-2xs text-red-400 font-semibold ml-1">{{ form.errors.telegram_id }}</p>
            </div>
          </div>
        </SettingsCard>

        <!-- ── GOOGLE CARD ──────────────────────────────────────────── -->
        <SettingsCard
          title="Google"
          :description="t('profile.google.connect', 'Connect your Google account for easy sign-in')"
        >
          <div v-if="!user.google_id">
            <a
              :href="route('google.login')"
              class="inline-flex items-center gap-3 px-5 py-3 bg-gray-800 hover:bg-gray-700 border border-white/10 hover:border-white/20 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all"
            >
              <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google" class="w-4 h-4" />
              {{ t('profile.google.connect') }}
            </a>
          </div>
          <div v-else class="flex items-center gap-3 px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm font-semibold text-emerald-400">{{ t('profile.google.connected') }}</span>
          </div>
        </SettingsCard>

        <!-- ── SAVE BUTTON ──────────────────────────────────────────── -->
        <div class="flex items-center gap-4 pt-2">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-3 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-700 disabled:cursor-not-allowed text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-colors shadow-lg shadow-purple-900/20"
          >
            <span v-if="form.processing" class="flex items-center gap-2">
              <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ t('common.saving') }}
            </span>
            <span v-else>{{ t('profile.updateProfile', 'Save Changes') }}</span>
          </button>

          <p v-if="form.recentlySuccessful" class="text-sm text-green-400 font-semibold">
            ✓ {{ t('toast.updated') }}
          </p>
        </div>

      </form>

      <!-- Note about password & account deletion -->
      <div class="mt-6 p-4 bg-gray-900/60 border border-white/5 rounded-xl flex gap-3">
        <svg class="w-4 h-4 text-gray-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-2xs text-gray-500 leading-relaxed">
          {{ t('settings.account.profile.help_text') }}
          <Link :href="route('settings.account.security')" class="text-purple-400 hover:text-purple-300 font-semibold ml-1 transition-colors">
            {{ t('settings.account.security.title') }}
          </Link>.
          {{ t('settings.privacy.danger.delete_account.title') }} →
          <Link :href="route('settings.privacy.danger')" class="text-red-400 hover:text-red-300 font-semibold ml-1 transition-colors">
            {{ t('settings.privacy.danger.title') }}
          </Link>.
        </p>
      </div>
    </SettingsLayout>

    <!-- Image Crop Modal -->
    <ImageCropModal
      v-model="showCropModal"
      :title="t('profile.choosePhoto', 'Atur Foto Profil')"
      :aspectRatio="1"
      :circle="true"
      :maxSizeKb="800"
      outputFormat="image/webp"
      @cropped="handleCropped"
    />
  </AuthenticatedLayout>
</template>
