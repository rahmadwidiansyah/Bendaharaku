# STATUS IMPLEMENTASI i18n — Bendaharaku
Generated: 2026-07-18

## KONTEKS PROYEK

- Stack: Laravel + Vue 3 + Inertia.js + Tailwind CSS v4
- Path: `/home/widi/Belajar/laravel/Bendaharaku`
- Library: `vue-i18n` v11 (sudah terinstall)

---

## ARSITEKTUR i18n (SUDAH SELESAI)

### File yang sudah dibuat:

| File | Status |
|------|--------|
| `resources/js/i18n/index.js` | ✅ Selesai — setup createI18n, resolveInitialLocale() |
| `resources/js/i18n/locales/id.js` | ✅ Selesai — translation Bahasa Indonesia lengkap |
| `resources/js/i18n/locales/en.js` | ✅ Selesai — translation English lengkap |
| `resources/js/Composables/useLocale.js` | ✅ Selesai — setLocale(), device detection, localStorage |
| `resources/js/app.js` | ✅ Selesai — `.use(i18n)` sudah ditambahkan |

### Struktur translation key (`id.js` dan `en.js`):
```
common, types, nav, header, dashboard, portfolio, transaction,
wallet, category, loan, analytics, settings, profile,
upcomingDebts, insight, ai, errors, validation, toast, empty, btn
```

### Cara pakai di komponen:
```vue
<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
// Di script: t('types.income')
// Di template: $t('types.income')
</script>
```

### Cara ganti bahasa (di Settings):
```vue
import { useLocale } from '@/Composables/useLocale.js'
const { currentPreference, setLocale } = useLocale()
// setLocale('id') | setLocale('en') | setLocale('auto')
```

---

## KOMPONEN & PAGES — STATUS DETAIL

### ✅ SUDAH SELESAI (sudah pakai useI18n / $t)

| File | Catatan |
|------|---------|
| `Components/BottomNav.vue` | Semua label nav sudah $t() |
| `Components/MobileHeader.vue` | routeTitleMap sudah computed + t() |
| `Pages/Dashboard.vue` | getTypeName(), calendarDayNames computed, semua string |
| `Pages/Analytics/Index.vue` | Chart.js labels, tab buttons, semua string |
| `Pages/Loans/Index.vue` | Semua string termasuk tab Hutang/Piutang |
| `Pages/Wallets/Index.vue` | Semua string |
| `Pages/Wallets/Create.vue` | Form labels, placeholders, buttons |
| `Pages/Wallets/Edit.vue` | Form labels, confirm() dialog |
| `Pages/Categories/Create.vue` | Form labels, placeholders, buttons |
| `Pages/Categories/Edit.vue` | Form labels, confirm() dialog |
| `Pages/Categories/Index.vue` | ⚠️ PARTIAL — import useI18n sudah ada, getHeaderText() sudah t(), tapi HEAD title dan beberapa string di template masih hardcoded |
| `Pages/Settings/Index.vue` | ⚠️ PARTIAL — import useI18n sudah ada, tapi Language section belum ditambahkan |
| `Pages/Dashboard/InsightBanner.vue` | Sudah t() |
| `Pages/Dashboard/UpcomingDebts.vue` | Sudah t() |
| `Pages/Dashboard/PortfolioCard.vue` | Sudah t() |

---

### ❌ BELUM DIKERJAKAN (prioritas tinggi)

#### 1. `Pages/Categories/Index.vue` — PARTIAL, perlu dilengkapi
String hardcoded yang masih tersisa di template:
- `<Head title="Vault Kategori" />` → `<Head :title="$t('category.title')" />`
- `Collection` → `$t('category.collection')`
- `Kategori` (di h1 span) → `$t('nav.label')`
- `Total` (label) → `$t('category.totalLabel')`
- `Tambah Kategori` (link text) → `$t('category.addNew')`
- `Buat kategori baru` → `$t('category.titleCreate')`
- `Kategori Masih Kosong` → `$t('empty.category')`
- `Buat kategori pertamamu sekarang...` → `$t('empty.categoryMsg')`

