<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { showToast } = useToast();

const formatDateTime = (iso: string) => {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  try {
    return d.toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });
  } catch {
    return iso;
  }
};

interface Props {
  aiAccounts?: Record<string, any>[];
  aiRouting?: Record<string, any>;
  aiCandidates?: Record<string, any>[];
  aiSkipped?: Record<string, any>[];
}

const props = withDefaults(defineProps<Props>(), {
  aiAccounts: () => [],
  aiRouting: () => ({}),
  aiCandidates: () => [],
  aiSkipped: () => [],
});

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
  fallback_models: ([...((props.aiRouting as any)?.fallback_models || [])] as Record<string, any>[]).slice(0, 3),
});
const accountModels = ref<Record<number, Record<string, any>[]>>({});
const loadingModelsFor = ref<number | null>(null);
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
      routingForm.value.fallback_models = ([...(data.routing.fallback_models || [])] as Record<string, any>[]).slice(0, 3);
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

function moveFallback(index: number, dir: -1 | 1) {
  const list = [...routingForm.value.fallback_models];
  const j = index + dir;
  if (j < 0 || j >= list.length) return;
  [list[index], list[j]] = [list[j], list[index]];
  routingForm.value.fallback_models = list;
}

function addFallbackRow() {
  if (routingForm.value.fallback_models.length >= 3) return;
  routingForm.value.fallback_models = [...routingForm.value.fallback_models, { provider: 'gemini', model: '' }];
}

function removeFallbackRow(index: number) {
  routingForm.value.fallback_models = routingForm.value.fallback_models.filter((_, i) => i !== index);
}

async function loadAccountModels(accId: number) {
  if (loadingModelsFor.value === accId) return;
  loadingModelsFor.value = accId;
  try {
    const { data } = await axios.get(route('settings.ai.accounts.models', { id: accId }));
    accountModels.value = { ...accountModels.value, [accId]: data.models || [] };
  } catch (err: any) {
    showToast(err.response?.data?.message || 'Gagal memuat model akun.', 'error');
  } finally {
    loadingModelsFor.value = null;
  }
}

async function toggleModelEnabled(accId: number, modelId: string, enable: boolean) {
  try {
    await axios.patch(route('settings.ai.accounts.models.toggle', { id: accId, model: modelId }), { is_enabled: enable });
    showToast(enable ? `Model ${modelId} diaktifkan kembali.` : `Model ${modelId} dinonaktifkan.`, 'success');
    await loadAccountModels(accId);
    await refreshPool();
  } catch (err: any) {
    showToast(err.response?.data?.message || 'Gagal mengubah status model.', 'error');
  }
}

