# Optimasi Warna Gradasi di Light Mode

**Status:** Approved  
**Tanggal:** 2026-07-31

## Masalah

1. Gradasi brand (`from-purple-800 to-purple-500`) tidak mengikuti accent color: `useAccentColor.js` hanya meng-override `--color-purple-300..700`, sehingga `purple-800` selalu ungu tua default (`#6b21a8`) apa pun accent yang dipilih user.
2. Di light mode, gradasi solid berwarna (purple-800, red-900, green-900, amber-900, blue-900, fuchsia-900) tetap gelap karena tidak diremap oleh blok `.light` — terlalu mencolok di atas permukaan terang.
3. Gradasi abu (`from-gray-900/to-gray-800`) sudah aman (teremap via token gray di `.light`), begitu juga gradasi transparan (`red-900/30`) yang menjadi pastel di atas permukaan terang.

## Desain

### 1. Palet accent mendapat shade `800` (`resources/js/Composables/useAccentColor.js`)

- Semua palet di `ACCENT_PALETTES` (teal, blue, indigo, pink, cyan, rose) mendapat entri `800` (warna terdalam, kira-kira setara `mixColor(base, -0.60)`).
- `generatePalette()` untuk custom color menambahkan `800: mixColor(baseHex, -0.60)`.
- `buildOverrideCSS()` otomatis memasukkan shade baru (iterasi `Object.entries`) ke `:root` dan `.light` (varian gelap -18%).

Efek: `from-purple-800` ikut accent di dark mode.

### 2. Token gradasi semantic dual-mode (`resources/css/app.css`)

`@theme` (default dark):

```css
--color-brand-deep:  var(--color-purple-800);
--color-brand-mid:   var(--color-purple-600);
--color-brand-soft:  var(--color-purple-500);
--color-brand-tint:  var(--color-purple-400);
--color-danger-deep: var(--color-red-900);
--color-danger-soft: var(--color-red-500);
--color-success-deep: var(--color-green-900);
--color-success-soft: var(--color-green-500);
--color-warning-deep: var(--color-amber-900);
--color-warning-soft: var(--color-amber-500);
```

Blok `.light`:

```css
--color-brand-deep:  color-mix(in srgb, var(--color-purple-600) 14%, #FFFFFF);
--color-brand-mid:   color-mix(in srgb, var(--color-purple-500) 10%, #FFFFFF);
--color-brand-soft:  color-mix(in srgb, var(--color-purple-400) 8%, #FFFFFF);
--color-brand-tint:  var(--color-purple-300);
--color-danger-deep: color-mix(in srgb, var(--color-red-500) 12%, #FFFFFF);
--color-danger-soft: color-mix(in srgb, var(--color-red-400) 8%, #FFFFFF);
--color-success-deep: color-mix(in srgb, var(--color-green-500) 12%, #FFFFFF);
--color-success-soft: color-mix(in srgb, var(--color-green-400) 8%, #FFFFFF);
--color-warning-deep: color-mix(in srgb, var(--color-amber-500) 14%, #FFFFFF);
--color-warning-soft: color-mix(in srgb, var(--color-amber-400) 10%, #FFFFFF);
```

Efek: light mode jadi wash pucat warna accent/semantik; dark mode identik dengan kondisi sekarang.

### 3. Migrasi class gradasi

Ganti hardcoded stop warna dengan token (peta utama):

| Sebelum | Sesudah |
|---|---|
| `from-purple-800 to-purple-500` | `from-brand-deep to-brand-soft` |
| `from-purple-800 via-purple-600 to-purple-500` | `from-brand-deep via-brand-mid to-brand-soft` |
| `from-purple-700 to-purple-500` | `from-brand-deep to-brand-soft` |
| `from-purple-600 to-purple-400` | `from-brand-mid to-brand-tint` |
| `to-purple-700`, `to-purple-600` | `to-brand-soft` / `to-brand-mid` (sesuai konteks) |
| `from-red-900 to-gray-900` (Toast) | `from-danger-deep to-gray-900` |
| `from-green-900 to-gray-900` (Toast) | `from-success-deep to-gray-900` |
| `to-green-900` solid | `to-success-deep` |
| `to-blue-900`, `to-fuchsia-900`, `to-amber-900` solid | token semantik sesuai konteks |

Yang TIDAK diubah: gradasi abu (`from-gray-900/to-gray-800`, sudah remap), gradasi transparan `*/30` (sudah pastel), shadow/ring/border accent (sudah ikut accent).

### 4. Verifikasi

- `npm run build` lolos.
- Headless render light mode: computed `background-image` tombol primary = gradasi wash pucat (bukan ungu tua).
- Dark mode: gradasi tetap pekat (deep accent).
- Ganti accent color → gradasi deep/soft ikut berubah.

## Lanjutan: Donut Chart & Halaman Kategori

### Masalah

- Palet donut/bar chart di `Analytics/Index.vue` hardcoded hex (`activeColors`, `FLOW_COLORS`, border `#121212`) — tidak mengikuti tema; warna piutang ungu statis.
- Tombol create di `Categories/Index.vue` masih `from-purple-900/50` (ungu gelap di light mode).
- Warna tipe kategori (`green-400` dkk) kontras rendah di light mode.

### Solusi (disetujui, dengan keputusan: piutang TETAP ungu statis)

- `FLOW_COLORS` → computed yang baca token CSS runtime: income→`--color-income-chart`, expense→`--color-expense-chart`, debt→`--color-debt-chart`, receivable→`--color-receivable-chart` (statis ungu, tidak ikut accent).
- `activeColors` → `buildPalette(baseHex)` menghasilkan ramp 6 shade (lighten 0.40/0.22, base, darken 0.15/0.30/0.45); base dibaca dari token tema → otomatis dark/light.
- Border donut & point border → `--color-gray-950` (menyatu dengan kartu).
- Tombol create: `from-brand-deep/50 to-gray-800` + `border-brand-border` + `hover:border-brand-tint`; sub-teks `text-purple-400`.
- `getTheme` (list kategori) & `CATEGORY_ICON_COLORS` (`useIcon.js`) → token semantik (`text-income-text`, `bg-income-bg`, `hover:border-income-border`, dst.) yang punya varian dark+light.
