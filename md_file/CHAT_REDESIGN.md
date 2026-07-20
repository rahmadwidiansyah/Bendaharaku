# Web Chat Bendaharaku — Redesign Roadmap

Status per 2026-07-19. P1–P3 sudah selesai diimplementasi. P4–P7 belum.

---

## ✅ P1 — Bug Fix & UX Dasar (SELESAI)

### Scroll
- Pesan lama di atas, terbaru di bawah — urutan sudah benar
- Auto-scroll ke bawah saat pertama buka atau kirim pesan
- **Smart scroll**: jika user sedang baca riwayat lama, AI reply tidak paksa scroll
- **Jump-to-latest button** muncul saat user scroll ke atas, hilang otomatis saat kembali ke bawah

### Single Bubble AI
- Semua komponen `text`, `divider`, `suggestion` digabung dalam **1 bubble** (bukan puluhan bubble terpisah)
- Field kosong (text kosong, divider di awal/akhir) otomatis disembunyikan
- `transaction_card`, `summary_card`, `error` tetap di luar bubble karena butuh lebar penuh

### Avatar Grouped
- Avatar hanya muncul di bubble **pertama** tiap grup pesan (jika role berbeda dari pesan sebelumnya)
- Pesan lanjutan dalam grup yang sama tidak tampilkan avatar — persis seperti Telegram
- Placeholder kosong tetap ada agar alignment konsisten

### Timestamp di Dalam Bubble
- Timestamp di pojok kanan bawah **di dalam** bubble, bukan mengambang di luar
- User bubble: timestamp warna putih transparan
- Bot bubble: timestamp abu-abu di footer bubble

### ChatComposer Compact
- Height lebih kecil (`h-9` bukan `h-10`, `py-1.5` bukan `py-2`)
- Max 4 baris textarea (bukan 5)
- Border lebih subtle, gap lebih rapat — feel Telegram

---

## ✅ P2 — Response Metadata (SELESAI)

### ResponseMeta.vue
Komponen baru di footer bot bubble, menampilkan:
- **Latency**: `823ms` atau `1.42s` — dari `metadata.latency_ms`
- **Token**: `412 token` — dari `metadata.total_tokens`
- **Model badge**: `Gemini Flash`, `GPT-4o`, `DeepSeek`, `Python NLP` — dipersingkat dari nama model mentah
- Warna badge berbeda per provider (biru=Gemini, hijau=OpenAI, cyan=DeepSeek, kuning=Python)
- Semua field conditional — tidak tampil jika data tidak ada

---

## ✅ P3 — Transaction Detail Modal (SELESAI)

### TransactionDetailModal.vue
Modal/bottom-sheet yang muncul saat user tap TransactionCard. Berisi:

| Info | Sumber |
|------|--------|
| ID Transaksi + tombol **Salin** | `reference_number` atau `id` |
| Dicatat dari (Web Chat, Telegram Bot, dll) | Prefix reference_number (WEB, TEL, WA, dll) |
| Intent | `metadata.intent` |
| Diproses oleh | `metadata.model` |
| Durasi proses | `metadata.latency_ms` |
| Confidence AI | `metadata.confidence` dengan label Tinggi/Sedang/Rendah |
| Status (AI Parsed / Draft) | `is_cleared` |
| Waktu dibuat (tanggal + jam) | `created_at` |
| **Accordion: JSON Metadata** | `metadata` raw, untuk debugging |

---

## 🔜 P4 — Chat Experience Lanjutan (BELUM)

### Yang perlu dibuat:

**MessageToolbar.vue** — toolbar muncul saat hover di bot bubble
- Tombol **Salin** teks bubble
- Tombol **Coba Lagi** (hanya jika message adalah error)

**Retry logic di useChat.js**
- `retryLastMessage()`: cari pesan user terakhir, hapus error bubble bot, kirim ulang

**Regenerate** — kirim ulang prompt yang sama untuk dapat jawaban berbeda

**Favorite/Bintang** — tandai pesan penting (butuh kolom `is_starred` di `chat_messages`)

**Search** — cari teks dalam percakapan aktif (client-side filter atau endpoint baru)

**Streaming** — jika provider support SSE/stream, tampilkan teks AI muncul bertahap
- Butuh endpoint stream di backend (`/chat/stream`)
- Frontend pakai `EventSource` atau `fetch` dengan `ReadableStream`

