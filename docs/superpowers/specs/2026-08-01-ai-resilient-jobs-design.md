# Design: AI Tahan Banting — Generate Budget & Chat Tidak Gagal Saat Pindah Halaman

Tanggal: 2026-08-01
Status: Disetujui (diskusi lisan) — menunggu review spec tertulis

## Masalah

1. Semua panggilan LLM berjalan **sinkron di dalam HTTP request**:
   - Generate budget: `POST /api/v1/budget/generate` menggantung 15–30 detik (adapter timeout 15–30s).
   - Chat: `POST /chat/message` menggantung hingga ~182 detik (worst case `AiReportClient` retry).
2. Saat user pindah halaman, Inertia membatalkan XHR. Di sisi server request bisa terterminasi di tengah panggilan LLM → hasil tidak tersimpan, frontend menampilkan "gagal".
3. `QUEUE_CONNECTION=database` sudah diset, tetapi **docker-compose tidak punya worker queue** → job menggantung di tabel `jobs` selamanya.
4. Tidak ada kolom status di `budget_groups` / `chat_messages` dan tidak ada pola polling di frontend.

## Solusi

Pindahkan semua panggilan LLM ke **background job (database queue)** dengan **status tracking + polling frontend** (pola yang sudah dipakai pipeline Evidence: job + status + endpoint status).

Flow umum:
- Request frontend → validasi → simpan status (pending) / simpan pesan pending → dispatch job → respons instan `{ queued: true }`.
- Job menjalankan logika AI di worker → update status (completed/failed).
- Frontend polling endpoint status (interval 2 detik) → render hasil / error.
- Kalau tab ditutup: job tetap selesai; saat user kembali, halaman membaca status atau data final dari DB.

## 1. Infrastruktur

- **docker-compose.yml**: tambah service `queue` yang menjalankan worker:
  `php artisan queue:work --sleep=2 --tries=3 --timeout=300`
  (mengikuti pola service `app`: build image yang sama, env yang sama).
- `QUEUE_CONNECTION=database` sudah benar di `.env` / `.env.example`.

## 2. Generate Budget

### Backend

- **Migrasi** `create_budget_generation_statuses`:
  - `id`, `user_id` (FK), `year`, `month`, `status` (`pending|processing|completed|failed`), `error_message` nullable, timestamps, unique(`user_id`, `year`, `month`).
- **Model** `BudgetGenerationStatus`.
- **`GenerateBudgetJob`** (queue `default`, `tries=3`, backoff 5s, `$timeout=300`):
  - Set status `processing` → panggil `AIBudgetService::generate($user, $month, $year)` → status `completed`.
  - Catch `AiTimeoutException|AiRateLimitException|AiProviderException|UnexpectedValueException|RuntimeException` → status `failed` + `error_message`.
  - Idempotent aman untuk retry (upsert + delete/recreate items & expenseGroups).
- **`BudgetController::generate`**: validasi bulan berjalan (tetap) → buat/update status `pending` → dispatch job → return **202 `{ queued: true }`**.
- **Endpoint baru** `GET /api/v1/budget/generate/status?year=&month=` → `{ status, error_message }` (validasi bulan berjalan tetap).
- `BudgetController::show` tidak berubah.

### Frontend (`Budgeting/Index.vue`)

- `generateBudget()`: POST → `{ queued: true }` → mulai polling status tiap 2 detik (state `isGenerating` tetap dipakai).
- Status:
  - `processing` → terus poll (UI generating: spinner tombol + skeleton).
  - `completed` → hentikan poll, `fetchBudget()`.
  - `failed` → hentikan poll, tampilkan pesan error (dari `error_message` / `budgeting.aiError` dengan nama bot) + tombol retry.
- `onMounted`: cek status dulu — `processing` → tampilkan state generating + lanjut poll; `failed` → tampil error; selain itu → `fetchBudget()` normal.
- Poll berhenti otomatis saat komponen unmount (user pindah halaman); saat kembali, `onMounted` menangani.

## 3. Chat

### Backend

- **Migrasi**: tambah kolom `status` (string, default `'completed'` untuk data lama) dan `error_message` (nullable) ke `chat_messages`.
- **`ProcessChatMessageJob`** (queue `default`, `tries=1`, `$timeout=300`):
  - `tries=1` karena pipeline chat dapat membuat transaksi/draft — retry berisiko duplikasi.
  - Input: `user_id`, `conversation_id`, `bot_message_id`.
  - Jalankan logika yang sama seperti `WebAdapter::handle` saat ini (resolve context → `ChatApplicationService::handleMessage` → `WebFormatter` → simpan konten bot), lalu update status `completed`.
  - Catch exception → update status `failed` + `error_message` (pesan error tetap di-persist sebagai fallback konten bot).
