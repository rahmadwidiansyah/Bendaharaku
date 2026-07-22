<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ProcessTransactionAction
{
    // Pada app/Actions/ProcessTransactionAction.php

    /**
     * Membuat data transaksi baru, memutasi saldo dompet, dan mencatat log transaksi.
     * Concurrency safe dengan pessimistics locking (lockForUpdate).
     */
    public function create(array $data, int $userId, string $sourcePrefix = 'TRX'): TransactionLog
    {
        if ($data['source_wallet_id'] === $data['destination_wallet_id']) {
            throw new InvalidArgumentException('Transaksi gagal: Dompet asal dan dompet tujuan tidak boleh sama.');
        }

        if ($data['amount'] <= 0) {
            throw new InvalidArgumentException('Transaksi gagal: Nominal transaksi harus lebih besar dari nol.');
        }

        return DB::transaction(function () use ($data, $userId, $sourcePrefix) {
            $user = User::findOrFail($userId);

            // Resolve kategori: Transfer, Debt, Receivable menggunakan system category otomatis.
            // Category_id dari form bisa null untuk tipe-tipe tersebut karena sudah ditentukan sistem.
            $type = strtolower($data['transaction_type'] ?? '');

            if ($type === 'transfer' || ($type !== 'debt' && $type !== 'receivable' && empty($data['category_id']))) {
                $category = $this->resolveTransferCategory($userId);
            } elseif (in_array($type, ['debt', 'receivable'])) {
                // Debt/Receivable: auto-resolve dari system_key berdasarkan debt_sub_type
                $subType = $data['debt_sub_type'] ?? null;
                $category = $this->resolveSystemCategory($userId, $type, $subType, $data['category_id'] ?? null);
            } else {
                $category = Category::where('user_id', $userId)->where('id', $data['category_id'])->firstOrFail();
            }

            $source = Wallet::where('user_id', $userId)->where('id', $data['source_wallet_id'])->lockForUpdate()->firstOrFail();
            $destination = Wallet::where('user_id', $userId)->where('id', $data['destination_wallet_id'])->lockForUpdate()->firstOrFail();

            $mainWallet = ($source->group_type !== 'System') ? $source : $destination;
            $balanceBefore = $mainWallet->balance;

            // Eksekusi mutasi saldo hanya jika transaksi berstatus cleared
            $isCleared = $data['is_cleared'] ?? true;
            if ($isCleared) {
                $this->applyTransaction($source, $destination, $data['amount'], $user->allow_negative_balance);
            }

            // Jika masuk draft (false), balance After = balance Before karena belum termutasi
            $balanceAfter = $isCleared ? Wallet::where('id', $mainWallet->id)->value('balance') : $balanceBefore;

            $subjectInput = isset($data['subject']) ? strtoupper(trim($data['subject'])) : '';
            $finalSubject = $subjectInput === '' ? '-' : $subjectInput;

            return TransactionLog::create([
                'reference_number' => $sourcePrefix.'-'.Str::ulid(),
                'user_id' => $userId,
                'date' => $data['date'],
                'type_id' => $category->type_id,
                'category_id' => $category->id,
                'source_wallet_id' => $source->id,
                'destination_wallet_id' => $destination->id,
                'amount' => $data['amount'],
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'subject' => $finalSubject,
                'notes' => $data['notes'] ?? null,
                'is_cleared' => $isCleared,
                'due_date' => $data['due_date'] ?? null,
                'due_date_type' => $data['due_date_type'] ?? null,
                'due_date_interval' => $data['due_date_interval'] ?? null,
            ]);
        });
    }

    /**
     * Memperbarui data transaksi, mengembalikan efek saldo lama, dan menerapkan efek saldo baru.
     * Proteksi penuh dari race condition saat pemulihan saldo lama dan penerapan saldo baru.
     */
    public function update(TransactionLog $transaction, array $data): TransactionLog
    {
        $userId = $transaction->user_id;

        if ($data['source_wallet_id'] === $data['destination_wallet_id']) {
            throw new InvalidArgumentException('Update gagal: Dompet asal dan dompet tujuan tidak boleh sama.');
        }

        if ($data['amount'] <= 0) {
            throw new InvalidArgumentException('Update gagal: Nominal transaksi harus lebih besar dari nol.');
        }

        return DB::transaction(function () use ($transaction, $data, $userId) {
            $user = User::findOrFail($userId);

            // Resolve kategori — sama dengan create()
            $type = strtolower($data['transaction_type'] ?? '');

            if ($type === 'transfer' || ($type !== 'debt' && $type !== 'receivable' && empty($data['category_id']))) {
                $newCategory = $this->resolveTransferCategory($userId);
            } elseif (in_array($type, ['debt', 'receivable'])) {
                $subType = $data['debt_sub_type'] ?? null;
                $newCategory = $this->resolveSystemCategory($userId, $type, $subType, $data['category_id'] ?? null);
            } else {
                $newCategory = Category::where('user_id', $userId)->where('id', $data['category_id'])->firstOrFail();
            }

            // 1. Rollback efek saldo dari transaksi lama dengan baris terkunci (lockForUpdate)
            if ($transaction->is_cleared) {
                $oldSource = Wallet::where('user_id', $userId)->where('id', $transaction->source_wallet_id)->lockForUpdate()->first();
                $oldDest = Wallet::where('user_id', $userId)->where('id', $transaction->destination_wallet_id)->lockForUpdate()->first();

                if ($oldSource && $oldDest) {
                    $this->reverseTransaction($oldSource, $oldDest, $transaction->amount, $user->allow_negative_balance);
                }
            }

            // 2. Kunci dan ambil state terbaru untuk Wallet Baru
            $newSource = Wallet::where('user_id', $userId)->where('id', $data['source_wallet_id'])->lockForUpdate()->firstOrFail();
            $newDest = Wallet::where('user_id', $userId)->where('id', $data['destination_wallet_id'])->lockForUpdate()->firstOrFail();

            // 3. Terapkan perubahan efek saldo dari transaksi baru
            $this->applyTransaction($newSource, $newDest, $data['amount'], $user->allow_negative_balance);

            // 4. Ambil instansiasi wallet utama untuk re-kalkulasi log
            $mainWallet = ($newSource->group_type !== 'System') ? $newSource : $newDest;
            $currentBalance = Wallet::where('id', $mainWallet->id)->value('balance');

            // 5. Normalisasi Subject Kosong/Spasi menjadi '-'
            $subjectInput = isset($data['subject']) ? strtoupper(trim($data['subject'])) : '';
            $finalSubject = $subjectInput === '' ? '-' : $subjectInput;

            // 6. Update rekaman data Transaction Log (Reference Number lama dipertahankan)
            $transaction->update([
                'date' => $data['date'],
                'category_id' => $newCategory->id,
                'type_id' => $newCategory->type_id,
                'source_wallet_id' => $newSource->id,
                'destination_wallet_id' => $newDest->id,
                'amount' => $data['amount'],
                'balance_before' => $currentBalance + $data['amount'],
                'balance_after' => $currentBalance,
                'subject' => $finalSubject,
                'notes' => $data['notes'] ?? null,
                'is_cleared' => true,
                'due_date' => $data['due_date'] ?? null,
                'due_date_type' => $data['due_date_type'] ?? null,
                'due_date_interval' => $data['due_date_interval'] ?? null,
            ]);

            return $transaction;
        });
    }

    /**
     * Mengkonfirmasi transaksi Draft menjadi transaksi terkonfirmasi.
     * Memutasi saldo dompet yang sebelumnya ditahan karena status draft.
     * Membersihkan tag [DRAFT AI] dan varian sejenis dari field notes.
     */
    public function confirm(TransactionLog $transaction): TransactionLog
    {
        if ($transaction->is_cleared) {
            throw new InvalidArgumentException('Konfirmasi gagal: Transaksi ini sudah terkonfirmasi.');
        }

        return DB::transaction(function () use ($transaction) {
            $user = User::findOrFail($transaction->user_id);

            $source = Wallet::where('user_id', $transaction->user_id)->where('id', $transaction->source_wallet_id)->lockForUpdate()->firstOrFail();
            $destination = Wallet::where('user_id', $transaction->user_id)->where('id', $transaction->destination_wallet_id)->lockForUpdate()->firstOrFail();

            // Terapkan mutasi saldo yang sebelumnya ditahan
            $this->applyTransaction($source, $destination, $transaction->amount, $user->allow_negative_balance);

            $mainWallet = ($source->group_type !== 'System') ? $source : $destination;
            $balanceAfter = Wallet::where('id', $mainWallet->id)->value('balance');

            // Bersihkan semua tag draft dari notes agar transaksi final tidak
            // menampilkan artefak draft seperti "[DRAFT AI]" atau "[DRAFT AI: wallet belum dipilih]"
            $cleanNotes = $transaction->notes !== null
                ? trim(preg_replace('/\s*\[DRAFT AI[^\]]*\]/u', '', $transaction->notes))
                : null;

            // Normalkan string kosong menjadi null
            if ($cleanNotes === '') {
                $cleanNotes = null;
            }

            $transaction->update([
                'is_cleared' => true,
                'balance_after' => $balanceAfter,
                'notes' => $cleanNotes,
            ]);

            return $transaction;
        });
    }

    /**
     * Menghapus transaksi, membalikkan kondisi saldo dompet, dan mengeksekusi soft delete.
     */
    public function delete(TransactionLog $transaction): bool
    {
        return DB::transaction(function () use ($transaction) {
            $allowNegativeBalance = (bool) User::whereKey($transaction->user_id)->value('allow_negative_balance');
            if ($transaction->is_cleared) {
                // Kunci baris data wallet saat pemulihan penghapusan transaksi
                $source = Wallet::where('user_id', $transaction->user_id)->where('id', $transaction->source_wallet_id)->lockForUpdate()->first();
                $destination = Wallet::where('user_id', $transaction->user_id)->where('id', $transaction->destination_wallet_id)->lockForUpdate()->first();

                if ($source && $destination) {
                    $this->reverseTransaction($source, $destination, $transaction->amount, $allowNegativeBalance);
                }
            }

            return $transaction->delete();
        });
    }

    /**
     * Resolve Transfer category milik user.
     * Setiap user sudah memiliki kategori Transfer (mis. "Transfer Saldo") yang bertipe Transfer.
     * Tidak perlu membuat kategori baru — cukup ambil kategori Transfer pertama milik user.
     * Jika belum ada (edge case), buat otomatis.
     */
    private function resolveTransferCategory(int $userId): Category
    {
        $transferType = TransactionType::where('name', 'Transfer')->first();

        if (! $transferType) {
            $transferType = TransactionType::create([
                'name' => 'Transfer',
                'keyword' => 'trf',
            ]);
        }

        // Cari kategori Transfer yang sudah ada milik user (TRANSFER system_key lebih diutamakan)
        $category = Category::where('user_id', $userId)
            ->where('system_key', 'TRANSFER')
            ->first();

        if (! $category) {
            $category = Category::where('user_id', $userId)
                ->where('type_id', $transferType->id)
                ->first();
        }

        // Fallback: buat jika belum ada (user baru / belum di-seed)
        if (! $category) {
            $category = Category::create([
                'user_id' => $userId,
                'type_id' => $transferType->id,
                'category_name' => 'Transfer Saldo',
                'icon' => '🔄',
                'keyword' => 'trf, transfer',
                'is_active' => true,
                'system_key' => 'TRANSFER',
            ]);
        }

        return $category;
    }

    /**
     * Resolve kategori sistem untuk Debt dan Receivable berdasarkan system_key.
     *
     * Prioritas:
     * 1. Jika category_id dari form sudah terisi, gunakan langsung (validasi milik user).
     * 2. Jika tidak, resolve berdasarkan system_key menggunakan debt_sub_type.
     *
     * Mapping system_key:
     *   Debt:
     *     sub_type = 'loan' / 'income'  → system_key = LOAN (Dapat Hutangan)
     *     sub_type = 'payment' / 'expense' → system_key = DEBT_PAYMENT (Bayar Hutang)
     *   Receivable:
     *     sub_type = 'give' / 'expense'  → system_key = RECEIVABLE (Kasih Piutang)
     *     sub_type = 'receive' / 'income' → system_key = RECEIVABLE_PAYMENT (Terima Bayar Piutang)
     */
    private function resolveSystemCategory(int $userId, string $type, ?string $subType, ?int $categoryId): Category
    {
        // Jika form sudah mengirim category_id yang valid, gunakan itu
        if (! empty($categoryId)) {
            $cat = Category::where('user_id', $userId)->where('id', $categoryId)->first();
            if ($cat) {
                return $cat;
            }
        }

        // Tentukan system_key berdasarkan tipe dan sub-tipe
        $systemKey = match (true) {
            $type === 'debt' && in_array($subType, ['loan', 'income', null]) => 'LOAN',
            $type === 'debt' && in_array($subType, ['payment', 'expense']) => 'DEBT_PAYMENT',
            $type === 'receivable' && in_array($subType, ['give', 'expense']) => 'RECEIVABLE',
            $type === 'receivable' && in_array($subType, ['receive', 'income']) => 'RECEIVABLE_PAYMENT',
            // Default fallbacks
            $type === 'debt' => 'LOAN',
            $type === 'receivable' => 'RECEIVABLE',
            default => null,
        };

        if ($systemKey !== null) {
            $cat = Category::where('user_id', $userId)->where('system_key', $systemKey)->first();
            if ($cat) {
                return $cat;
            }
        }

        // Last resort: ambil kategori pertama dari tipe tersebut
        $transactionType = TransactionType::where('name', ucfirst($type))->first();
        if ($transactionType) {
            $cat = Category::where('user_id', $userId)->where('type_id', $transactionType->id)->first();
            if ($cat) {
                return $cat;
            }
        }

        throw new InvalidArgumentException("Kategori sistem untuk tipe '{$type}' tidak ditemukan.");
    }

    /**
     * Method Internal: Menerapkan mutasi nominal sesuai preferensi saldo pengguna.
     */
    private function applyTransaction(Wallet $source, Wallet $destination, float|int $amount, bool $allowNegativeBalance): void
    {
        if (! $allowNegativeBalance && $source->group_type !== 'System' && ($source->balance - $amount) < 0) {
            throw new RuntimeException("Transaksi ditolak: Saldo pada dompet '{$source->name}' tidak mencukupi.");
        }

        $source->decrement('balance', $amount);
        $destination->increment('balance', $amount);
    }

    /**
     * Method Internal: Memulihkan kondisi saldo (kebalikan dari applyTransaction).
     */
    private function reverseTransaction(Wallet $source, Wallet $destination, float|int $amount, bool $allowNegativeBalance): void
    {
        if (! $allowNegativeBalance && $destination->group_type !== 'System' && ($destination->balance - $amount) < 0) {
            throw new RuntimeException("Rollback gagal: Saldo pada dompet '{$destination->name}' tidak mencukupi untuk proses pembalikan.");
        }

        $source->increment('balance', $amount);
        $destination->decrement('balance', $amount);
    }
}
