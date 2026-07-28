<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import ImageCropModal from '@/Components/ImageCropModal.vue';

import { useI18n } from 'vue-i18n';
import ConfirmationDialog from '@/Components/ConfirmationDialog.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { showToast } = useToast();

interface Props {
  botName?: string;
  botAvatar?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
  botName: 'Ken-Chan',
  botAvatar: null,
});

const botAvatarUrl = computed(() => previewFile.value || props.botAvatar);

const form = useForm({
  bot_name: props.botName,
  bot_avatar: null as any,
  _method: 'PATCH',
});

const previewFile = ref<string | null>(null);
const showCropModal = ref(false);
const showPhotoMenu = ref(false);
const photoMenuRef = ref<HTMLElement | null>(null);

const suggestedNames = ['Ken-Chan', 'Ken-Dana', 'Assistant', 'Bendaharaku'];

const openCropModal = () => {
  showPhotoMenu.value = false;
  showCropModal.value = true;
};

const handleCropped = (blob: Blob) => {
  const file = new File([blob], 'bot-avatar.webp', { type: blob.type });
  form.bot_avatar = file;
  const reader = new FileReader();
  reader.onload = (e) => {
    previewFile.value = e.target?.result as string;
  };
  reader.readAsDataURL(blob);
  form.post(route('settings.chat.bot-profile.update'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      showToast(t('toast.updated'), 'success');
      form.bot_avatar = null;
    },
    onError: () => {
      showToast(t('errors.generic'), 'error');
    },
  });
};

const saveBotSettings = () => {
  form.post(route('settings.chat.bot-profile.update'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      showToast(t('toast.updated'), 'success');
      form.bot_avatar = null;
    },
    onError: () => {
      showToast(t('errors.generic'), 'error');
    }
  });
};

const showDeleteAvatarConfirm = ref(false);

const deleteBotAvatar = () => {
  showPhotoMenu.value = false;
  showDeleteAvatarConfirm.value = true;
};

const confirmDeleteBotAvatar = () => {
  router.delete(route('settings.chat.bot-avatar.destroy'), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteAvatarConfirm.value = false;
      previewFile.value = null;
      form.bot_avatar = null;
      showToast(t('toast.updated'), 'success');
    }
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
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.ai.bot.title')" />

    <SettingsLayout
      :title="t('settings.ai.bot.title')"
      :description="t('settings.ai.bot.description')"
    >
      <form @submit.prevent="saveBotSettings" class="space-y-3 sm:space-y-5">

        <!-- Bot Avatar -->
        <div class="flex flex-col items-center py-2 sm:py-4">
          <div ref="photoMenuRef" class="relative">
            <button type="button" @click="togglePhotoMenu" class="outline-none">
              <div class="w-28 h-28 sm:w-36 sm:h-36 rounded-full ring-2 ring-purple-500/30 shadow-lg shadow-purple-900/20 overflow-hidden transition-all hover:ring-purple-500/70 active:scale-95 bg-gray-800 flex items-center justify-center">
                <img
                  v-if="botAvatarUrl"
                  :src="botAvatarUrl"
                  :alt="botName"
                  class="w-full h-full object-cover"
                />
                <span v-else class="text-4xl sm:text-5xl">🤖</span>
              </div>
            </button>
            <div
              v-if="showPhotoMenu"
              class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-44 bg-gray-800 border border-white/10 rounded-xl shadow-xl shadow-black/50 overflow-hidden z-10"
            >
              <button type="button" @click="openCropModal" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-white hover:bg-white/10 transition-colors">
                <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ t('settings.ai.bot.avatar.upload_button') }}
              </button>
              <button v-if="botAvatarUrl" type="button" @click="deleteBotAvatar" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-red-400 hover:bg-white/10 transition-colors border-t border-white/5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                {{ t('chatBot.removePhoto') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Bot Name -->
        <SettingsCard
          :title="t('settings.ai.bot.name.label')"
          :description="t('settings.ai.bot.name.description')"
        >
          <div class="space-y-3">
            <input
              v-model="form.bot_name"
              type="text"
              :placeholder="t('settings.ai.bot.name.placeholder')"
              class="w-full px-3 py-1.5 sm:px-4 sm:py-2.5 bg-gray-800 border border-gray-700 rounded-lg sm:rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 transition-all text-sm"
            />

            <!-- Name Suggestion Chips -->
            <div class="space-y-2">
              <p class="text-2xs text-gray-500 font-medium">{{ t('settings.ai.bot.name.suggestions') }}</p>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="name in suggestedNames"
                  :key="name"
                  type="button"
                  @click="form.bot_name = name"
                  class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full bg-gray-800 border border-white/8 text-2xs sm:text-xs text-gray-300 hover:text-white hover:border-purple-500/50 hover:bg-purple-500/10 transition-all cursor-pointer"
                >
                  {{ name }}
                </button>
              </div>
            </div>

            <p class="text-2xs text-gray-400">
              {{ t('settings.ai.bot.name.hint') }}
            </p>
          </div>
        </SettingsCard>

        <!-- Save -->
        <div class="flex items-center gap-3 pt-1 sm:pt-2">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 sm:px-6 sm:py-3 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-700 disabled:cursor-not-allowed text-white rounded-lg sm:rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-lg shadow-purple-900/20"
          >
            <span v-if="form.processing" class="flex items-center gap-2">
              <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ t('common.saving') }}
            </span>
            <span v-else>{{ t('common.save') }}</span>
          </button>
        </div>
      </form>

    </SettingsLayout>

    <!-- Confirm Delete Avatar -->
    <ConfirmationDialog
      :show="showDeleteAvatarConfirm"
      title="Hapus Avatar Bot?"
      message="Apakah kamu yakin ingin menghapus avatar bot?"
      :confirm-text="t('btn.delete')"
      :cancel-text="t('common.cancel')"
      variant="danger"
      @close="showDeleteAvatarConfirm = false"
      @confirm="confirmDeleteBotAvatar"
    />

    <!-- Image Crop Modal -->
    <ImageCropModal
      v-model="showCropModal"
      :title="t('settings.ai.bot.avatar.upload_button')"
      :aspectRatio="1"
      :circle="true"
      :maxSizeKb="800"
      outputFormat="image/webp"
      @cropped="handleCropped"
    />
  </AuthenticatedLayout>
</template>
