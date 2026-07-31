# AUDIT TOTAL - WIDTH ISSUES REPORT

## RINGKASAN

Ditemukan **49 hasil** dengan class `max-w-*` di file `.vue`, plus **57 hasil** dengan `mx-auto/container`, plus **27 hasil** dengan hardcoded `w-[...]`, plus **3 hasil** di `.blade.php`.

---

## KRITERIA KLASIFIKASI

| Level | Kriteria |
|-------|----------|
| **CRITICAL** | max-w-sm/md di layout utama / halaman desktop / reusable component → Memaksa tampilan mobile di desktop |
| **HIGH** | max-w-* di reusable component/modal/sheet → Harus responsive atau prop-based |
| **MEDIUM** | max-w-* di konten spesifik (auth, toast, bottom nav, FAB) → Bisa diterima tapi sebaiknya responsive |
| **LOW** | max-w-* di element kecil (badge, icon, decorative, text block) → Tidak masalah |

---

## FORMAT OUTPUT

Setiap baris memiliki 8 kolom:

1. File
2. Line
3. Component
4. Class
5. Nilai max-width
6. Apakah memang disengaja?
7. Apakah seharusnya responsive?
8. Apakah menyebabkan desktop tetap seperti mobile?

---

## CRITICAL - HARUS DIHAPUS

| # | File | Line | Component | Class | Nilai max-width | Disengaja? | Seharusnya responsive? | Desktop tetap mobile? |
|---|------|------|-----------|-------|-----------------|------------|------------------------|-----------------------|
| 1 | resources/js/Layouts/AuthenticatedLayout.vue | ~70 | Layout Root | `max-w-md mx-auto` | 448px | YA (mobile-first) | YA | **YA** - Seluruh app desktop terkunci 448px |
| 2 | resources/js/Pages/Chat/Index.vue | ~45 | Chat Container | `max-w-2xl mx-auto` | 672px | YA | YA | **YA** - Chat tidak memakai layar lebar |
| 3 | resources/js/Pages/Dashboard.vue | ~200 | Sort Modal | `max-w-sm` | 384px | Mungkin | YA | **YA** - Modal sort terkunci 384px |
| 4 | resources/js/Components/BaseModal.vue | Props | BaseModal (sm) | `max-w-sm` | 384px | YA (prop) | YA | **YA** - Default modal 384px |
| 5 | resources/js/Components/BaseModal.vue | Props | BaseModal (md) | `max-w-md` | 448px | YA (prop) | YA | **YA** - |
| 6 | resources/js/Components/BaseModal.vue | Props | BaseModal (lg) | `max-w-lg` | 512px | YA (prop) | YA | **YA** - |
| 7 | resources/js/Components/BaseModal.vue | Props | BaseModal (xl) | `max-w-xl` | 576px | YA (prop) | YA | **YA** - |
| 8 | resources/js/Pages/Transactions/Create.vue | ~50 | Type Selector | `max-w-sm` | 384px | YA | YA | **YA** - Form type terkunci 384px |
| 9 | resources/js/Pages/Transactions/Create.vue | ~120 | Date Modal | `max-w-md` | 448px | YA | YA | **YA** - Modal date terkunci 448px |
| 10 | resources/js/Pages/Transactions/Create.vue | ~150 | Wallet Modal | `max-w-md` | 448px | YA | YA | **YA** - Modal wallet terkunci 448px |
| 11 | resources/js/Pages/Transactions/Edit.vue | ~50 | Type Selector | `max-w-sm` | 384px | YA | YA | **YA** - Form type terkunci 384px |
| 12 | resources/js/Pages/Transactions/Edit.vue | ~120 | Date Modal | `max-w-md` | 448px | YA | YA | **YA** - Modal date terkunci 448px |
| 13 | resources/js/Pages/Transactions/Edit.vue | ~150 | Wallet Modal | `max-w-md` | 448px | YA | YA | **YA** - Modal wallet terkunci 448px |

