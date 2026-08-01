# Web Push Notifications — Bendaharaku

Tanggal: 2026-08-01
Status: Disetujui (2026-08-01)
Branch: `feat/light-mode`

## 1. Tujuan

Memberi tahu user melalui **browser web push** (fokus utama: Android/mobile) ketika:

1. **Balasan chat selesai diproses** — sukses maupun gagal — saat user tidak sedang membuka aplikasi (keluar app, pindah ke app lain, atau tab di-minimize).
2. **Budget bulanan over** — pengeluaran melewati target — sekali per grup per bulan.
3. **Budget bulan baru dibuat** — pada tanggal 1 (auto-generate) dan saat generate manual selesai — sukses maupun gagal.
4. **Hutang/piutang jatuh tempo** — pengingat 1 hari sebelum (D-1) dan pada hari H, untuk hutang yang harus dibayar maupun piutang yang harus diterima.

Batasan kunci: push dikirim **hanya jika user tidak sedang aktif melihat tab Bendaharaku**. Jika user sedang membuka aplikasi, notifikasi dalam aplikasi (toast) sudah menangani — tidak boleh ada notif ganda.

Bahasa notifikasi mengikuti `user->locale` (id/en), konsisten dengan locale aplikasi.

## 2. Keputusan Desain

| Topik | Keputusan |
|---|---|
| Transport | Web Push Protocol standar dengan **VAPID** (`minishlink/web-push`) |
| Kapan push chat dikirim | Hanya saat user **away** (tidak membuka tab Bendaharaku; minimize/background/tab tertutup = away) |
| Ambang budget over | `spent > target` (over 100%), **sekali** per grup per bulan |
| Deteksi kehadiran | Sinyal presensi dari browser (`visibilitychange` + heartbeat 60s) + fallback timestamp kedaluwarsa 2 menit |
| Bahasa | `user->locale`, keys di `lang/{id,en}/push.php` (file baru) |
| Pengiriman | `SendPushNotificationJob` (queue), agar tidak memperlambat job utama |
| Endpoint mati | `POST /notifications/*` + PATCH settings jadi nyata |
| Pengingat jatuh tempo | Command harian `loan:send-reminders` (07:00); push saat **D-1** dan **H** per (subject, tipe, tanggal jatuh tempo); hanya saldo > 0; dedupe persisten via tabel `loan_reminders` |
| Cakupan jatuh tempo | Ketiga tipe `due_date_type`: `fixed`, `monthly`, `daily` — logika `next_due_date` diekstrak dari `DashboardController` ke service bersama `DueDateService` agar tidak duplikat |

### 2.1. Mengapa VAPID server-driven (bukan client `showNotification`)

- Client-driven mati total saat tab ditutup — gagal di skenario utama (user keluar dari app).
- Server-driven tahu status pengiriman (410 Gone → hapus subscription basi; error → log).

### 2.2. Mengapa bukan FCM

- FCM menambah ketergantungan Google dan setup project. VAPID bekerja di semua browser (Chrome/Edge/Firefox Android, iOS Safari 16.4+).
- FCM relevan hanya jika nanti ada app native — di luar scope.

## 3. Arsitektur

### 3.1. Alur presensi (kapan push dikirim)

```
Browser                                 Server (cache)
  visibilitychange=visible  ──POST /notifications/presence {state:active}──► presence:{user}: {state:active, at:t}
  heartbeat tiap 60s saat visible (refresh at)
  visibilitychange=hidden   ──POST /notifications/presence {state:away}────► presence:{user}: {state:away, at:t}
  tab ditutup               (tanpa sinyal — at terakhir membusuk)

Aturan push (server, di job trigger):
  away  = presence.state == 'away' ATAU (now - presence.at) > 2 menit
  kirim push HANYA jika away
```

- Storage: cache store (`CACHE_STORE=file` di dev, `database` di prod/.env.example) — cukup, tanpa tabel DB.
- Key cache: `presence:{user_id}` → `{state: 'active'|'away', at: timestamp}`.
- Heartbeat & sinyal dikirim dari `usePushNotifications` composable yang dipasang di `AuthenticatedLayout`.

