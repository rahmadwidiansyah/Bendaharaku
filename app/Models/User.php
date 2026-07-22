<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'telegram_id',
        'whatsapp_number',
        'google_id',
        'avatar',
        'allow_negative_balance',
        'locale',
        'timezone',
        'bot_name',
        'bot_avatar',
        'date_format',
        'accent_color',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function transactionLogs(): HasMany
    {
        return $this->hasMany(TransactionLog::class);
    }

    public function transactionDrafts(): HasMany
    {
        return $this->hasMany(TransactionDraft::class);
    }

    public function aiCredentials(): HasMany
    {
        return $this->hasMany(UserAiCredential::class);
    }

    public function aiPreferences(): HasMany
    {
        return $this->hasMany(UserAiPreference::class);
    }

    /**
     * Relasi untuk mengambil preferensi AI yang bertindak sebagai provider aktif.
     */
    public function activeAiPreference(): HasOne
    {
        return $this->hasOne(UserAiPreference::class)->where('is_active_provider', true);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function monthlyReports(): HasMany
    {
        return $this->hasMany(MonthlyReport::class);
    }

    /**
     * Conversation aktif user saat ini.
     */
    public function activeConversation(): HasOne
    {
        return $this->hasOne(Conversation::class)
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->latest();
    }

    /**
     * Nama bot yang dipersonalisasi, fallback ke 'Ken-Chan'.
     */
    public function getBotDisplayNameAttribute(): string
    {
        return $this->bot_name ?? 'Ken-Chan';
    }

    /**
     * URL avatar bot (storage) atau null jika belum diatur.
     */
    public function getBotAvatarUrlAttribute(): ?string
    {
        return $this->bot_avatar
            ? asset('storage/'.$this->bot_avatar)
            : null;
    }

    /**
     * URL avatar user (storage) atau null jika belum diatur.
     * Field `avatar` bisa berisi path relatif storage atau URL absolut (Google OAuth).
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) {
            return null;
        }
        // Jika sudah URL absolut (http/https — dari Google OAuth), return as-is
        if (str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }

        // Jika path relatif storage
        return asset('storage/'.$this->avatar);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'allow_negative_balance' => 'boolean',
        ];
    }

    /**
     * Jalankan aksi otomatis setelah User baru terdaftar
     */
    protected static function booted(): void
    {
        static::created(function (User $user) {

            // 1. Buatkan Dompet (Terintegrasi dengan Config SSOT)
            $user->wallets()->createMany([
                ['name' => config('bendaharaku.system_wallets.debt', 'System Hutang'), 'balance' => 0, 'group_type' => 'System', 'icon' => '🏦', 'keyword' => 'sistem hutang'],
                ['name' => config('bendaharaku.system_wallets.receivable', 'System Piutang'), 'balance' => 0, 'group_type' => 'System', 'icon' => '🏦', 'keyword' => 'sistem piutang'],
                ['name' => config('bendaharaku.system_wallets.external', 'External System'), 'balance' => 0, 'group_type' => 'System', 'icon' => '🌐', 'keyword' => 'external'],
                ['name' => config('bendaharaku.system_wallets.merchant', 'Merchant System'), 'balance' => 0, 'group_type' => 'System', 'icon' => '🏪', 'keyword' => 'merchant'],
                ['name' => 'Dompet Cash', 'balance' => 0, 'group_type' => 'Liquid', 'icon' => '💵', 'keyword' => 'cash, tunai, dompet'],
            ]);

            // 2. Ambil semua ID tipe transaksi yang sudah kita buat di Seeder
            $incomeType = TransactionType::where('name', 'Income')->first();
            $expenseType = TransactionType::where('name', 'Expense')->first();
            $transferType = TransactionType::where('name', 'Transfer')->first();
            $debtType = TransactionType::where('name', 'Debt')->first();
            $receivableType = TransactionType::where('name', 'Receivable')->first();

            // 3. Buatkan Kategori Murni Transfer (Pindah Saldo)
            if ($transferType) {
                $user->categories()->create([
                    'category_name' => 'Pindah Saldo',
                    'type_id' => $transferType->id,
                    'icon' => '🔄',
                    'keyword' => 'transfer, pindah uang, pindahkan saldo, kirim saldo, kirim uang, pindah semua saldo, transfer semua saldo, mutasi, pindah saldo',
                    'system_key' => 'TRANSFER',
                ]);
            }

            // 4. Buatkan Kategori Khusus Hutang (Debt)
            if ($debtType) {
                $user->categories()->createMany([
                    ['category_name' => 'Dapat Hutangan', 'type_id' => $debtType->id, 'icon' => '📥', 'keyword' => 'dapat hutangan, ngutang, pinjam duit, ditalangin, kasbon, pinjol, minjem uang, pinjam uang, dapet pinjeman, hutang, utang, pinjam, minjam, pinjem, berhutang, berutang', 'system_key' => 'LOAN'],
                    ['category_name' => 'Bayar Cicilan Hutang', 'type_id' => $debtType->id, 'icon' => '💸', 'keyword' => 'bayar utang, bayar hutang, lunasin, nyicil, cicilan, balikin duit, balikin uang, ganti duit, nutup utang, bayar kasbon, bayar pinjol, lunasi hutang, lunasin utang, balikin pinjaman, melunasi pinjaman, kembalikan hutang', 'system_key' => 'DEBT_PAYMENT'],
                ]);
            }

            // 5. Buatkan Kategori Khusus Piutang (Receivable)
            if ($receivableType) {
                $user->categories()->createMany([
                    ['category_name' => 'Ngasih Piutang', 'type_id' => $receivableType->id, 'icon' => '📤', 'keyword' => 'ngasih piutang, minjemin, ngutangin, dipinjem, dipinjam, nalangin, kasih utang, pinjemin, ngasih pinjaman, kasih pinjam, meminjamkan, memberi pinjaman', 'system_key' => 'RECEIVABLE'],
                    ['category_name' => 'Terima Bayar Piutang', 'type_id' => $receivableType->id, 'icon' => '🤑', 'keyword' => 'terima bayar piutang, dibayar, utang dibayar, utang lunas, ditagih, nagih utang, teman balikin, uang kembali, pelunasan teman, piutang dibayar, dibayar hutang, dibayar utang, balikin uang, balikin pinjaman, mengembalikan pinjaman, menerima pembayaran hutang, menerima pembayaran piutang', 'system_key' => 'RECEIVABLE_PAYMENT'],
                ]);
            }

            // 6. Kategori Starter Expense (Pengeluaran)
            if ($expenseType) {
                $user->categories()->createMany([
                    [
                        'category_name' => 'Makan & Minum',
                        'type_id' => $expenseType->id,
                        'icon' => '🍔',
                        'keyword' => 'makan, minum, gofood, kfc, warkop, warteg, resto, kantin, kuliner, bekal',
                    ],
                    [
                        'category_name' => 'Transportasi',
                        'type_id' => $expenseType->id,
                        'icon' => '🚗',
                        'keyword' => 'bensin, gojek, grab, parkir, pertalite, pertamax, tol, bus, kereta, travel',
                    ],
                    [
                        'category_name' => 'Pendidikan & Kuliah',
                        'type_id' => $expenseType->id,
                        'icon' => '📚',
                        'keyword' => 'ukt, spp, buku, fotocopy, print, alat tulis, kursus, seminar, skripsi, pendaftaran',
                    ],
                    [
                        'category_name' => 'Kos & Tempat Tinggal',
                        'type_id' => $expenseType->id,
                        'icon' => '🏠',
                        'keyword' => 'bayar kos, kontrakan, iuran, kebersihan, keamanan, sampah, listrik kos, air, laundry, sewa',
                    ],
                    [
                        'category_name' => 'Belanja Dapur & Groceries',
                        'type_id' => $expenseType->id,
                        'icon' => '🛒',
                        'keyword' => 'beras, galon, gas, sayur, minyak goreng, bumbu, telur, mie instan, sabun cuci, tisu',
                    ],
                    [
                        'category_name' => 'Jajan & Nongkrong',
                        'type_id' => $expenseType->id,
                        'icon' => '☕',
                        'keyword' => 'kopi, cemilan, snack, es krim, boba, kafe, bioskop, chill, hangout',
                    ],
                    [
                        'category_name' => 'Perawatan Diri',
                        'type_id' => $expenseType->id,
                        'icon' => '🧴',
                        'keyword' => 'skincare, sabun, sampo, potong rambut, vitamin, obat, klinik, parfum, handbody, facial',
                    ],
                    [
                        'category_name' => 'Pakaian & Aksesoris',
                        'type_id' => $expenseType->id,
                        'icon' => '👕',
                        'keyword' => 'baju, celana, sepatu, tas, jaket, kaos kaki, jam tangan, topi, hijab, kacamata',
                    ],
                    [
                        'category_name' => 'Servis & Perbaikan',
                        'type_id' => $expenseType->id,
                        'icon' => '🛠️',
                        'keyword' => 'bengkel, servis motor, servis laptop, flash hp, ganti oli, ban bocor, sparepart, benerin pc, mekanik, perbaikan',
                    ],
                    [
                        'category_name' => 'Pajak & Legalitas',
                        'type_id' => $expenseType->id,
                        'icon' => '📄',
                        'keyword' => 'pajak motor, stnk, sim, pbb, materai, legalisir, administrasi, denda, paspor, npwp',
                    ],
                    [
                        'category_name' => 'Sosial & Hadiah',
                        'type_id' => $expenseType->id,
                        'icon' => '🎁',
                        'keyword' => 'sedekah, zakat, kado, sumbangan, kondangan, donasi, infaq, hampers, traktiran, amal',
                    ],
                    [
                        'category_name' => 'Pulsa & Internet',
                        'type_id' => $expenseType->id,
                        'icon' => '📱',
                        'keyword' => 'kuota, pulsa, paket data, wifi, indihome, langganan, top up, voucher, kartu perdana, roaming',
                    ],
                    [
                        'category_name' => 'Langganan Digital',
                        'type_id' => $expenseType->id,
                        'icon' => '💳',
                        'keyword' => 'spotify, youtube premium, netflix, langganan, auto debit, gemini, ai, aplikasi, cloud, hosting, disney, zoom',
                    ],
                    [
                        'category_name' => 'Tagihan & Utilitas',
                        'type_id' => $expenseType->id,
                        'icon' => '⚡',
                        'keyword' => 'listrik, air, pln, pdam, token, pascabayar, prabayar, pam, iuran, meteran, bpjs, iuran sampah',
                    ],
                ]);
            }

            // 7. Kategori Starter Income (Pemasukan)
            if ($incomeType) {
                $user->categories()->createMany([
                    [
                        'category_name' => 'Gaji / Pendapatan',
                        'type_id' => $incomeType->id,
                        'icon' => '💰',
                        'keyword' => 'gaji, bonus, thr, upah, komisi, insentif, lembur, bayaran, tunjangan, pendapatan tetap',
                    ],
                    [
                        'category_name' => 'Pendapatan Sampingan',
                        'type_id' => $incomeType->id,
                        'icon' => '🚀',
                        'keyword' => 'freelance, proyek, jualan, dagang, side hustle, jasa, desain, koding, cuan, bisnis kecil',
                    ],
                    [
                        'category_name' => 'Bunga & Hasil Aset',
                        'type_id' => $incomeType->id,
                        'icon' => '📈',
                        'keyword' => 'bunga bank, bagi hasil, dividen, profit, saham, reksadana, investasi, kripto, kupon, imbal hasil',
                    ],
                    [
                        'category_name' => 'Pendapatan Lain-lain',
                        'type_id' => $incomeType->id,
                        'icon' => '🍃',
                        'keyword' => 'refund, nemu duit, hadiah, cashback, kembalian, hibah, angpao, warisan, klaim, temuan',
                    ],
                ]);
            }

        });
    }
}