#### 2. `Pages/Settings/Index.vue` — PARTIAL, perlu tambah Language section
Perlu tambahkan **section baru** setelah section Tampilan, berisi radio buttons:
- Auto (ikuti device)
- Bahasa Indonesia
- English

Gunakan `useLocale()` dari `@/Composables/useLocale.js`:
```vue
import { useLocale } from '@/Composables/useLocale.js'
const { currentPreference, setLocale } = useLocale()
```

HTML structure yang harus ditambahkan (taruh setelah section Tampilan, sebelum section AI):
```vue
<section>
  <div class="flex items-center gap-3 mb-4">
    <h2 class="text-2xs font-black text-gray-400 uppercase tracking-[0.2em]">{{ $t('settings.language') }}</h2>
    <div class="flex-1 h-px bg-white/5"></div>
  </div>
  <div class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl p-5 space-y-3">
    <p class="text-sm font-bold text-white mb-4">{{ $t('settings.lang.title') }}</p>
    <!-- Radio: auto -->
    <label class="flex items-center gap-4 cursor-pointer group">
      <div class="relative">
        <input type="radio" name="language" value="auto" :checked="currentPreference === 'auto'" @change="setLocale('auto')" class="sr-only" />
        <div :class="['w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors', currentPreference === 'auto' ? 'border-purple-500 bg-purple-500/20' : 'border-gray-600 group-hover:border-gray-400']">
          <div v-if="currentPreference === 'auto'" class="w-2.5 h-2.5 rounded-full bg-purple-400"></div>
        </div>
      </div>
      <div>
        <p class="text-sm font-bold text-white">{{ $t('settings.lang.auto') }}</p>
        <p class="text-2xs text-gray-500">{{ $t('settings.lang.autoDesc') }}</p>
      </div>
      <span v-if="currentPreference === 'auto'" class="ml-auto text-2xs font-bold text-purple-400 uppercase tracking-widest">{{ $t('settings.lang.current') }}</span>
    </label>
    <div class="border-t border-white/5"></div>
    <!-- Radio: Bahasa Indonesia -->
    <label class="flex items-center gap-4 cursor-pointer group">
      <div class="relative">
        <input type="radio" name="language" value="id" :checked="currentPreference === 'id'" @change="setLocale('id')" class="sr-only" />
        <div :class="['w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors', currentPreference === 'id' ? 'border-purple-500 bg-purple-500/20' : 'border-gray-600 group-hover:border-gray-400']">
          <div v-if="currentPreference === 'id'" class="w-2.5 h-2.5 rounded-full bg-purple-400"></div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-lg">🇮🇩</span>
        <p class="text-sm font-bold text-white">{{ $t('settings.lang.id') }}</p>
      </div>
      <span v-if="currentPreference === 'id'" class="ml-auto text-2xs font-bold text-purple-400 uppercase tracking-widest">{{ $t('settings.lang.current') }}</span>
    </label>
    <!-- Radio: English -->
    <label class="flex items-center gap-4 cursor-pointer group">
      <div class="relative">
        <input type="radio" name="language" value="en" :checked="currentPreference === 'en'" @change="setLocale('en')" class="sr-only" />
        <div :class="['w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors', currentPreference === 'en' ? 'border-purple-500 bg-purple-500/20' : 'border-gray-600 group-hover:border-gray-400']">
          <div v-if="currentPreference === 'en'" class="w-2.5 h-2.5 rounded-full bg-purple-400"></div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-lg">🇺🇸</span>
        <p class="text-sm font-bold text-white">{{ $t('settings.lang.en') }}</p>
      </div>
      <span v-if="currentPreference === 'en'" class="ml-auto text-2xs font-bold text-purple-400 uppercase tracking-widest">{{ $t('settings.lang.current') }}</span>
    </label>
  </div>
</section>
```