---

## HIGH - SEBAIKNYA RESPONSIVE

| # | File | Line | Component | Class | Nilai max-width | Disengaja? | Seharusnya responsive? | Desktop tetap mobile? |
|---|------|------|-----------|-------|-----------------|------------|------------------------|-----------------------|
| 14 | resources/js/Components/Chat/ChatUploadSheet.vue | ~20 | Upload Sheet | `max-w-md` | 448px | YA | YA | **YA** - Sheet terkunci 448px |
| 15 | resources/js/Components/Chat/CommandSheet.vue | ~25 | Command Sheet | `max-w-md` | 448px | YA | YA | **YA** - Sheet terkunci 448px |
| 16 | resources/js/Components/Chat/EvidenceReviewSheet.vue | ~30 | Evidence Sheet | `max-w-lg` | 512px | YA | YA | **YA** - Sheet terkunci 512px |
| 17 | resources/js/Components/Chat/Messages/TransactionDetailModal.vue | ~15 | Transaction Modal | `sm:max-w-lg` | 512px | YA | YA | **YA** - Modal detail 512px max |
| 18 | resources/js/Components/ImageCropModal.vue | ~20 | Crop Modal | `sm:max-w-lg` | 512px | YA | YA | **YA** - Modal crop 512px max |
| 19 | resources/js/Components/Header/GlobalSearchOverlay.vue | ~30 | Search Overlay | `max-w-2xl` | 672px | YA | YA | **YA** - Overlay search terkunci 672px |
| 20 | resources/js/Pages/Search/Index.vue | ~20 | Search Container | `max-w-3xl` | 768px | YA | YA | **YA** - Search page terkunci 768px |
| 21 | resources/js/Pages/Settings/Layouts/SettingsLayout.vue | ~10 | Settings Container | `max-w-4xl` | 896px | YA | YA | **YA** - Settings terkunci 896px |
| 22 | resources/js/Pages/Settings/Privacy/Danger.vue | ~80 | Delete Modal | `max-w-sm` | 384px | Mungkin | YA | **YA** - Modal delete terkunci 384px |
| 23 | resources/js/Components/Toast.vue | ~20 | Toast Notification | `max-w-sm` | 384px | YA | YA | Partial - Toast notif, tapi bisa lebih lebar di desktop |
| 24 | resources/js/Components/BottomNav.vue | ~25 | Bottom Navigation | `max-w-sm` | 384px | YA | YA | Partial - Mobile nav, tapi desktop juga pakai jika tidak ada sidebar |
| 25 | resources/js/Components/CreateTransactionFab.vue | ~15 | FAB Container | `max-w-md` | 448px | YA | YA | Partial - FAB mobile, tapi terkunci di desktop |

---

## MEDIUM - OPTIONAL

| # | File | Line | Component | Class | Nilai max-width | Disengaja? | Seharusnya responsive? | Desktop tetap mobile? |
|---|------|------|-----------|-------|-----------------|------------|------------------------|-----------------------|
| 26 | resources/js/Pages/Auth/Login.vue | ~20 | Login Container | `max-w-md` | 448px | YA (mobile-first) | Optional | YA tapi by design - Auth page |
| 27 | resources/js/Pages/Auth/Register.vue | ~20 | Register Container | `max-w-md` | 448px | YA (mobile-first) | Optional | YA tapi by design - Auth page |
| 28 | resources/js/Pages/Auth/ForgotPassword.vue | ~20 | Forgot Container | `max-w-md` | 448px | YA (mobile-first) | Optional | YA tapi by design - Auth page |
| 29 | resources/js/Pages/Auth/ResetPassword.vue | ~20 | Reset Container | `max-w-md` | 448px | YA (mobile-first) | Optional | YA tapi by design - Auth page |
| 30 | resources/js/Pages/Auth/ConfirmPassword.vue | ~20 | Confirm Container | `max-w-md` | 448px | YA (mobile-first) | Optional | YA tapi by design - Auth page |
| 31 | resources/js/Pages/Auth/VerifyEmail.vue | ~20 | Verify Container | `max-w-md` | 448px | YA (mobile-first) | Optional | YA tapi by design - Auth page |
| 32 | resources/js/Pages/Welcome.vue | ~15 | Welcome Page | `max-w-md` | 448px | YA (mobile-first) | Optional | YA tapi by design - Landing page |
| 33 | resources/views/errors/minimal.blade.php | ~15 | Error Page | `max-w-2xl` | 672px | YA | Optional | YA tapi by design - Error page |
| 34 | resources/views/errors/minimal.blade.php | ~20 | Error Message | `max-w-lg mx-auto` | 512px | YA | Optional | YA tapi by design - Error page |

