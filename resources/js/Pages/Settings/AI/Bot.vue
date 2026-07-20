<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import SettingsBreadcrumb from '@/Components/Settings/SettingsBreadcrumb.vue';
import { useI18n } from 'vue-i18n';
import { ref, onMounted } from 'vue';

const { t } = useI18n();

interface Props {
  botName?: string;
  botAvatar?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
  botName: 'Ken-Chan',
  botAvatar: null,
});

const form = useForm({
  bot_name: props.botName,
  bot_avatar: null as any,
  _method: 'PATCH',
});

const previewFile = ref<string | null>(null);
const saveStatus = ref('');

const suggestedNames = ['Ken-Chan', 'Kira', 'Nova', 'Aria', 'Zara', 'Luna', 'Rex', 'Byte', 'Sage', 'Echo'];

const breadcrumbs = [
  { label: t('settings.title'), href: route('settings.index') },
  { label: t('settings.ai.title') },
  { label: t('settings.ai.bot.title') },
];

const handleAvatarUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  
  if (file) {
    form.bot_avatar = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      previewFile.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
};

const saveBotSettings = () => {
  form.post(route('settings.chat.bot-profile.update'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      saveStatus.value = 'saved';
      form.bot_avatar = null;
      setTimeout(() => (saveStatus.value = ''), 3000);
    },
    onError: () => {
      saveStatus.value = 'error';
      setTimeout(() => (saveStatus.value = ''), 3000);
    }
  });
};

const deleteBotAvatar = () => {
  if (confirm('Are you sure you want to remove the bot avatar?')) {
    router.delete(route('settings.chat.bot-avatar.destroy'), {
      preserveScroll: true,
      onSuccess: () => {
        previewFile.value = null;
        form.bot_avatar = null;
        saveStatus.value = 'saved';
        setTimeout(() => (saveStatus.value = ''), 3000);
      }
    });
  }
};
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.ai.bot.title')" />
    
    <SettingsLayout>
      <template #header>
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-black text-white tracking-tight leading-none">{{ t('settings.ai.bot.title') }}</h2>
          <p class="text-sm text-gray-400 mt-1.5 font-medium">{{ t('settings.ai.bot.description') }}</p>
        </div>
        <div class="mt-2">
          <SettingsBreadcrumb :breadcrumbs="breadcrumbs" />
        </div>
      </template>

      <!-- Messages -->
      <div v-if="saveStatus === 'saved'" class="mb-4 p-4 bg-green-500/20 border border-green-500/50 rounded-lg">
        <p class="text-sm text-green-400">✓ {{ t('toast.updated') }}</p>
      </div>
      <div v-if="saveStatus === 'error'" class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
        <p class="text-sm text-red-400">✗ {{ t('errors.generic') }}</p>
      </div>

      <!-- Bot Avatar -->
      <SettingsCard
        :title="t('settings.ai.bot.avatar.label')"
        :description="t('settings.ai.bot.avatar.description')"
      >
        <div class="space-y-4">
          <!-- Avatar Preview -->
          <div class="flex justify-center gap-4 items-center flex-col sm:flex-row">
            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center overflow-hidden border-4 border-purple-500/30 shrink-0">
              <img
                v-if="previewFile || botAvatar"
                :src="previewFile || botAvatar"
                :alt="botName"
                class="w-full h-full object-cover"
              />
              <span v-else class="text-3xl">🤖</span>
            </div>
            
            <div class="flex flex-col gap-2">
              <!-- Upload Button -->
              <label class="cursor-pointer text-center">
                <div class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-colors">
                  {{ t('settings.ai.bot.avatar.upload_button') }}
                </div>
                <input
                  type="file"
                  accept="image/*"
                  @change="handleAvatarUpload"
                  class="hidden"
                />
              </label>
              
              <!-- Remove Button -->
              <button
                v-if="botAvatar || previewFile"
                @click="deleteBotAvatar"
                class="px-5 py-2 bg-red-900/40 border border-red-900/50 text-red-200 hover:bg-red-600 hover:text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all"
              >
                {{ t('chatBot.removePhoto') }}
              </button>
            </div>
          </div>

          <p class="text-xs text-gray-400 text-center">
            {{ t('settings.ai.bot.avatar.hint') }}
          </p>
        </div>
      </SettingsCard>

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
            class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 transition-colors"
          />

          <!-- Name Suggestion Chips -->
          <div class="space-y-2">
            <p class="text-xs text-gray-500 font-medium">Nama Saran:</p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="name in suggestedNames"
                :key="name"
                type="button"
                @click="form.bot_name = name"
                class="px-3 py-1 rounded-full bg-gray-800 border border-white/8 text-xs text-gray-300 hover:text-white hover:border-purple-500/50 hover:bg-purple-500/10 transition-all cursor-pointer"
              >
                {{ name }}
              </button>
            </div>
          </div>

          <p class="text-xs text-gray-400">
            {{ t('settings.ai.bot.name.hint') }}
          </p>
        </div>
      </SettingsCard>

      <!-- Save Button -->
      <div class="mt-6 flex justify-end gap-2">
        <button
          @click="saveBotSettings"
          :disabled="form.processing"
          class="px-6 py-2.5 bg-purple-600 text-white rounded-xl hover:bg-purple-700 disabled:bg-gray-700 disabled:cursor-not-allowed transition-colors font-bold text-xs uppercase tracking-wider"
        >
          {{ form.processing ? t('common.saving') : t('settings.save_button') }}
        </button>
      </div>

      <!-- Help Text -->
      <div class="mt-6 p-4 bg-blue-900/20 border border-blue-500/20 rounded-lg">
        <p class="text-sm text-blue-400">
          💡 {{ t('settings.ai.bot.help_text') }}
        </p>
      </div>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