#### 3. `Components/TransactionDetailModal.vue` — BELUM
String hardcoded yang perlu diganti:
- `'Draft'` (badge) → `$t('transaction.draft')`
- `'Nominal'` → `$t('transaction.amount')`
- `'Tanggal'` → `$t('transaction.detail.date')`
- `'Dompet'` → `$t('wallet.title')` atau label singkat
- `'Pelaku'` → `$t('transaction.detail.loanSubject')`
- `'Jatuh Tempo'` → `$t('transaction.detail.dueDate')`
- `'Catatan'` → `$t('transaction.detail.note')`
- `'Tidak ada catatan.'` → `$t('common.none')`
- `'Konfirmasi Transaksi'` (button) → `$t('transaction.confirmDraft')`
- `'Edit'` (button) → `$t('transaction.detail.editBtn')`
- `'Hapus'` (button) → `$t('transaction.detail.deleteBtn')`
- `'Konfirmasi Transaksi?'` (dialog h3) → `$t('transaction.confirmDraftQ')`
- `'Apakah data transaksi ini sudah benar?'` → custom text, bisa buat key baru
- `'Draft'` → `$t('transaction.draft')`, `'Terkonfirmasi'` → `$t('transaction.confirmed')`
- `'Batal'` → `$t('btn.no')`
- `'Ya, Konfirmasi'` → ubah ke `$t('common.confirm')`
- `'Memproses...'` → `$t('common.loading')`
- `'Hapus Transaksi?'` (dialog h3) → `$t('transaction.deleteTitle')`
- `'Data yang dihapus tidak bisa dikembalikan.'` → `$t('transaction.deleteMsg')`
- `'Batal'` → `$t('btn.no')`
- `'Menghapus...'` → `$t('btn.deleting')`
- `'Ya, Hapus'` → `$t('btn.yes')`
- Tipe transaksi di Badge masih raw (`transaction.type?.name`) — perlu type name map sama seperti di Dashboard

Tambahkan di script:
```js
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
const getTypeName = (name) => ({
  Income: t('types.income'), Expense: t('types.expense'),
  Transfer: t('types.transfer'), Debt: t('types.debt'),
  Receivable: t('types.receivable'),
}[name] ?? name)
```
Dan di Badge: `{{ getTypeName(transaction.type?.name) }}`

#### 4. `Components/TransactionBottomSheet.vue` — BELUM
String hardcoded kritis:
- Array tipe transaksi di baris ~96-100:
  ```js
  // Sekarang:
  { id: 'Income', label: 'Pemasukan', ... },
  { id: 'Expense', label: 'Pengeluaran', ... },
  // Harus jadi computed:
  const transactionTypes = computed(() => [
    { id: 'Income',     label: t('types.income'),     icon: '📥', color: '...' },
    { id: 'Expense',    label: t('types.expense'),    icon: '📤', color: '...' },
    { id: 'Transfer',   label: t('types.transfer'),   icon: '🔄', color: '...' },
    { id: 'Debt',       label: t('types.debt'),       icon: '📊', color: '...' },
    { id: 'Receivable', label: t('types.receivable'), icon: '💰', color: '...' },
  ])
  ```
- `'Jenis Hutang'` / `'Jenis Piutang'` (baris ~391)
- `'Dapat Hutang'` / `'Terima Piutang'` (baris ~399)
- Semua label form, button, placeholder di dalam sheet

#### 5. `Pages/Transactions/Create.vue` — BELUM (file besar, ~1200 baris)
String kritis:
- Semua form labels, placeholders
- Type names (Income/Expense/Transfer/Debt/Receivable)
- Validation error messages
- Button texts (Simpan, Batal, Simpan sebagai Draft)
- `'Wajib diisi Bos!'` → `$t('transaction.validation.amountRequired')`
- `'Masa depan tidak diizinkan!'` → `$t('transaction.validation.dateFuture')`

#### 6. `Pages/Transactions/Edit.vue` — BELUM (file besar, ~1300 baris)
Sama persis dengan Create.vue, plus confirm() dialog sebelum hapus.

