<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';
import { ref, computed, watch, reactive } from 'vue';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { showToast } = useToast();

interface Props {
  providerStatuses?: Record<string, any>;
  availableProviders?: string[];
  modelsByProvider?: Record<string, string[]>;
  aiAccounts?: Record<string, any>[];
  aiRouting?: Record<string, any>;
  aiCandidates?: Record<string, any>[];
  aiSkipped?: Record<string, any>[];
}

const props = withDefaults(defineProps<Props>(), {
  providerStatuses: () => ({}),
  availableProviders: () => [],
  modelsByProvider: () => ({}),
  aiAccounts: () => [],
  aiRouting: () => ({}),
  aiCandidates: () => [],
  aiSkipped: () => [],
});

const selectedProvider = ref(props.availableProviders?.[0] || 'gemini');
const isTesting = ref(false);
const testResult = ref<string | null>(null);
const testResultType = ref<'success' | 'error'>('success');

// Track active provider and selected model locally — syncs with backend after save
const initialActive = Object.entries(props.providerStatuses).find(
  ([, s]) => s?.is_active_provider,
);
const localActiveProvider = ref(initialActive?.[0] ?? (props.availableProviders?.[0] ?? ''));
const localStatuses = reactive<Record<string, any>>({});

// Form state per provider yang sedang aktif
const form = ref({
  provider: selectedProvider.value,
  api_key: '',
  selected_model: '',
  is_active_provider: false,
});

const saving = ref(false);


// ── Helpers ───────────────────────────────────────────────────────

// Provider status: local override > props from server
function getProviderStatus(provider: string) {
  return localStatuses[provider] ?? props.providerStatuses?.[provider];
}

const currentStatus = computed(() => {
  return getProviderStatus(selectedProvider.value)?.status || 'Not Configured';
});

const currentModels = computed(() => props.modelsByProvider?.[selectedProvider.value] || []);

// Apakah form berbeda dari keadaan saat ini (server)?
const hasChanges = computed(() => {
  const status = getProviderStatus(form.value.provider);
  if (!status) return true;
  return form.value.selected_model !== (status.selected_model ?? '')
    || form.value.is_active_provider !== (status.is_active_provider ?? false);
});

const syncFormWithProvider = (provider: string) => {
  const config = getProviderStatus(provider);
  const models = props.modelsByProvider?.[provider] || [];

  form.value.provider           = provider;
  form.value.api_key            = '';
  form.value.selected_model     = config?.selected_model || models[0] || '';
  form.value.is_active_provider = provider === localActiveProvider.value;
testResult.value              = null;
};

const handleProviderChange = (provider: string) => {
  if (saving.value) return;
  selectedProvider.value = provider;
};

// Sync form setiap kali provider berubah
watch(selectedProvider, (newProvider) => {
  syncFormWithProvider(newProvider);
}, { immediate: true });

// ── Actions ───────────────────────────────────────────────────────

const testConnection = async () => {
  isTesting.value = true;
  testResult.value = null;
  try {
    const response = await axios.post(route('settings.ai.test'), {
      provider: selectedProvider.value,
    });
    testResult.value = response.data.message || t('settings.ai.models.test_success');
    testResultType.value = 'success';
    localStatuses[selectedProvider.value] = {
      ...getProviderStatus(selectedProvider.value),
      status: 'Connected',
    };
  } catch (error: any) {
    testResult.value = error.response?.data?.message || t('settings.ai.models.test_failed');
    testResultType.value = 'error';
    localStatuses[selectedProvider.value] = {
      ...getProviderStatus(selectedProvider.value),
      status: 'Failed',
    };
  } finally {
    isTesting.value = false;
  }
};