### 3.2. Komponen backend

| Komponen | Tanggung jawab |
|---|---|
| `app/Models/PushSubscription.php` | Model tabel `push_subscriptions`; relasi `User::subscriptions()` |
| `app/Services/Push/PushNotificationService.php` | Kirim payload ke semua subscription user; bahasa dari `user->locale`; hapus endpoint 410; hormati `user->push_notifications` |
| `app/Services/Push/PresenceService.php` | `markActive` / `markAway` / `isAway` (threshold 2 menit) via cache |
| `app/Services/Push/PushPayloadBuilder.php` | Bangun array payload `{title, body, url, tag, icon, data}` dari template lokal + context |
| `app/Jobs/SendPushNotificationJob.php` | Queue worker pengiriman (tries 2, timeout 60, backoff [10]) |
| `app/Jobs/CheckBudgetAlertsJob.php` | Hitung spend per grup bulan berjalan; kirim push over-budget sekali per grup |
| `app/Jobs/CheckLoanRemindersJob.php` | Per-user: cari hutang/piutang jatuh tempo (D-1 & H), kirim push, catat ke `loan_reminders` |
| `app/Services/Loan/DueDateService.php` | Logika `next_due_date` (fixed/monthly/daily) + `upcomingDueDates(user)` — diekstrak dari `DashboardController::index` agar satu sumber kebenaran |
| `app/Http/Controllers/PushNotificationController.php` | `subscribe`, `unsubscribe`, `presence` |
| `app/Console/Commands/GenerateVapidKeysCommand.php` | `php artisan notification:generate-vapid-keys` — generate + print pasangan VAPID |
| `routes/console.php` | Schedule `loan:send-reminders` harian 07:00 + command; + 3 route notifikasi di web.php |

### 3.3. Trigger push

| Event | Tempat hook | Isi notif (id/en) | Deep link |
|---|---|---|---|
| Chat reply sukses | `ProcessChatMessageJob::handle()` (sebelum return) | "Balasan chat kamu sudah siap" + preview teks (potong 60 char) | `/chat` |
| Chat reply gagal | `ProcessChatMessageJob::handle()` (blok catch) | "Balasan chat gagal diproses" | `/chat` |
| Generate budget sukses (manual) | `GenerateBudgetJob::handle()` (sebelum return) | "Budget {Bulan} kamu sudah dibuat" | `/budgeting` |
| Generate budget gagal (manual) | `GenerateBudgetJob::failed()` | "Generate budget gagal" | `/budgeting` |
| Budget auto-generate tgl 1 | command `budget:auto-generate` (`routes/console.php`) setelah generate per user (sukses/gagal) | sama seperti manual | `/budgeting` |
| Budget over | `ProcessTransactionAction` (setelah create/confirm sukses) → dispatch `CheckBudgetAlertsJob` | "Budget {nama grup} sudah terlewati bulan ini" | `/budgeting` |
| Jatuh tempo D-1 | command `loan:send-reminders` (harian 07:00) → `CheckLoanRemindersJob` per user | "Besok {subject} jatuh tempo" (+ nominal & tipe hutang/piutang) | `/loans/hutang` atau `/loans/piutang` |
| Jatuh tempo H | command `loan:send-reminders` (harian 07:00) → `CheckLoanRemindersJob` per user | "Hari ini {subject} jatuh tempo" (+ nominal & tipe) | `/loans/hutang` atau `/loans/piutang` |

### 3.5. Pengingat jatuh tempo — dedupe & cakupan