#### 7. `Pages/Categories/Show.vue` — BELUM
String hardcoded:
- `'Pemasukan'` / `'Pengeluaran'` di type badge (baris ~75): `category.type.name === 'Income' ? 'Pemasukan' : 'Pengeluaran'`
  → Harus pakai: `t('types.' + category.type.name.toLowerCase())`
- `'Total Periode'` → buat key baru atau gunakan `$t('common.total')`
- `'Riwayat Transaksi'` → `$t('category.show.transactions')`
- `'Record'` (counter) → `$t('category.transaction')`
- `'Tanpa catatan'` → `$t('common.none')`
- `trx.type.name` di badge masih raw English (Income/Expense/dll)
- `'Belum ada transaksi'` → `$t('category.show.noTransactions')`

#### 8. `Pages/Wallets/Show.vue` — BELUM
String hardcoded:
- `getTypeName()` masih hardcoded:
  ```js
  // Sekarang:
  { Income: 'Pemasukan', Expense: 'Pengeluaran', ... }
  // Harus:
  const getTypeName = (n) => t('types.' + n.toLowerCase()) ?? n
  ```
- `'Detail Dompet'` → `$t('wallet.titleEdit')`
- `'Edit Dompet'` → `$t('wallet.titleEdit')`
- `'Mutasi Terakhir'` → buat key `wallet.recentMutation: 'Mutasi Terakhir'` di locales
- `'Belum ada mutasi'` → buat key `wallet.emptyMutation: 'Belum ada mutasi'` di locales

  **Perlu tambah 2 key baru di id.js dan en.js:**
  ```js
  // id.js wallet section:
  recentMutation: 'Mutasi Terakhir',
  emptyMutation: 'Belum ada mutasi',
  // en.js wallet section:
  recentMutation: 'Recent Mutations',
  emptyMutation: 'No mutations yet',
  ```

#### 9. `Pages/Profile/Edit.vue` — BELUM
String kritis:
- Form labels: Nama, Email, Avatar, Password Saat Ini, Password Baru, dll
- Button: `'Perbarui Profil'`, `'Perbarui Password'`
- Delete confirm: `confirm('YAKIN HAPUS PERMANEN? ...')` → `confirm(t('profile.deleteAccountConfirm'))`
- Section titles

#### 10. `Pages/Settings/Ai.vue` — BELUM (~19000 chars)
String kritis:
- `'Pengaturan AI'` → `$t('ai.title')`
- `'Provider AI'` → `$t('ai.provider')`
- `'API Key'` → `$t('ai.apiKey')`
- `'Tes Koneksi'` → `$t('ai.testConnection')`
- `'Menguji...'` → `$t('ai.testing')`
- `'Simpan Pengaturan'` → `$t('ai.save')`
- Stats labels: Requests, Success, Drafts, Tokens, Final Confidence
  → `$t('ai.requests')`, `$t('ai.success')`, `$t('ai.drafts')`, `$t('ai.tokens')`, `$t('ai.finalConfidence')`

#### 11. `Pages/Settings/AiAnalytics.vue` — BELUM
String kritis:
- `'Analitik AI'` → `$t('ai.analyticsTitle')`
- Chart titles: `'Traffic by Provider'` → `$t('ai.charts.trafficByProvider')`, dll
- Section labels: Overview, Performance, Learning → `$t('ai.overview')`, dll
- Stat labels sama seperti Ai.vue

---

### ⚪ TIDAK PERLU i18n (props-only atau no UI strings)