const saveSettings = async () => {
  if (saving.value || !hasChanges.value) return;

  const prevStatus = { ...getProviderStatus(form.value.provider) };

  saving.value = true;
  try {
    const { data } = await axios.patch(route('settings.ai.store'), {
      provider:             form.value.provider,
      api_key:              form.value.api_key || null,
      selected_model:       form.value.selected_model,
      is_active_provider:   form.value.is_active_provider,
    });

    showToast(data.message || t('toast.updated'), 'success');

    // Sync local state with server response
    localStatuses[form.value.provider] = {
      ...getProviderStatus(form.value.provider),
      selected_model: data.selected_model,
      is_active_provider: data.is_active_provider,
    };

    if (data.is_active_provider) {
      // Deactivate all other providers in local state
      for (const key in localStatuses) {
        if (key !== form.value.provider) {
          localStatuses[key] = { ...localStatuses[key], is_active_provider: false };
        }
      }
      localActiveProvider.value = form.value.provider;
    }

    // Reset API key field setelah simpan (keamanan — jangan tampilkan key)
    form.value.api_key = '';
  } catch (err: any) {
    // Rollback — restore to previous state
    localStatuses[form.value.provider] = prevStatus;
    showToast(err.response?.data?.message || t('errors.generic'), 'error');
  } finally {
    saving.value = false;
  }
};

// ── Provider pool: multi-akun + routing (tanpa pernah tampilkan secret) ──
const poolAccounts = ref<Record<string, any>[]>([...(props.aiAccounts || [])]);
const poolCandidates = ref<Record<string, any>[]>([...(props.aiCandidates || [])]);
const poolSkipped = ref<Record<string, any>[]>([...(props.aiSkipped || [])]);
const newAccount = ref({ provider: 'gemini', alias: '', api_key: '', base_url: '', priority: 1 });
const addingAccount = ref(false);
const routingForm = ref({
  mode: (props.aiRouting as any)?.mode || 'automatic',
  primary_provider: (props.aiRouting as any)?.primary_provider || '',
  primary_model: (props.aiRouting as any)?.primary_model || '',
  free_policy: (props.aiRouting as any)?.free_policy || 'free_first',
  allow_paid_fallback: !!(props.aiRouting as any)?.allow_paid_fallback,
});
const savingRouting = ref(false);
const busyAccountId = ref<number | null>(null);

async function refreshPool() {
  try {
    const { data } = await axios.get(route('settings.ai.accounts.index'));
    poolAccounts.value = data.accounts || [];
    poolCandidates.value = (data.candidates || []).slice(0, 8);
    poolSkipped.value = (data.skipped || []).slice(0, 20);
    if (data.routing) {
      routingForm.value.mode = data.routing.mode || 'automatic';
      routingForm.value.primary_provider = data.routing.primary_provider || '';
      routingForm.value.primary_model = data.routing.primary_model || '';
      routingForm.value.free_policy = data.routing.free_policy || 'free_first';
      routingForm.value.allow_paid_fallback = !!data.routing.allow_paid_fallback;
    }
  } catch (err: any) {
    showToast(err.response?.data?.message || 'Gagal memuat akun AI.', 'error');
  }
}

async function addAccount() {
  if (addingAccount.value || !newAccount.value.api_key) return;
  addingAccount.value = true;
  try {
    await axios.post(route('settings.ai.accounts.store'), { ...newAccount.value });
    showToast('Akun AI ditambahkan. Discovery model berjalan otomatis.', 'success');
    newAccount.value = { provider: 'gemini', alias: '', api_key: '', base_url: '', priority: 1 };
    await refreshPool();
  } catch (err: any) {
    showToast(err.response?.data?.message || 'Gagal menambah akun.', 'error');
  } finally {
    addingAccount.value = false;
  }
}

async function testAccount(id: number) {
  busyAccountId.value = id;
  try {
    const { data } = await axios.post(route('settings.ai.accounts.test', { id }));
    showToast(data.message || 'Koneksi berhasil.', 'success');
    await refreshPool();
  } catch (err: any) {
    showToast(err.response?.data?.message || 'Koneksi gagal.', 'error');
    await refreshPool();
  } finally {
    busyAccountId.value = null;
  }
}