- Tabel baru `loan_reminders`: `id`, `user_id` FK cascade, `subject` (uppercase), `loan_type` (`debt`|`receivable`), `reminder_type` (`day_before`|`due_date`), `due_date` (date), `sent_at` (timestamp), `created_at/updated_at`; **unique** `(user_id, subject, loan_type, reminder_type, due_date)`.
- `CheckLoanRemindersJob(user, date)`: panggil `DueDateService::dueTransactions(user, date)` → ambil transaksi `LOAN`/`RECEIVABLE` dengan `due_date_type` not null yang `next_due_date`-nya = `date` atau `date + 1`; sisa saldo per `subject` dihitung via `CalculatesDebtAndReceivable` (balance > 0 → aktif); untuk tiap subject aktif → upsert `loan_reminders` (tanpa duplikat) → jika baris baru (belum diingatkan) → push (gate presensi).
- Rekurensi: `monthly`/`daily` otomatis ter-remind tiap instance berikutnya karena `due_date` baru; `fixed` sekali saja (dedupe by design).
- Perubahan `DashboardController` agar memakai `DueDateService` (refactor kecil, perilaku tidak berubah).

Aturan bersama di semua trigger: panggil helper `PushGate::dispatchIfAway(user, payload)` — jika user **aktif**, job tidak di-dispatch (toast di frontend yang menangani).

### 3.4. Budget over — dedupe

- Kolom baru `over_alert_sent_at` (nullable timestamp) di `budget_groups`.
- `CheckBudgetAlertsJob` (user, month, year): hitung spend per grup dari `transaction_logs` (is_cleared, bukan cancelled, kategori anggota grup, periode berjalan); jika `spent > target_amount` && `over_alert_sent_at IS NULL` → kirim push + set kolom. Idempotent oleh desain.
- Dipicu dari `ProcessTransactionAction` (satu-satunya corong pembuatan transaksi: web form, chat, evidence) — sekali per aksi sukses.

## 4. Detail Implementasi

### 4.1. Migrasi

1. `create_push_subscriptions_table`:
   - `id`, `user_id` FK cascade, `endpoint` text **unique**, `p256dh` text, `auth` text, `user_agent` nullable, timestamps.
2. `add_notification_preferences_to_users_table`:
   - `email_notifications` boolean default `true`
   - `push_notifications` boolean default `true`
3. `add_over_alert_sent_at_to_budget_groups_table`:
   - `over_alert_sent_at` timestamp nullable.
4. `create_loan_reminders_table` (lihat §3.5): unique `(user_id, subject, loan_type, reminder_type, due_date)`.

### 4.2. Model

- `PushSubscription`: `$fillable` (user_id, endpoint, p256dh, auth, user_agent); relasi `user()`.
- `User`: relasi `subscriptions()` (hasMany); casts `email_notifications`/`push_notifications` boolean; fillable diperbarui.
- `BudgetGroup`: fillable + cast `over_alert_sent_at` datetime.
- `LoanReminder`: fillable + casts (`due_date` date, `sent_at` datetime); relasi `user()`.

### 4.2.1. DueDateService (refactor bersama)

- Ekstrak logika `next_due_date` dari `DashboardController::index` (baris ~301-322):
  - `fixed` → `due_date`
  - `monthly` → tanggal `due_date_interval` bulan berikutnya
  - `daily` → `due_date + interval` hari
- API: `nextDueDate(TransactionLog $trx): ?Carbon` dan `dueTransactions(User $user, Carbon $date): Collection` (transaksi tipe `LOAN`/`RECEIVABLE` aktif dengan jatuh tempo target).
- `DashboardController` diubah memakai service ini (output identik).

### 4.3. Konfigurasi

- `.env` (baru): `VAPID_PUBLIC_KEY=`, `VAPID_PRIVATE_KEY=`, `VAPID_SUBJECT=mailto:admin@bendaharaku.id` — ditambahkan ke `.env.example` juga.
- `config/services.php`: blok `webpush` (vapid_public_key, vapid_private_key, vapid_subject).
- `config/bendaharaku.php`: blok `push` → `presence_ttl_seconds` (default 120), `chat_preview_length` (60), `over_alert` di-scope ke `budget` (boleh kosong; konstanta default).
- Composer: `minishlink/web-push` (^9 atau versi stabil terbaru).

### 4.4. Routes (web, auth, CSRF aktif)

```
POST /notifications/subscribe    → validasi endpoint (url), p256dh, auth (base64) → upsert per (user, endpoint)
POST /notifications/unsubscribe  → validasi endpoint → delete
POST /notifications/presence     → validasi state in:active,away → PresenceService
```

