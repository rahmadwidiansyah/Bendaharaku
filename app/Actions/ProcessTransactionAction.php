<?php

namespace App\Actions;

use App\Enums\TransactionSource;
use App\Events\TransactionPosted;
use App\Jobs\CheckBudgetAlertsJob;
use App\Models\Category;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\UserAiMemoryContribution;
use App\Models\Wallet;
use App\Services\AI\Memory\UserMemoryService;
use App\Services\Category\CategoryResolutionService;
use App\Services\Loan\LoanBalanceService;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ProcessTransactionAction
{
    public function __construct(
        private readonly CategoryResolutionService $categoryResolution,
        private readonly LoanBalanceService $loanBalances,
        private readonly UserMemoryService $memoryService,
    ) {}

    /**
     * Membuat data transaksi baru, memutasi saldo dompet, dan mencatat log transaksi.
     * Concurrency safe dengan pessimistics locking (lockForUpdate).
     * Menerbitkan TransactionPosted setelah commit database berhasil.
     */
    public function create(
        array $data,
        int $userId,
        string $sourcePrefix = 'TRX',
        TransactionSource $source = TransactionSource::SYSTEM,
        array $aiKeywords = [],
    ): TransactionLog {
        if ($data['source_wallet_id'] === $data['destination_wallet_id']) {
            throw new InvalidArgumentException('Transaksi gagal: Dompet asal dan dompet tujuan tidak boleh sama.');
        }

        if ($data['amount'] <= 0) {
            throw new InvalidArgumentException('Transaksi gagal: Nominal transaksi harus lebih besar dari nol.');
        }

        $transactionLog = DB::transaction(function () use ($data, $userId, $sourcePrefix) {
            $user = User::findOrFail($userId);

            $type = strtolower($data['transaction_type'] ?? '');

            if ($type === 'transfer' || ($type !== 'debt' && $type !== 'receivable' && empty($data['category_id']))) {
                $category = $this->categoryResolution->resolveTransferCategory($userId);
            } elseif (in_array($type, ['debt', 'receivable'])) {
                $subType = $data['debt_sub_type'] ?? null;
                $category = $this->categoryResolution->resolveSystemCategory($userId, $type, $subType, $data['category_id'] ?? null);
            } else {
                $category = Category::where('user_id', $userId)->where('id', $data['category_id'])->firstOrFail();
            }

            $source = Wallet::where('user_id', $userId)->where('id', $data['source_wallet_id'])->lockForUpdate()->firstOrFail();
            $destination = Wallet::where('user_id', $userId)->where('id', $data['destination_wallet_id'])->lockForUpdate()->firstOrFail();

            $mainWallet = ($source->group_type !== 'System') ? $source : $destination;
            $balanceBefore = $mainWallet->balance;

            $isCleared = $data['is_cleared'] ?? true;
            if ($isCleared) {
                $this->applyTransaction($source, $destination, $data['amount'], $user->allow_negative_balance);
            }

            $balanceAfter = $isCleared ? Wallet::where('id', $mainWallet->id)->value('balance') : $balanceBefore;

            $subjectInput = isset($data['subject']) ? strtoupper(trim($data['subject'])) : '';
            $finalSubject = $subjectInput === '' ? '-' : $subjectInput;

            $transaction = TransactionLog::create([
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

            $transaction->setRelation('category', $category);
            $this->loanBalances->validate($transaction);
            $loanType = $this->loanType($category->system_key);
            if ($loanType) {
                $this->loanBalances->rebuild($userId, $finalSubject, $loanType);
            }

            return $transaction;
        });

        event(new TransactionPosted($transactionLog, $source, $aiKeywords));

        $this->dispatchBudgetOverCheckIfExpense($transactionLog);

        // Unified activity log (7-day retention via prune command)
        $sourceLabel = $source->value ?? 'SYSTEM';
        if ($sourceLabel === TransactionSource::WEB->value) {
            $sourceLabel = 'WEB';
        }
        ActivityLogger::log($userId, 'transaction', 'created', $transactionLog->subject !== '-' ? $transactionLog->subject : 'Transaksi ('.$sourceLabel.')', 'Transaksi Rp '.number_format((float) $transactionLog->amount, 0, ',', '.').' via '.$sourceLabel, [
            'source' => $sourceLabel,
            'transaction_id' => $transactionLog->id,
            'amount' => (float) $transactionLog->amount,
            'category_id' => $transactionLog->category_id,
            'ai_keywords_count' => count($aiKeywords),
        ]);

        return $transactionLog;
    }

    /**
     * Memperbarui data transaksi, mengembalikan efek saldo lama, dan menerapkan efek saldo baru.
     * Proteksi penuh dari race condition saat pemulihan saldo lama dan penerapan saldo baru.
     */
    public function update(TransactionLog $transaction, array $data, array $newAiKeywords = [], ?string $source = null): TransactionLog
    {
        $userId = $transaction->user_id;

        if ($data['source_wallet_id'] === $data['destination_wallet_id']) {
            throw new InvalidArgumentException('Update gagal: Dompet asal dan dompet tujuan tidak boleh sama.');
        }

        if ($data['amount'] <= 0) {
            throw new InvalidArgumentException('Update gagal: Nominal transaksi harus lebih besar dari nol.');
        }

        $result = DB::transaction(function () use ($transaction, $data, $userId) {
            $user = User::findOrFail($userId);

            // Tangkap pasangan subjek/tipe LAMA sebelum dimutasi: rebuildAll()
            // hanya menyentuh subjek yang masih ada di log, sehingga subjek
            // lama yang ditinggalkan (ganti subject/kategori) harus di-rebuild
            // eksplisit agar tidak menjadi baris yatim.
            $oldSubject = strtoupper(trim((string) $transaction->subject));
            $oldLoanType = $this->loanType($transaction->category()->value('system_key'));

            // Resolve kategori — sama dengan create()
            $type = strtolower($data['transaction_type'] ?? '');

            if ($type === 'transfer' || ($type !== 'debt' && $type !== 'receivable' && empty($data['category_id']))) {
                $newCategory = $this->categoryResolution->resolveTransferCategory($userId);
            } elseif (in_array($type, ['debt', 'receivable'])) {
                $subType = $data['debt_sub_type'] ?? null;
                $newCategory = $this->categoryResolution->resolveSystemCategory($userId, $type, $subType, $data['category_id'] ?? null);
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

            $this->loanBalances->rebuildAll($userId);

            // Hapus baris yatim bila subjek/tipe lama ditinggalkan update.
            // rebuild() tanpa sisa log menghapus barisnya; bila masih ada sisa
            // log (mis. transaksi lain dengan subjek sama) hasilnya identik.
            // Replay negatif (mis. LOAN dipindah sementara payment tertinggal)
            // melempar sehingga seluruh update rollback — sama seperti delete.
            $newSubject = strtoupper(trim((string) $transaction->subject));
            $newLoanType = $this->loanType($transaction->category()->value('system_key'));
            if ($oldSubject !== '' && $oldSubject !== '-' && $oldLoanType
                && ($oldSubject !== $newSubject || $oldLoanType !== $newLoanType)
            ) {
                $this->loanBalances->rebuild($userId, $oldSubject, $oldLoanType);
            }

            return $transaction;
        });

        $hasContributions = UserAiMemoryContribution::where('user_id', $userId)
            ->where('transaction_id', $transaction->id)
            ->where('is_active', true)
            ->exists();

        if ($hasContributions || ! empty($newAiKeywords)) {
            $this->memoryService->syncTransactionMemory($userId, $transaction->id, $newAiKeywords, $source);
        }

        return $result;
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

            // Samakan dengan create(): konfirmasi dapat mengaktifkan transaksi
            // hutang/piutang yang sebelumnya tidak dihitung (is_cleared=false
            // dikecualikan validate/rebuild). Urutan lock tetap wallet->loan,
            // sama seperti create/update/delete, agar tidak ada deadlock baru.
            // validate() melempar bila konfirmasi membuat saldo negatif
            // (mis. pembayaran melebihi sisa) sehingga seluruh tx rollback.
            $this->loanBalances->validate($transaction);
            $confirmLoanType = $this->loanType($transaction->category()->value('system_key'));
            if ($confirmLoanType) {
                $confirmSubject = strtoupper(trim((string) $transaction->subject));
                if ($confirmSubject !== '' && $confirmSubject !== '-') {
                    $this->loanBalances->rebuild($transaction->user_id, $confirmSubject, $confirmLoanType);
                }
            }

            $this->dispatchBudgetOverCheckIfExpense($transaction);

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
            // Tangkap pasangan subjek/tipe sebelum soft-delete: bila ini log
            // terakhir untuk subjek tersebut, rebuildAll() tidak akan
            // menyentuhnya sehingga baris ringkasan menjadi yatim.
            $oldSubject = strtoupper(trim((string) $transaction->subject));
            $oldLoanType = $this->loanType($transaction->category()->value('system_key'));
            if ($transaction->is_cleared) {
                // Kunci baris data wallet saat pemulihan penghapusan transaksi
                $source = Wallet::where('user_id', $transaction->user_id)->where('id', $transaction->source_wallet_id)->lockForUpdate()->first();
                $destination = Wallet::where('user_id', $transaction->user_id)->where('id', $transaction->destination_wallet_id)->lockForUpdate()->first();

                if ($source && $destination) {
                    $this->reverseTransaction($source, $destination, $transaction->amount, $allowNegativeBalance);
                }
            }

            // Revoke memory contributions dari transaksi ini
            $this->memoryService->revokeContributions($transaction->user_id, $transaction->id);

            $deleted = $transaction->delete();
            $this->loanBalances->rebuildAll($transaction->user_id);
            if ($oldSubject !== '' && $oldSubject !== '-' && $oldLoanType) {
                $this->loanBalances->rebuild($transaction->user_id, $oldSubject, $oldLoanType);
            }

            return $deleted;
        });
    }

    private function loanType(?string $systemKey): ?string
    {
        return match ($systemKey) {
            'LOAN', 'DEBT_PAYMENT' => 'debt',
            'RECEIVABLE', 'RECEIVABLE_PAYMENT' => 'receivable',
            default => null,
        };
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
     * Method Internal: Dispatch cek budget over hanya untuk transaksi tipe Expense.
     * Dipanggil setelah create/confirm berhasil.
     */
    private function dispatchBudgetOverCheckIfExpense(TransactionLog $transaction): void
    {
        if (strtolower($transaction->type?->name ?? '') !== 'expense') {
            return;
        }

        CheckBudgetAlertsJob::dispatch($transaction->user_id, now()->month, now()->year);
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