---

## LOW - TIDAK MASALAH

| # | File | Line | Component | Class | Nilai max-width | Disengaja? | Seharusnya responsive? | Desktop tetap mobile? |
|---|------|------|-----------|-------|-----------------|------------|------------------------|-----------------------|
| 35 | resources/js/Components/Chat/ChatEmptyState.vue | ~20 | Empty State Text | `max-w-sm` | 384px | YA | TIDAK | TIDAK - Text block, bukan layout |
| 36 | resources/js/Pages/Settings/AI/MemoryManage.vue | ~80 | Empty Description | `max-w-sm` | 384px | YA | TIDAK | TIDAK - Text description, bukan layout |
| 37 | resources/js/Components/Chat/ChatMessage.vue | ~50 | Bot Bubble | `style="max-width: 80%"` | 80% | YA | TIDAK | TIDAK - Bubble chat, proporsional |
| 38 | resources/js/Components/Chat/ChatMessage.vue | ~70 | User Bubble | `style="max-width: 80%"` | 80% | YA | TIDAK | TIDAK - Bubble chat, proporsional |
| 39 | resources/js/Components/Chat/EvidencePreview.vue | ~20 | Preview Image | `style="max-width: 300px"` | 300px | YA | TIDAK | TIDAK - Preview thumbnail |
| 40 | resources/js/Components/TransactionDetailModal.vue | ~40 | Notes Text | `max-w-[60%]` | 60% | YA | TIDAK | TIDAK - Text truncation |
| 41 | resources/js/Components/Chat/Messages/MessageReportSection.vue | ~30 | Wallet Name | `max-w-[60px]` | 60px | YA | TIDAK | TIDAK - Text truncation |
| 42 | resources/js/Components/Chat/Messages/MessageImage.vue | ~20 | Evidence Label | `max-w-[140px]` | 140px | YA | TIDAK | TIDAK - Text truncation |
| 43 | resources/js/Components/Header/ProfileMenu.vue | ~20 | Profile Menu | `max-w-[280px]` | 280px | YA | TIDAK | TIDAK - Dropdown menu width |
| 44 | resources/js/Pages/Categories/Index.vue | ~60 | Empty Message | `max-w-[200px]` | 200px | YA | TIDAK | TIDAK - Text block |
| 45 | resources/js/Pages/Categories/Show.vue | ~40 | Source Wallet | `max-w-[50px] lg:max-w-[60px]` | 50-60px | YA | TIDAK | TIDAK - Text truncation |
| 46 | resources/js/Pages/Categories/Show.vue | ~50 | Dest Wallet | `max-w-[50px] lg:max-w-[60px]` | 50-60px | YA | TIDAK | TIDAK - Text truncation |
| 47 | resources/js/Components/Header/ProfileAvatar.vue | ~15 | Avatar Touch | `style="width: 44px"` | 44px | YA | TIDAK | TIDAK - Touch target size |
| 48 | resources/js/Components/Header/HeaderActions.vue | ~20 | Button Touch | `style="width: 44px"` | 44px | YA | TIDAK | TIDAK - Touch target size |
| 49 | resources/js/Components/Header/AIChatShortcut.vue | ~15 | Button Touch | `style="width: 44px"` | 44px | YA | TIDAK | TIDAK - Touch target size |
| 50 | resources/js/Components/Header/NotificationButton.vue | ~15 | Button Touch | `style="width: 44px"` | 44px | YA | TIDAK | TIDAK - Touch target size |
| 51 | resources/js/Components/ConfirmationDialog.vue | ~40 | Dialog Message | `max-w-xs` | 320px | YA | TIDAK | TIDAK - Text readability |
| 52 | resources/js/Layouts/AuthenticatedLayout.vue | ~150 | Sidebar Brand | `max-w-xs`/`max-w-0` | 320px/0px | YA | TIDAK | TIDAK - Brand text expand/collapse |