PATCH `settings.application.notifications.update` (route lama) diubah:
- Validasi tetap (`email_notifications`, `push_notifications` boolean) → simpan ke users → `SettingsChangeLogger::logChange` tetap dipanggil.

### 4.5. Frontend

1. `public/sw.js` — service worker statis:
   - `push` event → `event.waitUntil(self.registration.showNotification(data.title, {body, icon, data:{url}, tag, badge}))`
   - `notificationclick` → `clients.openWindow(url)` / fokus tab yang ada; close notif
   - Tanpa cache logic apa pun (hindari konflik dengan aset Vite).
2. `resources/js/Composables/usePushNotifications.js`:
   - `isSupported` (serviceWorker + PushManager + Notification)
   - `permission` reaktif (`Notification.permission`)
   - `enablePush()` → `Notification.requestPermission()` → `pushManager.subscribe({userVisibleOnly:true, applicationServerKey: Uint8Array(VAPID public)})` → `POST /notifications/subscribe`; sukses → toast
   - `disablePush()` → `pushManager.getSubscription().unsubscribe()` + `POST /notifications/unsubscribe` → toast
   - `isSubscribed` reaktif (bandingkan dengan state server via prop/GET)
   - Presence: listener `visibilitychange` → POST presence active/away (throttle 2s); `setInterval` heartbeat 60s saat visible; dipasang sekali di `AuthenticatedLayout` (module-level, pindah halaman aman seperti `useChatPending`)
3. `resources/js/Pages/Settings/Application/Notifications.vue` — ditulis ulang:
   - Props: `email_notifications`, `push_notifications`, `vapid_public_key`, `browser_supported`, `browser_permission`
   - Toggle email → PATCH (persist nyata)
   - Toggle push:
     - Browser tidak support → disabled + keterangan
     - Permission `denied` → tombol "Buka pengaturan browser" (instruksi: beri izin notifikasi di pengaturan situs)
     - Permission `prompt`/`granted` belum subscribe → request + subscribe saat toggle di-on
     - On → `enablePush()`, Off → `disablePush()`
   - State subscribe gagal → toast error
4. `app.js`: `register('/sw.js')` saat app siap (guard: isSupported).
5. i18n (`resources/js/i18n/locales/id.js` & `en.js`): keys baru di `settings.application.notifications.*` (state permission, tombol, pesan) — mengikuti blok yang sudah ada.
6. Server payload title/body memakai `lang/{id,en}/push.php` (file baru) — dipilih via `user->locale`.

### 4.6. Trigger detail

- `ProcessChatMessageJob`:
  - Sukses: bangun payload `chat_reply_ready` (title dari bot name, body = preview teks balasan, url `/chat`, tag `chat:{messageId}`)
  - Gagal: payload `chat_reply_failed` (body dari `__('push.chat_reply_failed')`, url `/chat`)
  - Panggil `PushGate::dispatchIfAway(...)` — tidak menambah latency respons job (dispatch queue).
- `GenerateBudgetJob`:
  - Sukses: payload `budget_created` (title "Budget {Bulan} siap", body, url `/budgeting`)
  - `failed()`: payload `budget_generation_failed`, url `/budgeting`
- Command `budget:auto-generate` (`routes/console.php`): setelah generate sukses per user → payload sama seperti job; pada kegagalan per-user → payload gagal (jangan hentikan loop). 
  - Catatan: command tetap sinkron (di luar scope untuk refactor async), hanya menambah push.
- `ProcessTransactionAction`: setelah create/confirm sukses → `CheckBudgetAlertsJob::dispatch($userId, now()->month, now()->year)` (job sendiri yang hitung & dedupe; tanpa cek presensi karena tidak tahu tipe notif).
- Schedule harian (routes/console.php): `loan:send-reminders` → `dailyAt('07:00')` + `withoutOverlapping()`; command mendispatch `CheckLoanRemindersJob` per user (hanya user dengan `push_notifications = true` dan punya transaksi jatuh tempo).

