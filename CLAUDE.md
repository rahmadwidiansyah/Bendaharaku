# Panduan Pengembangan Bendaharaku untuk AI

Dokumen ini adalah sumber kebenaran tunggal bagi setiap AI yang bekerja di codebase Bendaharaku. Mengabaikan aturan di sini akan menyebabkan penolakan pull request.

---

## 1. Quick Start & Lingkungan Kerja

Lingkungan pengembangan utama menggunakan Docker.

**Perintah Setup Kanonikal:**

```bash
# 1. Salin file environment
cp .env.example .env

# 2. Jalankan skrip setup dari Composer
composer setup
```

Skrip `composer setup` akan menangani semua langkah yang diperlukan: instalasi dependensi, pembuatan kunci aplikasi, migrasi database, dan build aset frontend.

**Perintah Pengembangan Harian:**

-   **Menjalankan Dev Server**: `npm run dev`
-   **Menjalankan Tes**: `composer test`
-   **Menjalankan Linter & Formatter**: `composer lint` (memperbaiki otomatis)

---

## 2. Filosofi Desain & Aturan Inti

Aturan berikut diambil dari `design.md` dan tidak dapat ditawar.

### 2.1. Warna Semantik Finansial (Aturan Paling Sakral)

Warna adalah bahasa. Jangan pernah salah menggunakannya.

-   **Hijau**: **Hanya untuk Pemasukan (Income)**. Token: `var(--color-income-text)`, `var(--color-income-bg)`, dll.
-   **Merah**: **Hanya untuk Pengeluaran (Expense)**. Token: `var(--color-expense-text)`, `var(--color-expense-bg)`, dll.
-   **Amber/Kuning**: **Hanya untuk Utang (Debt)**. Token: `var(--color-debt-text)`, `var(--color-debt-bg)`, dll.
-   **Ungu**: **Hanya untuk Piutang (Receivable)**. Token: `var(--color-receivable-text)`, `var(--color-receivable-bg)`, dll.
-   **Biru**: **Hanya untuk Transfer** antar akun internal. Token: `var(--color-transfer-text)`, `var(--color-transfer-bg)`, dll.

**Implementasi:**
1.  **JANGAN PERNAH** hardcode warna finansial (misal: `text-green-500` adalah **TERLARANG**).
2.  **SELALU** gunakan token CSS yang sesuai (`var(--color-income-text)`).
3.  Warna harus diterapkan pada semua elemen terkait: teks nominal, ikon, latar belakang, border, dan grafik.
4.  Setiap penggunaan warna harus didampingi oleh ikon atau label untuk aksesibilitas.

### 2.2. Sistem Elevasi & Bayangan

UI kita tidak datar. Elevasi digunakan untuk menciptakan hierarki visual.

-   **Mode Terang**: Mengandalkan bayangan (`shadow-card`, `shadow-modal`) dan border (`border-default`) yang halus untuk menciptakan kedalaman.
-   **Mode Gelap**: Mengandalkan perubahan kecerahan permukaan. Semakin tinggi elevasi, semakin terang latar belakangnya (misal: Level 1 adalah `#111827`, Level 2 adalah `#1f2937`).
-   **Glassmorphism**: Diterapkan pada `Header`, `Bottom Navigation`, dan `Popovers/Menus` (Level 3, 4, 8) menggunakan `backdrop-filter: blur()` dan background semi-transparan.

### 2.3. Tipografi, Spasi, dan Konsistensi Visual

-   **Tipografi**: **HARUS** menggunakan token tipografi semantik (misal: `text-heading-1`, `text-body-md`). **DILARANG** menggunakan kelas utilitas mentah seperti `text-lg` atau `font-bold`.

-   **Spasi dan Ukuran (Aturan Diperketat)**: **SEMUA** nilai yang berhubungan dengan ukuran—termasuk `padding`, `margin`, `gap`, `width`, `height`, `border-radius`—**WAJIB** menggunakan kelas utilitas Tailwind yang telah dikonfigurasi untuk merujuk pada design tokens kita.
    -   **CONTOH NYATA**: Awalnya, `PortfolioCard.vue` menggunakan `p-3.5 sm:p-7`, yang merupakan nilai *hardcoded*. Ini menyebabkan inkonsistensi visual pada mobile. Setelah di-refactor, komponen ini menggunakan `p-3 sm:p-5`, yang sesuai dengan token `--spacing-md` dan `--spacing-xl` kita.
    -   **PRINSIP**: Jangan pernah "menebak" nilai spasi. Gunakan token yang sudah ada untuk memastikan keseragaman di seluruh aplikasi. Jika ragu, `p-3` (`--spacing-md: 12px`) adalah titik awal yang baik untuk padding internal komponen.

-   **Mobile-First & Fleksibilitas**: Desain kita adalah *mobile-first*. Komponen harus terlihat bagus di layar kecil secara default.
    -   Jika token standar terasa tidak pas pada layout mobile (misalnya, padding terlalu lebar), prioritasnya adalah menyesuaikan dengan token yang lebih kecil.
    -   Diskusi lebih diutamakan daripada *hardcoding*. Jika penyesuaian besar diperlukan, idealnya kita memperbarui nilai token itu sendiri, bukan menambahkan nilai baru secara sepihak.