---

## SUDAH RESPONSIVE (BENAR)

| # | File | Line | Component | Class | Nilai max-width | Disengaja? | Seharusnya responsive? | Desktop tetap mobile? |
|---|------|------|-----------|-------|-----------------|------------|------------------------|-----------------------|
| 53 | resources/js/Pages/Analytics/Index.vue | ~25 | Analytics Container | `lg:max-w-4xl` | 896px | YA | YA | TIDAK - Sudah responsive |
| 54 | resources/js/Pages/Wallets/Index.vue | ~15 | Wallets Container | `lg:max-w-4xl` | 896px | YA | YA | TIDAK - Sudah responsive |
| 55 | resources/js/Pages/Wallets/Show.vue | ~15 | Wallet Detail | `lg:max-w-4xl` | 896px | YA | YA | TIDAK - Sudah responsive |
| 56 | resources/js/Pages/Wallets/Create.vue | ~15 | Create Wallet | `lg:max-w-4xl` | 896px | YA | YA | TIDAK - Sudah responsive |
| 57 | resources/js/Pages/Wallets/Edit.vue | ~15 | Edit Wallet | `lg:max-w-4xl` | 896px | YA | YA | TIDAK - Sudah responsive |
| 58 | resources/js/Pages/Categories/Index.vue | ~15 | Categories | `lg:max-w-4xl` | 896px | YA | YA | TIDAK - Sudah responsive |
| 59 | resources/js/Pages/Categories/Show.vue | ~15 | Category Detail | `lg:max-w-4xl` | 896px | YA | YA | TIDAK - Sudah responsive |
| 60 | resources/js/Pages/Categories/Create.vue | ~15 | Create Category | `lg:max-w-4xl` | 896px | YA | YA | TIDAK - Sudah responsive |
| 61 | resources/js/Pages/Categories/Edit.vue | ~15 | Edit Category | `lg:max-w-4xl` | 896px | YA | YA | TIDAK - Sudah responsive |
| 62 | resources/js/Pages/Loans/Index.vue | ~15 | Loans | `lg:max-w-4xl` | 896px | YA | YA | TIDAK - Sudah responsive |

---

## STATISTIK

| Kategori | Jumlah |
|----------|--------|
| CRITICAL | 13 baris (6 file unik) |
| HIGH | 12 baris (11 file unik) |
| MEDIUM | 9 baris (8 file unik) |
| LOW | 18 baris |
| SUDAH BENAR | 10 baris |
| **TOTAL** | **62 baris** |

---

## FILE PRIORITAS PERBAIKAN

### CRITICAL (6 file)
1. `resources/js/Layouts/AuthenticatedLayout.vue` - Layout root terkunci 448px
2. `resources/js/Pages/Chat/Index.vue` - Chat terkunci 672px
3. `resources/js/Pages/Dashboard.vue` - Sort modal terkunci 384px
4. `resources/js/Components/BaseModal.vue` - Modal system hanya sampai xl
5. `resources/js/Pages/Transactions/Create.vue` - 3 modal terkunci
6. `resources/js/Pages/Transactions/Edit.vue` - 3 modal terkunci