| File | Alasan |
|------|--------|
| `Components/Badge.vue` | Props-only, tidak ada string hardcoded |
| `Components/Button.vue` | Slot-based, tidak ada string |
| `Components/Card.vue` | Layout wrapper |
| `Components/EmptyState.vue` | Props-only |
| `Components/FormLabel.vue` | Slot-based |
| `Components/NavItem.vue` | Props-only |
| `Components/TextInput.vue` | Props-based |
| `Components/Toast.vue` | Pesan dari server flash |
| `Components/BaseModal.vue` | Layout wrapper |
| `Components/Avatar.vue` | Image/initials only |
| `Components/SectionHeader.vue` | Slot-based |
| `Components/AmountKeypad.vue` | Angka/simbol |
| `Components/EmojiPicker.vue` | UI picker |
| `Components/ApplicationLogo.vue` | SVG |
| `Components/GoogleAd.vue` | Ad unit |
| `Components/Skeleton/*.vue` | Placeholder, no text |
| `Pages/Dashboard/DashboardHeader.vue` | **DEPRECATED** — tidak dipakai |
| `Pages/Auth/*.vue` | Auth pages, bisa skip atau dikerjakan terpisah |
| `Pages/Welcome.vue` | Landing page, bisa skip |

---

## TRANSLATION KEYS YANG PERLU DITAMBAH

Beberapa key belum ada di `id.js` dan `en.js`, perlu ditambahkan:

```js
// Tambahkan di wallet section:
wallet.recentMutation  // id: 'Mutasi Terakhir' / en: 'Recent Mutations'
wallet.emptyMutation   // id: 'Belum ada mutasi' / en: 'No mutations yet'

// Tambahkan di transaction section (untuk TransactionDetailModal):
transaction.detail.wallet     // id: 'Dompet' / en: 'Wallet'
transaction.detail.party      // id: 'Pelaku' / en: 'Party'
transaction.detail.noNote     // id: 'Tidak ada catatan.' / en: 'No notes.'
transaction.confirmDraftDetail // id: 'Apakah data transaksi ini sudah benar?' / en: 'Is this transaction data correct?'
transaction.confirmDraftWarn  // id: 'Status akan berubah dari Draft menjadi Terkonfirmasi dan saldo dompet akan dimutasi.' / en: 'Status will change from Draft to Confirmed and wallet balance will be updated.'
transaction.deleteWarn        // id: 'Data yang dihapus tidak bisa dikembalikan.' / en: 'Deleted data cannot be recovered.'

// Tambahkan di common section:
common.processing   // id: 'Memproses...' / en: 'Processing...'
common.period       // id: 'Periode' / en: 'Period'
```

---

## URUTAN PRIORITAS PENGERJAAN

1. **`Pages/Settings/Index.vue`** — tambahkan Language section (penting untuk UX)
2. **`Pages/Categories/Index.vue`** — selesaikan sisa hardcoded strings (partial)
3. **`Components/TransactionDetailModal.vue`** — dipakai di semua halaman
4. **`Components/TransactionBottomSheet.vue`** — form pencatatan utama (tipe transaksi!)
5. **`Pages/Transactions/Create.vue`** — form terbesar, banyak validasi
6. **`Pages/Transactions/Edit.vue`** — mirip Create
7. **`Pages/Categories/Show.vue`** — ada raw English type names
8. **`Pages/Wallets/Show.vue`** — ada getTypeName() hardcoded
9. **`Pages/Settings/Ai.vue`** — campur EN/ID
10. **`Pages/Settings/AiAnalytics.vue`** — campur EN
11. **`Pages/Profile/Edit.vue`** — confirm() dialog hardcoded

---

## CHECKLIST FINAL (setelah semua selesai)

- [ ] Build tanpa error: `npm run build`
- [ ] Switching bahasa di Settings → semua text berubah realtime tanpa reload
- [ ] Dashboard → tipe transaksi tampil dalam bahasa yang dipilih
- [ ] Analytics → tab dan chart labels berubah
- [ ] Loans → Hutang/Piutang tab berubah
- [ ] TransactionDetailModal → semua label berubah
- [ ] TransactionBottomSheet → tipe transaksi berubah
- [ ] Tidak ada string `'Pemasukan'`, `'Pengeluaran'`, `'Hutang'`, `'Piutang'`, `'Transfer'` hardcoded di template (boleh di logic/identifier)
- [ ] localStorage `locale` tersimpan saat pilih bahasa manual
- [ ] Reload halaman → bahasa tetap sesuai preferensi yang tersimpan