- **Refactor `WebAdapter::handle`** / `WebChatController::sendMessage`:
  - Controller: validasi → resolve conversation → persist pesan user → buat pesan bot **pending** (konten kosong) → dispatch job → return JSON `{ conversation_id, user_message, bot_message: { id, status: 'pending' }, queued: true }`.
  - Logika proses pesan dipindah ke job (hindari duplikasi).
- **Endpoint baru** `GET /chat/message-status/{botMessageId}` → `{ status, error_message, bot_message }` (`bot_message` = record lengkap pesan bot, terisi saat `completed` — frontend tidak perlu refetch riwayat; authorize kepemilikan).
- Riwayat chat (`WebChatController@index`) tetap; bot message berstatus `pending` ikut dikirim agar frontend bisa menampilkan indikator mengetik saat kembali ke halaman.

### Frontend

- **`useChat.js`**:
  - `sendMessage()`: POST → push bubble user (optimistic, seperti sekarang) + **bubble bot pending** (indikator mengetik) → poll `GET /chat/message-status/{id}` tiap 2 detik:
    - `completed` → ganti bubble dengan konten `bot_message` dari respons polling.
    - `failed` → bubble error + tombol "coba lagi".
  - Input tetap terkunci selama ada pesan pending (logika `isTyping` dipertahankan).
- **Global poller** — composable singleton baru `useChatPending.js` (modul-level state):
  - Dipakai di `AuthenticatedLayout.vue` (dipasang sekali).
  - Menyimpan daftar `bot_message_id` yang pending.
  - Poll tiap 2 detik; saat sebuah pesan mencapai terminal:
    - `completed` dan rute aktif **bukan** `/chat` → toast "Ken-Chan menjawab pesan kamu".
    - `failed` dan rute aktif bukan `/chat` → toast error.
    - Jika user sedang di `/chat` → tidak ada toast (bubble inline yang menampilkan hasil).
  - Berhenti mempoll saat daftar kosong.

## 4. Error Handling

- Job menangkap exception AI dan menyimpan pesan error (ramah, memakai `bot_display_name` di frontend).
- `GenerateBudgetJob`: retry 3× (aman, idempotent).
- `ProcessChatMessageJob`: tanpa retry (hindari duplikasi transaksi).
- Worker timeout 300s mengakomodasi worst case `AiReportClient` (~182s).

## 5. Testing

- **BudgetApiTest**: `generate` → 202 + `queued: true` + status `pending` (`Queue::fake`); endpoint status; guard bulan berjalan tetap (422); job sukses & gagal (jalankan job langsung dengan service mock; verifikasi status ter-update).
- **Chat tests** (WebChatController): `sendMessage` → respons `queued` + bot message pending dibuat; job menghasilkan konten bot (jalankan job langsung); endpoint status; pesan pending muncul di riwayat.
- Frontend: `npm run build` hijau.
- Verifikasi manual: generate budget → pindah halaman → kembali (hasil tampil); kirim pesan chat → pindah ke halaman lain → toast muncul; tutup tab → buka lagi → hasil tersimpan.

## 6. File yang Terlibat

Backend:
- `database/migrations/..._create_budget_generation_statuses_table.php` (baru)
- `database/migrations/..._add_status_to_chat_messages_table.php` (baru)
- `app/Models/BudgetGenerationStatus.php` (baru)
- `app/Jobs/GenerateBudgetJob.php` (baru)
- `app/Jobs/ProcessChatMessageJob.php` (baru)
- `app/Http/Controllers/Api/V1/BudgetController.php` (generate + endpoint status)
- `app/Http/Controllers/WebChatController.php` (sendMessage + endpoint status)
- `app/Chat/Adapters/WebAdapter.php` (refactor proses → job)
- `docker-compose.yml` (service `queue`)

Frontend:
- `resources/js/Pages/Budgeting/Index.vue`
- `resources/js/Composables/useChat.js`
- `resources/js/Composables/useChatPending.js` (baru)
- `resources/js/Layouts/AuthenticatedLayout.vue` (poller global + toast)
- `resources/js/Pages/Chat/Index.vue` (render pending bubble)
- i18n `resources/js/Locales/*.js` (key toast baru)

## Di Luar Lingkup

- Notifikasi push realtime (Reverb/Pusher) — polling cukup.
- Telegram adapter (alur webhook) — tidak berubah.
- Command laporan bulanan diproses dalam job chat yang sama (mengikuti pipeline yang ada).