### HIGH (11 file)
7. `resources/js/Components/Chat/ChatUploadSheet.vue`
8. `resources/js/Components/Chat/CommandSheet.vue`
9. `resources/js/Components/Chat/EvidenceReviewSheet.vue`
10. `resources/js/Components/Chat/Messages/TransactionDetailModal.vue`
11. `resources/js/Components/ImageCropModal.vue`
12. `resources/js/Components/Header/GlobalSearchOverlay.vue`
13. `resources/js/Pages/Search/Index.vue`
14. `resources/js/Pages/Settings/Layouts/SettingsLayout.vue`
15. `resources/js/Pages/Settings/Privacy/Danger.vue`
16. `resources/js/Components/Toast.vue`
17. `resources/js/Components/BottomNav.vue`
18. `resources/js/Components/CreateTransactionFab.vue`

---

## STATUS SETELAH PERBAIKAN (2026-07-31)

Semua item di bawah telah diperbaiki dengan transform responsive.

### CRITICAL — SELESAI DIPERBAIKI

| # | File | Perubahan |
|---|------|-----------|
| 1 | `AuthenticatedLayout.vue` | Root panel `max-w-md mx-auto` → `w-full` (prop `fullWidth` backward-compat, `containerSize` dead code dihapus) |
| 2 | `Chat/Index.vue` | `max-w-2xl` → `max-w-7xl mx-auto` |
| 3 | `Dashboard.vue` (Sort Modal) | `max-w-sm` → `sm:max-w-md` |
| 4-7 | `BaseModal.vue` | Sistem ukuran baru: `xs`→`7xl`, `full`, `adaptive` (grew per breakpoint) |
| 8 | `Transactions/Create.vue` Type Selector | `max-w-sm` → `sm:max-w-md` |
| 9-10 | `Transactions/Create.vue` Date/Wallet Modal | `max-w-md` → `sm:max-w-xl` |
| 11 | `Transactions/Edit.vue` Type Selector | `max-w-sm` → `sm:max-w-md` |
| 12-13 | `Transactions/Edit.vue` Date/Wallet Modal | `max-w-md` → `sm:max-w-xl` |

### HIGH — SELESAI DIPERBAIKI

| # | File | Perubahan |
|---|------|-----------|
| 14 | `ChatUploadSheet.vue` | `max-w-md` → `sm:max-w-xl` |
| 15 | `CommandSheet.vue` | `max-w-md` → `sm:max-w-xl` |
| 16 | `EvidenceReviewSheet.vue` | `max-w-lg` → `sm:max-w-2xl` |
| 17 | `TransactionDetailModal.vue` | `sm:max-w-lg` → `sm:max-w-2xl` |
| 18 | `ImageCropModal.vue` | `sm:max-w-lg` → `sm:max-w-2xl` |
| 19 | `GlobalSearchOverlay.vue` | `max-w-2xl` → `lg:max-w-4xl` |
| 20 | `Search/Index.vue` | `max-w-3xl` → `max-w-5xl` |
| 21 | `SettingsLayout.vue` | `max-w-4xl` → `max-w-7xl` |
| 22 | `Danger.vue` Delete Modal | `max-w-sm` → `sm:max-w-md` |
| 23 | `Toast.vue` | `max-w-sm` → `sm:max-w-md lg:max-w-lg` — toast melebar di tablet/desktop |
| 24 | `BottomNav.vue` | `max-w-sm` → `sm:max-w-md` — nav pill melebar di tablet sebelum `lg:` breakpoint |
| 25 | `CreateTransactionFab.vue` | Dibiarkan `max-w-md` — `lg:hidden`, mobile-only (LOW) |

### ROUND 2 — PAGE CONTAINER (ditambahkan setelah laporan awal)