---

## 3. Arsitektur Backend (Laravel)

Struktur backend kita dirancang untuk keterbacaan dan skalabilitas.

-   **Stack**: Laravel 13, PHP 8.3+.
-   **Pola Utama**: Actions & Services.
    -   **Actions**: Berisi logika bisnis inti untuk satu tindakan spesifik (misal: `CreateTransactionAction`). Mereka *single-purpose*, *class-based*, dan dapat dipanggil dari mana saja (Controller, Job, Command).
    -   **Services**: Berisi logika yang lebih kompleks atau berinteraksi dengan layanan eksternal (misal: `OpenAIService`).
-   **Data Transfer Objects (DTOs)**: Gunakan DTOs (direktori `app/DTO`) untuk mentransfer data terstruktur antara lapisan aplikasi (misal: dari Request ke Action). Ini mencegah "array-passing" yang tidak jelas.
-   **Rute**: Rute web menggunakan `Inertia.js` dan didefinisikan di `routes/web.php`. Rute API di `routes/api.php` dan dilindungi oleh Sanctum.
-   **Model**: Model Eloquent merepresentasikan entitas utama (Transaction, Wallet, Category). Jaga agar *logic-free* sebisa mungkin; pindahkan logika bisnis ke Actions atau Services.

---

## 4. Arsitektur Frontend (Vue & Inertia)

-   **Stack**: Vue 3, Inertia.js, Vite, Tailwind CSS.
-   **Struktur Direktori**:
    -   `resources/js/Pages`: Komponen halaman utama yang dirender oleh Inertia.
    -   `resources/js/Components`: Komponen Vue yang dapat digunakan kembali.
    -   `resources/js/Layouts`: Layout utama aplikasi (misal: `AuthenticatedLayout.vue`).
    -   `resources/js/Composables`: Logika reaktif yang dapat digunakan kembali.
    -   `resources/css/app.css`: File CSS utama tempat token dan layer Tailwind didefinisikan.
-   **Design Tokens**: Semua nilai desain (warna, spasi, font) didefinisikan sebagai CSS Custom Properties (variabel) di `app.css` dan dikonsumsi melalui `tailwind.config.js`. **JANGAN PERNAH** hardcode nilai di komponen.
-   **Komponen Bersama (Shared Components)**:
    -   Gunakan komponen dari `resources/js/Components` sesering mungkin.
    -   `BaseModal.vue` **WAJIB** digunakan untuk semua popup, dialog, dan picker. Jangan membuat modal dari nol.
    -   Manfaatkan prop `max-width` pada `BaseModal` (misal: `adaptive`, `4xl`) sesuai panduan `design.md`.

---

## 5. Pola Kritis & Aturan Implementasi

### 5.1. Loading State & Skeleton

-   **Aturan Utama**: **TIDAK ADA PERGESERAN LAYOUT (CLS)**.
-   **Implementasi**:
    1.  **SELALU** gunakan komponen Skeleton (`resources/js/Components/Skeleton/`) saat memuat data.
    2.  Skeleton **HARUS** memiliki dimensi yang sama persis dengan komponen final untuk mencegah *layout shift*.
    3.  **DILARANG** menggunakan spinner layar penuh setelah *initial load*. Gunakan skeleton untuk pemuatan komponen dan spinner *inline* di dalam tombol untuk aksi.
    4.  Terapkan *Progressive Rendering*: render bagian UI yang sudah siap terlebih dahulu (shell, header), lalu isi dengan konten saat data tersedia.

### 5.2. Penanganan Form & Auth Pages

-   **Aturan**: Halaman otentikasi (Login, Register) **HARUS** *full-width* di wrapper-nya dan menengahkan konten form di dalamnya (`max-w-md`). **JANGAN** membuat halaman auth terlihat seperti kartu yang melayang.
-   Gunakan komponen Input standar yang sudah memiliki state (focus, error, disabled) yang sesuai dengan design system.

### 5.3. Interaksi dengan AI

1.  **Verifikasi Konteks**: Sebelum menulis kode, baca file yang relevan untuk memahami implementasi yang ada.
2.  **Gunakan Token SECARA KETAT**: Semua nilai CSS (warna, spasi, font, shadow, radius) **HARUS** menggunakan token dari design system. Jangan pernah menggunakan nilai *hardcoded* seperti `p-3.5` atau `text-gray-400`.
3.  **Audit Visual Setelah Implementasi**: Setelah membuat atau memodifikasi komponen UI, lakukan pengecekan visual. Pastikan padding, margin, dan ukuran font konsisten dengan komponen lain yang ada di sekitarnya dan sesuai dengan design tokens.
4.  **Hormati Abstraksi**: Gunakan Actions, Services, dan komponen Vue yang ada. Jangan menulis ulang logika yang sudah ada.
5.  **Tangani Semua State**: Saat membuat komponen, implementasikan state `loading` (dengan skeleton), `empty`, dan `error`.
6.  **Aksesibilitas**: Pastikan semua elemen interaktif dapat diakses keyboard dan screen reader (`aria-*`, `role`).

Dengan mengikuti panduan ini, Anda memastikan bahwa kontribusi Anda konsisten, berkualitas tinggi, dan selaras dengan visi produk Bendaharaku.