async function saveRouting() {
  if (savingRouting.value) return;
  savingRouting.value = true;
  try {
    const fallbacks = routingForm.value.fallback_models
      .filter((f) => f?.provider && f?.model?.trim())
      .slice(0, 3)
      .map((f) => ({ provider: f.provider, model: f.model.trim() }));
    const payload: Record<string, any> = {
      mode: routingForm.value.mode,
      primary_provider: routingForm.value.primary_provider || null,
      primary_model: routingForm.value.primary_model || null,
      fallback_models: fallbacks,
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
      <!-- Provider pool: multi-akun -->
      <SettingsCard
        title="Akun API"
        description="Satu API key per akun. Status berdasarkan pemeriksaan nyata; key tidak pernah ditampilkan utuh."
      >
        <div class="space-y-3">
          <div v-if="!poolAccounts.length" class="text-xs text-[var(--color-text-secondary)]">
            Belum ada akun API. Tambahkan akun baru di bawah.
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
              Prioritas {{ acc.priority }} · {{ acc.model_count }} model · {{ acc.last_test_status || 'belum diuji' }}{{ acc.last_discovery_at ? ' · diperbarui ' + formatDateTime(acc.last_discovery_at) : '' }}
              <span v-if="acc.last_test_message"> · {{ acc.last_test_message }}</span>
            </div>
            <div v-if="acc.id !== 0" class="mt-2">
              <button @click="loadAccountModels(acc.id)" :disabled="loadingModelsFor === acc.id" class="text-2xs px-2 py-1 rounded-lg bg-[var(--color-surface-muted)] hover:bg-gray-600 disabled:opacity-50">
                {{ accountModels[acc.id] ? 'Muat ulang model' : 'Lihat model' }}
              </button>
              <div v-if="accountModels[acc.id]" class="mt-2 space-y-1 max-h-48 overflow-y-auto">
                <div v-for="m in accountModels[acc.id]" :key="m.model_id" class="flex items-center gap-2 text-2xs py-1 border-b border-white/5">
                  <span class="text-gray-300 font-semibold">{{ m.model_id }}</span>
                  <span class="text-gray-500">{{ m.free_tier }} · {{ m.category }}</span>
                  <span v-if="!m.is_enabled" class="px-1.5 py-0.5 rounded bg-red-900/30 text-red-300">nonaktif{{ m.last_error ? ' · ' + m.last_error : '' }}</span>
                  <span class="ml-auto">
                    <button v-if="!m.is_enabled" @click="toggleModelEnabled(acc.id, m.model_id, true)" class="px-2 py-0.5 rounded-lg bg-emerald-900/30 text-emerald-300 hover:bg-emerald-900/50">Aktifkan lagi</button>
                    <button v-else @click="toggleModelEnabled(acc.id, m.model_id, false)" class="px-2 py-0.5 rounded-lg bg-[var(--color-surface-muted)] hover:bg-gray-600">Nonaktifkan</button>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="p-3 rounded-lg border border-dashed border-white/10 space-y-2">
            <div class="text-xs font-semibold text-[var(--color-text-primary)]">Tambah akun</div>
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
            <button @click="addAccount" :disabled="addingAccount || !newAccount.api_key" class="px-3 py-2 bg-[var(--color-brand)] rounded-lg text-xs font-bold uppercase tracking-wider disabled:opacity-50">Tambah akun</button>
          </div>
        </div>
      </SettingsCard>

      <!-- Routing & kandidat -->
      <SettingsCard
        title="Routing"
        description="Otomatis menyusun kandidat dari model yang tersedia. Mode manual tidak diubah diam-diam."
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
          <div v-if="routingForm.mode === 'manual'" class="space-y-2">
            <div class="grid grid-cols-2 gap-2">
              <input v-model="routingForm.primary_provider" placeholder="provider utama (gemini/openai/...)" class="px-3 py-2 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg text-sm" />
              <input v-model="routingForm.primary_model" placeholder="model utama (ID resmi)" class="px-3 py-2 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg text-sm" />
            </div>
            <div class="text-xs font-semibold text-[var(--color-text-primary)]">Urutan fallback manual (maks 3, bisa digeser)</div>
            <div v-for="(fb, i) in routingForm.fallback_models" :key="i" class="grid grid-cols-[1fr_2fr_auto] gap-2 items-center">
              <select v-model="fb.provider" class="px-2 py-1.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg text-xs">
                <option value="gemini">gemini</option>
                <option value="openai">openai</option>
                <option value="deepseek">deepseek</option>
                <option value="openai-compatible">openai-compatible</option>
              </select>
              <input v-model="fb.model" placeholder="model ID resmi" class="px-2 py-1.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg text-xs" />
              <span class="flex gap-1">
                <button @click="moveFallback(i, -1)" :disabled="i === 0" class="px-1.5 py-1 rounded bg-[var(--color-surface-muted)] text-xs disabled:opacity-40">▲</button>
                <button @click="moveFallback(i, 1)" :disabled="i === routingForm.fallback_models.length - 1" class="px-1.5 py-1 rounded bg-[var(--color-surface-muted)] text-xs disabled:opacity-40">▼</button>
                <button @click="removeFallbackRow(i)" class="px-1.5 py-1 rounded bg-red-900/30 text-red-300 text-xs">✕</button>
              </span>
            </div>
            <button v-if="routingForm.fallback_models.length < 3" @click="addFallbackRow" class="text-2xs px-2 py-1 rounded-lg bg-[var(--color-surface-muted)] hover:bg-gray-600">+ Tambah fallback</button>
          </div>
          <button @click="saveRouting" :disabled="savingRouting" class="px-3 py-2 bg-[var(--color-brand)] rounded-lg text-xs font-bold uppercase tracking-wider disabled:opacity-50">Simpan routing</button>

          <div>
            <div class="text-xs font-semibold text-[var(--color-text-primary)] mb-1">Kandidat utama (maks 8)</div>
            <div v-if="!poolCandidates.length" class="text-2xs text-[var(--color-text-secondary)]">Belum ada kandidat layak. Cek akun/cooldown/kebijakan gratis.</div>
            <div v-for="(c, i) in poolCandidates" :key="i" class="text-2xs text-gray-300 py-1 border-b border-white/5">
              {{ i + 1 }}. {{ c.provider }}/{{ c.model }} <span class="text-gray-500"><template v-if="c.account_alias">· {{ c.account_alias }}</template> · {{ c.source }}</span>
            </div>
          </div>
          <div v-if="poolSkipped.length">
            <div class="text-xs font-semibold text-[var(--color-text-primary)] mb-1">Dilewati</div>
            <div v-for="(s, i) in poolSkipped.slice(0, 8)" :key="i" class="text-2xs text-[var(--color-text-secondary)] py-0.5">
              {{ s.provider }}/{{ s.model }} — {{ s.reason }}
            </div>
          </div>
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