### 4.7. PushNotificationService

```
sendToUser(User $user, array $payload): void
  - jika !$user->push_notifications → return
  - daftar subscription user; jika kosong → return
  - untuk tiap sub: build WebPush\Notification (TTL 86400, VAPID auth), kirim
  - 410 Gone / 404 Not Found → hapus subscription dari DB
  - error lain → log warning (jangan gagalkan job utama)
```

`SendPushNotificationJob` membungkus panggilan ini; `$payload` diserialisasi (array aman untuk queue database).

## 5. Penanganan Error

| Skenario | Perilaku |
|---|---|
| Subscription mati (410/404) | Hapus dari DB, lanjut ke subscription lain |
| Kirim push gagal (network) | Log warning; job selesai normal (tries 2 tetap berlaku di job) |
| Browser tidak mendukung push | UI disabled + keterangan di settings |
| Permission ditolak user | UI tampilkan instruksi membuka pengaturan browser |
| User menonaktifkan push | Service cek flag, tidak kirim; UI unsubscribe |
| VAPID key kosong (belum generate) | `subscribe`/kirim → exception → toast error "VAPID belum dikonfigurasi" (frontend membaca `vapid_public_key` null → toggle push dinonaktifkan) |

## 6. Testing

- `tests/Feature/Push/PushSubscriptionControllerTest`: subscribe (store/upsert), unsubscribe, presence (active/away + kevalidan state)
- `tests/Feature/Push/SettingsNotificationsTest`: PATCH settings persist kolom users + GET halaman memuat props
- `tests/Feature/Push/PushNotificationServiceTest`: mock `WebPush\WebPush` — kirim ke semua sub; hapus 410; skip jika flag mati
- `tests/Feature/Push/PresenceServiceTest`: markActive/markAway/isAway + kedaluwarsa threshold
- `tests/Feature/Push/ChatPushTriggerTest`: job chat sukses → dispatch `SendPushNotificationJob` saat away; tidak saat aktif (mock PresenceService)
- `tests/Feature/Push/BudgetOverAlertTest`: grup + item + transaksi over → `CheckBudgetAlertsJob` kirim sekali + set `over_alert_sent_at`; jalankan ulang → tidak kirim (dedupe)
- `tests/Feature/Push/GenerateBudgetPushTest`: `GenerateBudgetJob` sukses/gagal → push ter-dispatch saat away
- `tests/Feature/Push/LoanReminderTest`: D-1 dan H-day memicu push (mock PresenceService); dedupe (jalankan 2x → 1 push); hutang lunas tidak diingatkan; monthly berulang; `loan_reminders` terisi; command `loan:send-reminders` mendispatch job per user
- `tests/Feature/Push/DueDateServiceTest`: next_due_date fixed/monthly/daily
- Unit: `PushPayloadBuilderTest` (locale id/en, truncate preview)

Semua test memakai `Queue::fake` + mock `PresenceService`/`WebPush\WebPush` — tidak ada panggilan jaringan nyata.

## 7. Operasional

1. `composer require minishlink/web-push`
2. `php artisan notification:generate-vapid-keys` (sekali di mesin deploy) → isi `.env`
3. `php artisan migrate`
4. Build frontend + jalankan worker queue (`bendaharaku_queue` sudah ada di docker-compose)
5. WAJIB HTTPS (sudah terpenuhi: `widihhh.my.id`)

## 8. Di Luar Scope

- Pengirim email nyata (toggle `email_notifications` hanya disimpan, UI-only — kondisi sudah begitu)
- FCM / app native
- Notifikasi budget over real-time tanpa jeda (cek per aksi transaksi = praktis real-time; biaya rendah karena hanya hitung grup bulan berjalan)
- Presence multi-tab (asumsi 1 tab aktif; sinkronisasi antar tab di luar scope)
- iOS Safari (dapat berjalan tapi tidak diuji; fokus Android)
- Notifikasi in-app/persisten (semua pengingat via push browser + widget Dashboard yang sudah ada; tidak ada inbox notifikasi)