async function refreshDiscovery(id: number) {
  busyAccountId.value = id;
  try {
    const { data } = await axios.post(route('settings.ai.accounts.refresh', { id }));
    showToast(data.discovery?.error || `Discovery: ${data.discovery?.source} (${data.discovery?.eligible} layak).`, data.discovery?.source === 'error' ? 'error' : 'success');
    await refreshPool();
  } catch (err: any) {
    showToast(err.response?.data?.message || 'Discovery gagal.', 'error');
  } finally {
    busyAccountId.value = null;
  }
}

async function toggleAccountActive(acc: Record<string, any>) {
  try {
    await axios.patch(route('settings.ai.accounts.update', { id: acc.id }), { is_active: !acc.is_active });
    await refreshPool();
  } catch (err: any) {
    showToast(err.response?.data?.message || 'Gagal mengubah status akun.', 'error');
  }
}

async function deleteAccount(id: number) {
  if (!confirm('Hapus akun API ini? Katalog modelnya ikut terhapus.')) return;
  try {
    await axios.delete(route('settings.ai.accounts.destroy', { id }));
    showToast('Akun dihapus.', 'success');
    await refreshPool();
  } catch (err: any) {
    showToast(err.response?.data?.message || 'Gagal menghapus akun.', 'error');
  }
}