---

## 🔜 P5 — Visual Polish (BELUM)

Audit visual menyeluruh:
- Radius konsisten: `rounded-xl` untuk elemen kecil, `rounded-2xl` untuk card/bubble
- Shadow lebih halus: `shadow-sm` bukan `shadow-lg` di kebanyakan elemen
- Spacing lebih rapi: padding konsisten `px-3.5 py-2.5`
- Typography: `text-sm` untuk konten utama, `text-2xs` untuk metadata/label
- Card tidak terlalu tinggi: kompres padding TransactionCard di mode compact
- Badge warna konsisten: income=emerald, expense=red, transfer=blue, debt=amber
- Animasi masuk bubble: `translate-y-1` lebih subtle dari `translate-y-2`
- Mobile-first: pastikan semua tap target minimal 44×44px

---

## 🔜 P6 — Komponen Reusable (BELUM)

Komponen yang perlu diekstrak/dibuat:

| Komponen | Deskripsi |
|----------|-----------|
| `ChatBubble.vue` | Wrapper bubble generik (bot/user), handle padding, radius, shadow |
| `ChatTimestamp.vue` | Timestamp standalone dengan format konsisten |
| `TokenBadge.vue` | Badge token usage (`412 token`) reusable |
| `ModelBadge.vue` | Badge nama model dengan warna per provider |
| `MessageToolbar.vue` | Toolbar hover copy/retry (lihat P4) |
| `BalanceCard.vue` | Card khusus tampilan saldo (dari `/saldo` command) |

Catatan: `ResponseMeta.vue` dan `TransactionDetailModal.vue` sudah dibuat di P2–P3.

---

## 🔜 P7 — Clean Code & Performance (BELUM)

**Composables**
- Pisahkan scroll logic ke `useChatScroll.js`
- Pisahkan message normalization ke `useChatMessages.js`

**Render optimization**
- Gunakan `v-memo` di ChatMessage untuk messages yang tidak berubah
- Gunakan virtual scroll jika percakapan > 500 pesan (pertimbangkan `vue-virtual-scroller`)

**Dokumentasi**
- JSDoc untuk semua fungsi publik di composables
- Comment di setiap komponen menjelaskan props dan emit

**Backend yang masih perlu dikerjakan untuk P4**
- Endpoint `POST /chat/stream` untuk streaming response
- Kolom `is_starred` di tabel `chat_messages` + migration
- Route `PATCH /chat/messages/{id}/star` untuk toggle favorit
- Full-text search: `GET /chat/search?q=...&conversation_id=...`

---

## File yang Sudah Diubah (P1–P3)

```
resources/js/
├── Composables/
│   ├── useChat.js              ← scroll logic, smart scroll, jump-to-latest, retryLastMessage
│   └── useLocale.js            ← persist locale ke DB via axios
├── Components/Chat/
│   ├── ChatMessage.vue         ← single bubble, avatar grouped, timestamp inside, ResponseMeta
│   ├── ChatArea.vue            ← avatar grouping logic, jump-to-latest button, retry forward
│   ├── ChatComposer.vue        ← compact size, feel Telegram
│   ├── ChatHeader.vue          ← avatarFailed reactive fallback
│   ├── ChatEmptyState.vue      ← avatarFailed reactive fallback
│   └── Messages/
│       ├── ResponseMeta.vue    ← NEW: latency, token, model badge
│       ├── TransactionDetailModal.vue  ← NEW: detail lengkap + accordion
│       ├── MessageTransactionCard.vue  ← clickable, membuka modal
│       └── MessageRenderer.vue ← pass metadata ke transaction_card
└── Pages/Chat/
    └── Index.vue               ← wire scroll events, jump-to-latest
```

```
app/
├── Chat/
│   ├── ChatApplicationService.php  ← latency cast fix, platform guard
│   ├── DTOs/ChatContext.php        ← locale fallback ke 'id'
│   └── Adapters/WebAdapter.php     ← latency_ms key konsisten
├── Services/
│   ├── AI/AiParseLogService.php    ← tambah createMultiLog()
│   └── Chat/ChatTransactionOrchestrator.php  ← panggil createMultiLog
└── lang/
    ├── id/chat.php
    └── en/chat.php                 ← tambah missing keys
routes/web.php                      ← tambah PATCH /settings/locale
.env                                ← APP_LOCALE=id
```