| # | File | Perubahan |
|---|------|-----------|
| 26 | `Analytics/Index.vue` | `lg:max-w-4xl` → `lg:max-w-7xl` |
| 27 | `Loans/Index.vue` | `lg:max-w-4xl` → `lg:max-w-7xl` |
| 28 | `Categories/{Index,Create,Edit,Show}.vue` | `lg:max-w-4xl` → `lg:max-w-7xl` |
| 29 | `Wallets/{Index,Create,Edit,Show}.vue` | `lg:max-w-4xl` → `lg:max-w-7xl` |
| 30 | `ChatEmptyState.vue` wrapper | `max-w-sm` → `sm:max-w-md` |

### TIDAK DIUBAH (INTENTIONAL)

- **Auth pages** (`Login`, `Register`, `ForgotPassword`, `ResetPassword`, `ConfirmPassword`, `VerifyEmail`) — `max-w-md` centered, by design (form sempit).
- **`Welcome.vue`** — landing page `max-w-md`, by design.
- **Error pages** (`minimal.blade.php`) — `max-w-2xl`/`max-w-lg` centered, konten minim.
- **Element kecil** — avatar 44px, chat bubble 80%, truncate `max-w-[60px]`, dropdown `max-w-[280px]`, dll.

### ROUND 3 — MODAL DETAIL & BASEMODAL DEFAULT (ditambahkan 2026-07-31)

| # | File | Perubahan |
|---|------|-----------|
| 31 | `TransactionDetailModal.vue` | Main detail modal `max-width="sm"` → `max-width="adaptive"` (desktop full-width). Konfirmasi draft & hapus tetap `sm` (dialog kecil, by design). |
| 32 | `BaseModal.vue` | Default prop `maxWidth` dari `'sm'` → `'adaptive'`. Semua modal yang tidak men-set prop otomatis responsif: `max-w-md sm:max-w-lg md:max-w-xl lg:max-w-2xl xl:max-w-3xl 2xl:max-w-4xl`. |

### ROUND 4 — BOTTOM NAV, DATEMODAL, SORT MODAL (ditambahkan 2026-07-31)

| # | File | Perubahan |
|---|------|-----------|
| 33 | `BottomNav.vue` | Floating bar `sm:max-w-md` (448px, masih terkunci) → `sm:max-w-lg md:max-w-2xl` — melebar di tablet sebelum breakpoint `lg` (sidebar desktop). |
| 34 | `DateModal.vue` | `max-width="sm"` → `max-width="adaptive"` — date range picker ikut sistem modal responsif BaseModal. |
| 35 | `Dashboard.vue` Sort Modal | `w-full sm:max-w-md` → `w-full max-w-md sm:max-w-lg md:max-w-xl` — modal filter type melebar di tablet/desktop. |

### HASIL VERIFIKASI

- `grep max-w-(sm|md|lg|xl|2xl|3xl|4xl|5xl|6xl|7xl)` → sisa di `BaseModal.vue` (sistem ukuran prop, disengaja), halaman auth/welcome/error (by design), dan elemen text-level/overlay kecil: `Toast` (sm:max-w-md lg:max-w-lg), `BottomNav` (sm:max-w-lg md:max-w-2xl), `CreateTransactionFab` (max-w-md), `MemoryManage` empty text (max-w-sm), `ChatEmptyState` paragraf + `ConfirmationDialog` message (max-w-xs), sidebar brand expand/collapse (max-w-xs) — semua di tabel LOW/MEDIUM, tidak memengaruhi pemanfaatan ruang layar desktop.
- `BottomNav.vue` mobile/tablet bar kini tumbuh hingga `md:max-w-2xl` (672px); di atas `lg` menjadi sidebar desktop penuh.
- `BaseModal.vue` `adaptive` mengembang `max-w-md → sm:max-w-lg → md:max-w-xl → lg:max-w-2xl → xl:max-w-3xl → 2xl:max-w-4xl`.
- `PageContainer.vue` varian: `full`, `fluid` (default, sampai 7xl), `narrow` (sampai 4xl), `compact`, `none`.