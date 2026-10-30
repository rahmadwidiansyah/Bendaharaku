---
name: bendaharaku-admin
description: Use when working on Bendaharaku's /admin dashboard — admin pages, tables, sorting, filters, pagination, active-user metrics, charts, permissions, M3 tokens, or auditing the admin panel. Triggers on "admin dashboard", "admin panel", "user management", "audit logs", "AI logs", "support tools", "sortable table", "active users".
---

# Bendaharaku Admin Dashboard

## Peta kode

- Routes: `routes/web.php` (grup `admin`, prefix `/admin`)
- Controllers: `app/Http/Controllers/Admin/`
  - `DashboardController.php`
  - `UserController.php`
  - `SupportController.php`
  - `AiLogController.php`
  - `AuditLogController.php`
- Middleware: `app/Http/Middleware/AdminMiddleware.php`, `EnsureUserIsActive.php`, `TrackUserActivity.php`
- Helper: `app/Support/Admin/SortableTable.php`
- Layout: `resources/js/Layouts/AdminLayout.vue`
- Pages: `resources/js/Pages/Admin/**`
- Components: `resources/js/Components/Admin/**`
- Composable: `resources/js/Composables/useAdminTable.js`
- Audit model: `app/Models/AdminAuditLog.php`
- Permissions: `database/seeders/RolesAndPermissionsSeeder.php`
- E2E: `tests/e2e/admin_dashboard.spec.js`, `tests/e2e/global-setup.js`

Route penting: `/admin`, `/admin/users`, `/admin/users/{user}`, `/admin/support`, `/admin/support/user/{user}`, `/admin/ai/logs`, `/admin/ai/logs/{id}`, `/admin/audit-logs`, `/admin/audit-logs/{id}`.

## Aturan wajib

1. **M3 tokens only.** Tidak boleh `bg-white`, `bg-gray-*`, `text-gray-*`, `bg-green-*`, `bg-red-*`, `bg-blue-*` di halaman admin. Gunakan `rgb(var(--m3-color-*-rgb))` untuk Tailwind atau `var(--m3-color-*)` untuk CSS scoped.
2. **Lucide Vue Next** untuk ikon. Tanpa emoji/SVG inline.
3. **Server-side** pagination, sorting, filtering. Jangan load semua baris ke browser.
4. **Whitelist** kolom sort & nilai `direction`; fallback ke default, jangan pernah interpolasi input ke `orderBy`.
5. **Pagination wajib** `->withQueryString()`; jangan membangun URL `path + '?page='` manual.
6. **`per_page`** hanya `[10, 15, 25, 50, 100]`.
7. **Audit log** setiap aksi sensitif (status change, akses data user, export).
8. **Jangan pernah** mengirim secret/API key ke frontend.
9. **Suspended user harus ditolak** di login dan middleware.
10. **Active user ≠ verified user.** Gunakan `last_active_at` / `user_activity_daily`.

## Kontrak sorting

Query: `?sort=<key>&direction=asc|desc&page=&per_page=&<filters>`.
Backend helper: `App\Support\Admin\SortableTable::apply()`.
Frontend: `SortableTh.vue` + `useAdminTable.js`.
`th` wajib punya `aria-sort`. Klik 3× = asc → desc → default. Sorting reset `page=1` dan mempertahankan filter.

## Jebakan yang sudah terbukti

- `User` **tidak** punya relasi `transactions`; gunakan `transactionLogs()`.
- Tabel `categories` memakai `category_name`, bukan `name`.
- Tabel `wallets` **tidak** punya kolom `currency`.
- `activity_logs` **tidak** punya kolom `subject`; gunakan `subject_type`/`subject_id`.
- Route AI logs adalah `/admin/ai/logs` (bukan `/admin/ai-logs`).
- `$inertia` tersedia sebagai global property Inertia; jangan mengimpor ulang.
- Session driver `file`; tidak ada tabel `sessions`.
- `user_activity_logs` dipangkas 7 hari; jangan pakai untuk MAU.
- Asset di-bake ke image Docker: setelah `npm run build`, jalankan `docker compose build app && docker compose up -d app`.
- Jangan memakai `prompt()`/`confirm()` untuk aksi admin; gunakan modal komponen.

## Verifikasi

```bash
npm run build
npm run lint:colors                 # 0 pelanggaran di Pages/Admin
docker compose build app && docker compose up -d app
docker compose exec app php artisan test
npx playwright test --project=admin-light
```

Smoke test route admin (Playwright): pastikan semua route mengembalikan 200 untuk owner dan tidak ada console error.

## Definition of done

- [ ] Semua route admin 200 (kecuali 403 yang disengaja).
- [ ] Sorting berfungsi di semua kolom utama + `aria-sort`.
- [ ] Filter/search tetap saat pagination.
- [ ] Angka active user sesuai definisi (MAU 30d, exclude staff & suspended).
- [ ] Tidak ada pelanggaran warna M3.
- [ ] Dark mode & mobile 390px terverifikasi.
- [ ] Tidak ada data finansial bocor ke role support.
- [ ] Audit log tercatat untuk aksi sensitif.
- [ ] Test backend + E2E lulus.