async function saveRouting() {
  if (savingRouting.value) return;
  savingRouting.value = true;
  try {
    const payload: Record<string, any> = {
      mode: routingForm.value.mode,
      primary_provider: routingForm.value.primary_provider || null,
      primary_model: routingForm.value.primary_model || null,
      fallback_models: [],
      provider_priority: ['gemini', 'openai', 'deepseek', 'openai-compatible'],
      free_policy: routingForm.value.free_policy,
      allow_paid_fallback: routingForm.value.allow_paid_fallback,
    };
    const { data } = await axios.put(route('settings.ai.routing.store'), payload);
    showToast('Preferensi routing disimpan.', 'success');
    if (data.warnings?.length) showToast(data.warnings[0], 'error');
    await refreshPool();
  } catch (err: any) {
    showToast(err.response?.data?.message || 'Gagal menyimpan routing.', 'error');
  } finally {
    savingRouting.value = false;
  }
}
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.ai.models.title')" />

    <SettingsLayout
      :title="t('settings.ai.models.title')"
      :description="t('settings.ai.models.description')"
    >
      <!-- Provider Selection -->
      <SettingsCard
        :title="t('settings.ai.models.provider.label')"
        :description="t('settings.ai.models.provider.description')"
      >
        <div class="space-y-3">
          <!-- Provider Tabs -->
          <div class="-mx-4 sm:mx-0 overflow-x-auto">
            <div class="flex gap-2 px-4 sm:px-0 whitespace-nowrap">
              <button
                v-for="provider in availableProviders"
                :key="provider"
                @click="handleProviderChange(provider)"
                :class="[
                  'inline-flex items-center justify-center px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg font-semibold text-xs sm:text-sm transition-all select-none',
                  selectedProvider === provider
                    ? 'bg-[var(--color-brand)] text-[var(--color-text-primary)] shadow-md'
                    : 'bg-[var(--color-surface-muted)] text-gray-300 hover:bg-[var(--color-surface-muted)]',
                ]"
              >
                <span
                  v-if="getProviderStatus(provider)?.is_active_provider"
                  class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-emerald-400 shrink-0 mr-1.5 sm:mr-2"
                />
                {{ provider.toUpperCase() }}
              </button>
            </div>
          </div>

          <!-- Status Indicator -->
          <div class="flex items-center gap-2 p-2.5 sm:p-3 bg-[var(--color-surface-raised)] rounded-lg border border-white/5">
            <span
              :class="[
                'w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full shrink-0',
                currentStatus === 'Connected' ? 'bg-emerald-500' : 'bg-gray-500',
              ]"
            />
            <span class="text-xs sm:text-sm text-gray-300">
              {{ t('settings.ai.models.status') }}:
              <span :class="currentStatus === 'Connected' ? 'text-emerald-400' : 'text-[var(--color-text-secondary)]'">
                {{ currentStatus }}
              </span>
            </span>

            <!-- Test Connection -->
            <button
              @click="testConnection"
              :disabled="isTesting"
              class="ml-auto px-2.5 py-1 sm:px-3 sm:py-1.5 text-2xs sm:text-xs bg-[var(--color-surface-muted)] hover:bg-gray-600 text-[var(--color-text-primary)] rounded-lg disabled:opacity-50 transition-colors font-semibold"
            >
              {{ isTesting ? t('settings.ai.models.testing') : t('settings.ai.models.test_button') }}
            </button>
          </div>

          <!-- Test Result -->
          <div
            v-if="testResult"
            :class="[
              'p-2.5 sm:p-3 rounded-lg text-xs sm:text-sm border',
              testResultType === 'success'
                ? 'bg-emerald-900/30 text-emerald-300 border-emerald-500/30'
                : 'bg-red-900/30 text-red-300 border-red-500/30',
            ]"
          >
            {{ testResult }}
          </div>
        </div>
      </SettingsCard>

      <!-- Model Selection -->
      <SettingsCard
        :title="t('settings.ai.models.model.label')"
        :description="t('settings.ai.models.model.description')"
      >
        <select
          v-model="form.selected_model"
          class="w-full px-3 py-1.5 sm:px-4 sm:py-2.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg sm:rounded-xl text-[var(--color-text-primary)] focus:outline-none focus:border-purple-500 transition-all text-sm"
        >
          <option value="">{{ t('settings.ai.models.select_model') }}</option>
          <option v-for="model in currentModels" :key="model" :value="model">
            {{ model }}
          </option>
        </select>
        <p class="mt-2 text-2xs text-[var(--color-text-secondary)]">{{ t('settings.ai.models.model.hint') }}</p>
      </SettingsCard>

      <!-- API Key -->
      <SettingsCard
        :title="t('settings.ai.models.api_key.label')"
        :description="t('settings.ai.models.api_key.description')"
      >
        <input
          v-model="form.api_key"
          type="password"
          :placeholder="t('settings.ai.models.api_key.placeholder')"
          autocomplete="new-password"
          class="w-full px-3 py-1.5 sm:px-4 sm:py-2.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg sm:rounded-xl text-[var(--color-text-primary)] placeholder-gray-500 focus:outline-none focus:border-purple-500 transition-all text-sm"
        />
        <p class="mt-2 text-2xs text-[var(--color-text-secondary)]">
          {{ t('settings.ai.models.api_key.warning') }}
        </p>
      </SettingsCard>

      <!-- Set as Active Provider -->
      <SettingsCard
        :title="t('settings.ai.models.set_active')"
        :description="t('settings.ai.models.set_active_desc')"
      >
        <label class="flex items-center gap-3 cursor-pointer select-none">
          <div class="relative">
            <input
              v-model="form.is_active_provider"
              type="checkbox"
              class="sr-only peer"
            />
            <div class="w-9 h-5 sm:w-10 sm:h-6 bg-[var(--color-surface-muted)] rounded-full peer peer-checked:bg-[var(--color-brand)] transition-colors" />
            <div class="absolute top-0.5 sm:top-1 left-0.5 sm:left-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4 sm:peer-checked:translate-x-4" />
          </div>
          <span class="text-xs sm:text-sm text-gray-300">
            {{ t('settings.ai.models.provider_toggle') }}
          </span>
        </label>
      </SettingsCard>

      <!-- Help Text -->
      <div class="p-3 sm:p-4 bg-blue-900/20 border border-blue-500/20 rounded-lg">
        <p class="text-xs sm:text-sm text-blue-400">
          {{ t('settings.ai.models.help_text') }}
        </p>
      </div>

      <!-- Save -->
      <div class="flex items-center gap-3 pt-1 sm:pt-2">
        <button
          @click="saveSettings"
          :disabled="saving || !hasChanges"
          class="px-4 py-2 sm:px-5 sm:py-3 bg-[var(--color-brand)] hover:bg-[var(--color-brand-hover)] disabled:bg-[var(--color-surface-muted)] disabled:cursor-not-allowed text-[var(--color-text-primary)] rounded-lg sm:rounded-xl text-xs font-bold uppercase tracking-wider transition-all inline-flex items-center gap-2 shadow-lg shadow-purple-900/20"
        >
          <svg
            v-if="saving"
            class="animate-spin w-3.5 h-3.5"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
          </svg>
          {{ saving ? t('common.saving') : t('common.save') }}
        </button>
        <span v-if="!hasChanges && !saving" class="text-2xs text-emerald-400 font-semibold">
          ✓ {{ t('settings.ai.models.active') }}
        </span>
      </div>

      <!-- Provider pool: multi-akun -->
      <SettingsCard
        title="Akun API per provider (multi-key)"
        description="Satu provider bisa punya beberapa API key. Status jujur berdasarkan pemeriksaan nyata, bukan label. Key tidak pernah ditampilkan utuh."
      >
        <div class="space-y-3">
          <div v-if="!poolAccounts.length" class="text-xs text-[var(--color-text-secondary)]">
            Belum ada akun pool. Tambahkan di bawah atau gunakan pengaturan lama di atas (tetap kompatibel).
          </div>
          <div v-for="acc in poolAccounts" :key="acc.id" class="p-3 rounded-lg border border-white/5 bg-[var(--color-surface-raised)]">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-xs font-bold text-[var(--color-text-primary)]">{{ String(acc.provider).toUpperCase() }} · {{ acc.alias }}</span>
              <span class="text-2xs px-2 py-0.5 rounded-full bg-[var(--color-surface-muted)] text-gray-300">···{{ acc.key_suffix }}</span>
              <span class="text-2xs px-2 py-0.5 rounded-full" :class="acc.health_status === 'healthy' ? 'bg-emerald-900/30 text-emerald-300' : acc.health_status === 'invalid' ? 'bg-red-900/30 text-red-300' : 'bg-[var(--color-surface-muted)] text-gray-300'">
                {{ acc.health_status }}{{ acc.in_cooldown ? ' · cooldown' : '' }}
              </span>
              <span v-if="!acc.is_active" class="text-2xs px-2 py-0.5 rounded-full bg-[var(--color-surface-muted)] text-gray-400">nonaktif</span>
              <span class="ml-auto flex gap-2">
                <button @click="testAccount(acc.id)" :disabled="busyAccountId === acc.id || acc.id === 0" class="text-2xs px-2 py-1 rounded-lg bg-[var(--color-surface-muted)] hover:bg-gray-600 disabled:opacity-50">Uji</button>
                <button @click="refreshDiscovery(acc.id)" :disabled="busyAccountId === acc.id || acc.id === 0" class="text-2xs px-2 py-1 rounded-lg bg-[var(--color-surface-muted)] hover:bg-gray-600 disabled:opacity-50">Refresh model</button>
                <button v-if="acc.id !== 0" @click="toggleAccountActive(acc)" class="text-2xs px-2 py-1 rounded-lg bg-[var(--color-surface-muted)] hover:bg-gray-600">{{ acc.is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                <button v-if="acc.id !== 0" @click="deleteAccount(acc.id)" class="text-2xs px-2 py-1 rounded-lg bg-red-900/30 text-red-300 hover:bg-red-900/50">Hapus</button>
              </span>
            </div>
            <div class="mt-1 text-2xs text-[var(--color-text-secondary)]">
              Prioritas {{ acc.priority }} · {{ acc.model_count }} model · {{ acc.last_test_status || 'belum diuji' }}{{ acc.last_discovery_at ? ' · discovery ' + acc.last_discovery_at : '' }}
              <span v-if="acc.last_test_message"> · {{ acc.last_test_message }}</span>
            </div>
          </div>

          <div class="p-3 rounded-lg border border-dashed border-white/10 space-y-2">
            <div class="text-xs font-semibold text-[var(--color-text-primary)]">Tambah akun baru</div>
            <div class="grid grid-cols-2 gap-2">
              <select v-model="newAccount.provider" class="px-3 py-2 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg text-sm">
                <option value="gemini">gemini</option>
                <option value="openai">openai</option>
                <option value="deepseek">deepseek</option>
                <option value="openai-compatible">openai-compatible</option>
              </select>
              <input v-model="newAccount.alias" placeholder="Alias (mis. Akun 2)" class="px-3 py-2 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg text-sm" />
            </div>
            <input v-model="newAccount.api_key" type="password" placeholder="Tempel API key (tidak ditampilkan lagi setelah disimpan)" autocomplete="new-password" class="w-full px-3 py-2 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg text-sm" />
            <input v-if="newAccount.provider === 'openai-compatible'" v-model="newAccount.base_url" placeholder="Base URL (mis. https://api.contoh.com/v1/chat/completions)" class="w-full px-3 py-2 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg text-sm" />
            <button @click="addAccount" :disabled="addingAccount || !newAccount.api_key" class="px-3 py-2 bg-[var(--color-brand)] rounded-lg text-xs font-bold uppercase tracking-wider disabled:opacity-50">Tambah & discovery otomatis</button>
          </div>
        </div>
      </SettingsCard>

      <!-- Routing & kandidat -->
      <SettingsCard
        title="Routing cerdas & kandidat utama"
        description="Otomatis menyusun 3–4 kandidat dari model ter-discovery. Manual tidak diubah diam-diam saat mode otomatis mati."
      >
        <div class="space-y-3">
          <div class="grid grid-cols-2 gap-2">
            <label class="text-xs text-gray-300">Mode
              <select v-model="routingForm.mode" class="mt-1 w-full px-3 py-2 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg text-sm">
                <option value="automatic">Otomatis</option>
                <option value="manual">Manual</option>
              </select>
            </label>
            <label class="text-xs text-gray-300">Kebijakan biaya
              <select v-model="routingForm.free_policy" class="mt-1 w-full px-3 py-2 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg text-sm">
                <option value="free_only">Hanya gratis</option>
                <option value="free_first">Gratis dulu</option>
                <option value="paid_allowed">Berbayar diizinkan</option>
              </select>
            </label>
          </div>
          <label class="flex items-center gap-2 text-xs text-gray-300">
            <input v-model="routingForm.allow_paid_fallback" type="checkbox" class="w-4 h-4" />
            Izinkan fallback berbayar (wajib untuk free-first → berbayar & paid-allowed)
          </label>
          <div v-if="routingForm.mode === 'manual'" class="grid grid-cols-2 gap-2">
            <input v-model="routingForm.primary_provider" placeholder="provider utama (gemini/openai/...)" class="px-3 py-2 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg text-sm" />
            <input v-model="routingForm.primary_model" placeholder="model utama (ID resmi)" class="px-3 py-2 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg text-sm" />
          </div>
          <button @click="saveRouting" :disabled="savingRouting" class="px-3 py-2 bg-[var(--color-brand)] rounded-lg text-xs font-bold uppercase tracking-wider disabled:opacity-50">Simpan routing</button>

          <div>
            <div class="text-xs font-semibold text-[var(--color-text-primary)] mb-1">Kandidat utama (maks 8 ditampilkan)</div>
            <div v-if="!poolCandidates.length" class="text-2xs text-[var(--color-text-secondary)]">Belum ada kandidat layak. Cek akun/cooldown/kebijakan gratis.</div>
            <div v-for="(c, i) in poolCandidates" :key="i" class="text-2xs text-gray-300 py-1 border-b border-white/5">
              {{ i + 1 }}. {{ c.provider }}/{{ c.model }} <span class="text-gray-500">· akun #{{ c.account_id }} {{ c.account_alias }} · {{ c.source }}</span>
            </div>
          </div>
          <div v-if="poolSkipped.length">
            <div class="text-xs font-semibold text-[var(--color-text-primary)] mb-1">Dilewati (alasan jujur)</div>
            <div v-for="(s, i) in poolSkipped.slice(0, 8)" :key="i" class="text-2xs text-[var(--color-text-secondary)] py-0.5">
              {{ s.provider }}/{{ s.model }} — {{ s.reason }}
            </div>
          </div>
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
