<?php

namespace App\Http\Controllers;

use App\Actions\ProcessTransactionAction;
use App\Models\Category;
use App\Models\TransactionDraft;
use App\Models\TransactionLog;
use App\Services\Chat\DraftConfirmationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Enums\TransactionSource;
use Inertia\Response;

class TransactionController extends Controller
{
    /**
     * Menampilkan daftar transaksi.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $query = $user->transactionLogs()->with(['type', 'category', 'sourceWallet', 'destinationWallet']);
        $query->where('is_cleared', true);
        $query->whereBetween('date', [$startDate, $endDate]);

        if ($request->filled('type')) {
            $query->whereHas('type', fn ($q) => $q->where('name', $request->type));
        }

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(notes) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(subject) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('category', fn ($qCat) => $qCat->whereRaw('LOWER(category_name) LIKE ?', ["%{$search}%"]));
            });
        }

        $transactions = $query->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($trx) => [
                'id' => $trx->id,
                'amount' => (float) $trx->amount,
                'notes' => $trx->notes,
                'subject' => $trx->subject,
                'date' => Carbon::parse($trx->date)->translatedFormat('d M Y'),
                'raw_date' => $trx->date,
                'time' => Carbon::parse($trx->created_at)->format('H:i'),
                'type' => $trx->type,
                'category' => $trx->category,
                'source_wallet' => $trx->sourceWallet,
                'destination_wallet' => $trx->destinationWallet,
            ]);

        // ── Query Pending Drafts ──
        $pendingDraftsQuery = $user->transactionDrafts()->where('status', 'pending');
        $pendingDraftsQuery->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ]);

        if ($request->filled('type')) {
            $type = strtolower($request->type);
            $pendingDraftsQuery->whereRaw('LOWER(payload->>\'type_key\') = ?', [$type]);
        }

        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $pendingDraftsQuery->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(original_text) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(payload->>\'notes\') LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(payload->>\'subject\') LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(payload->>\'category_name\') LIKE ?', ["%{$search}%"]);
            });
        }

        $drafts = $pendingDraftsQuery->orderBy('created_at', 'desc')->get();

        $draftData = $drafts->map(function ($draft) {
            $payload = $draft->payload ?? [];

            return [
                'id' => $draft->id,
                'is_draft' => true,
                'draft_id' => $draft->id,
                'amount' => (float) ($payload['amount'] ?? 0),
                'notes' => $payload['notes'] ?? $draft->original_text,
                'subject' => $payload['subject'] ?? '-',
                'is_cleared' => false,
                'reference_number' => null,
                'date' => Carbon::parse($payload['date'] ?? $draft->created_at)->translatedFormat('d M Y'),
                'raw_date' => $payload['date'] ?? $draft->created_at->toDateString(),
                'time' => Carbon::parse($draft->created_at)->format('H:i'),
                'type' => [
                    'id' => null,
                    'name' => ucfirst($payload['type_key'] ?? 'expense'),
                ],
                'category' => isset($payload['category_name']) ? [
                    'id' => $payload['category_id'] ?? null,
                    'category_name' => $payload['category_name'],
                ] : null,
                'source_wallet' => isset($payload['source_wallet_name']) ? [
                    'id' => $payload['source_wallet_id'] ?? null,
                    'name' => $payload['source_wallet_name'],
                ] : null,
                'destination_wallet' => isset($payload['destination_wallet_name']) ? [
                    'id' => $payload['destination_wallet_id'] ?? null,
                    'name' => $payload['destination_wallet_name'],
                ] : null,
            ];
        });

        $transactions = $transactions->concat($draftData)->sort(function ($a, $b) {
            $dateCompare = strcmp($b['raw_date'], $a['raw_date']);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }
            $timeCompare = strcmp($b['time'], $a['time']);
            if ($timeCompare !== 0) {
                return $timeCompare;
            }

            return $b['id'] <=> $a['id'];
        })->values();

        return Inertia::render('Transactions/Index', [
            'transactions' => ['data' => $transactions],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    /**
     * Menampilkan form pembuatan transaksi.
     */
    public function create(): Response
    {
        return Inertia::render('Transactions/Create', $this->getFormData());
    }

    /**
     * Menyimpan transaksi baru ke database.
     */
    public function store(Request $request, ProcessTransactionAction $action)
    {
        $validated = $this->validateTransaction($request);

        try {
            $action->create(
                data: $validated,
                userId: Auth::id(),
                source: TransactionSource::WEB,
            );

            return redirect()->route('dashboard')->with('success', 'Transaksi Berhasil!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Menampilkan form edit transaksi / draft.
     */
    public function edit(Request $request, $id): Response
    {
        $user = Auth::user();
        $isDraft = $request->boolean('is_draft') || $request->input('is_draft') === 'true';

        // 1. Coba cari di transaction_drafts terlebih dahulu
        $draft = null;
        if ($isDraft) {
            $draft = TransactionDraft::where('user_id', $user->id)
                ->where('status', 'pending')
                ->find($id);
        }

        if (! $draft && ! $request->has('is_draft')) {
            $draft = TransactionDraft::where('user_id', $user->id)
                ->where('status', 'pending')
                ->find($id);
        }

        if ($draft) {
            $payload = $draft->payload ?? [];

            $catId = $payload['category_id'] ?? null;
            $category = $catId ? Category::find($catId) : null;
            $systemKey = $category ? $category->system_key : null;

            $debtSubType = null;
            $typeKey = strtolower($payload['type_key'] ?? 'expense');
            if (in_array($typeKey, ['debt', 'receivable'])) {
                if ($systemKey === 'DEBT_PAYMENT' || $systemKey === 'RECEIVABLE') {
                    $debtSubType = 'expense';
                } else {
                    $debtSubType = 'income';
                }
            }

            // Map payload agar sesuai dengan format yang diharapkan Edit.vue
            $mappedTransaction = [
                'id' => $draft->id,
                'is_draft' => true,
                'amount' => (float) ($payload['amount'] ?? 0),
                'notes' => $payload['notes'] ?? $draft->original_text,
                'subject' => $payload['subject'] ?? '-',
                'date' => $payload['date'] ?? $draft->created_at->toDateString(),
                'category_id' => $payload['category_id'] ?? null,
                'source_wallet_id' => $payload['source_wallet_id'] ?? null,
                'destination_wallet_id' => $payload['destination_wallet_id'] ?? null,
                'due_date' => $payload['due_date'] ?? null,
                'due_date_type' => $payload['due_date_type'] ?? null,
                'due_date_interval' => $payload['due_date_interval'] ?? null,
                'transaction_type' => $typeKey,
                'debt_sub_type' => $debtSubType,
            ];

            return Inertia::render('Transactions/Edit', array_merge(
                ['transaction' => $mappedTransaction],
                $this->getFormData()
            ));
        }

        // 2. Kalau tidak ada di drafts, cari di transaction_logs
        $transaction = $user->transactionLogs()->findOrFail($id);
        $this->authorizeOwnership($transaction);

        return Inertia::render('Transactions/Edit', array_merge(
            ['transaction' => $transaction],
            $this->getFormData()
        ));
    }

    /**
     * Memperbarui transaksi yang sudah ada / memproses draft menjadi transaksi final.
     */
    public function update(Request $request, $id, ProcessTransactionAction $action, DraftConfirmationService $draftService)
    {
        $user = Auth::user();
        $isDraft = $request->boolean('is_draft') || $request->input('is_draft') === 'true';

        // 1. Coba cari di transaction_drafts terlebih dahulu
        $draft = null;
        if ($isDraft) {
            $draft = TransactionDraft::where('user_id', $user->id)
                ->where('status', 'pending')
                ->find($id);
        }

        if (! $draft && ! $request->has('is_draft')) {
            $draft = TransactionDraft::where('user_id', $user->id)
                ->where('status', 'pending')
                ->find($id);
        }

        if ($draft) {
            $validated = $this->validateTransaction($request);

            try {
                $transactionLog = DB::transaction(function () use ($draft, $user, $validated, $action) {
                    // a. Insert ke transactions (buat TransactionLog via ProcessTransactionAction)
                    // b. Update saldo wallet (otomatis dilakukan di ProcessTransactionAction::create)
                    $log = $action->create(
                        data: [
                            'date' => $validated['date'],
                            'category_id' => $validated['category_id'],
                            'source_wallet_id' => $validated['source_wallet_id'],
                            'destination_wallet_id' => $validated['destination_wallet_id'],
                            'amount' => $validated['amount'],
                            'subject' => $validated['subject'] ?? $user->name,
                            'notes' => $validated['notes'] ?? null,
                            'transaction_type' => $validated['transaction_type'] ?? null,
                            'debt_sub_type' => $validated['debt_sub_type'] ?? null,
                            'due_date' => $validated['due_date'] ?? null,
                            'due_date_type' => $validated['due_date_type'] ?? null,
                            'due_date_interval' => $validated['due_date_interval'] ?? null,
                            'is_cleared' => true,
                        ],
                        userId: $user->id,
                        sourcePrefix: 'WEB',
                        source: TransactionSource::WEB,
                    );

                    // c. Hapus/Tandai Draft selesai (confirmed)
                    $draft->update([
                        'status' => 'confirmed',
                        'confirmed_transaction_ids' => [$log->id],
                    ]);

                    return $log;
                });

                // Sinkronkan riwayat chat
                $draftService->syncChatHistoryAfterConfirm($user->id, $draft->id, $transactionLog);

                return redirect()->route('dashboard')->with('success', 'Draft berhasil disimpan dan dikonfirmasi!');
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        // 2. Kalau tidak ada di drafts, cari di transaction_logs
        $transaction = $user->transactionLogs()->findOrFail($id);
        $this->authorizeOwnership($transaction);
        $validated = $this->validateTransaction($request);

        try {
            $action->update($transaction, $validated);

            return redirect()->route('dashboard')->with('success', 'Transaksi diupdate!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mengkonfirmasi transaksi Draft menjadi transaksi terkonfirmasi.
     * Memutasi saldo dompet yang sebelumnya ditahan.
     */
    public function confirm(Request $request, $id, ProcessTransactionAction $action, DraftConfirmationService $draftService)
    {
        $user = Auth::user();
        $isDraft = $request->boolean('is_draft') || $request->input('is_draft') === 'true';

        // 1. Coba cari di transaction_drafts terlebih dahulu
        $draft = null;
        if ($isDraft) {
            $draft = TransactionDraft::where('user_id', $user->id)
                ->where('status', 'pending')
                ->find($id);
        }

        if (! $draft && ! $request->has('is_draft')) {
            $draft = TransactionDraft::where('user_id', $user->id)
                ->where('status', 'pending')
                ->find($id);
        }

        if ($draft) {
            try {
                $draftService->confirm($draft, $user);

                return back()->with('success', 'Draft berhasil dikonfirmasi!');
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        // 2. Kalau tidak ada di drafts, cari di transaction_logs (backward compat)
        $transaction = $user->transactionLogs()->findOrFail($id);

        if ($transaction->is_cleared) {
            return back()->with('error', 'Transaksi ini sudah terkonfirmasi.');
        }

        try {
            $action->confirm($transaction);

            return back()->with('success', 'Transaksi berhasil dikonfirmasi!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Menghapus transaksi / membatalkan draft.
     */
    public function destroy(Request $request, $id, ProcessTransactionAction $action, DraftConfirmationService $draftService)
    {
        $user = Auth::user();
        $isDraft = $request->boolean('is_draft') || $request->input('is_draft') === 'true';

        // 1. Coba cari di transaction_drafts terlebih dahulu
        $draft = null;
        if ($isDraft) {
            $draft = TransactionDraft::where('user_id', $user->id)->find($id);
        }

        if (! $draft && ! $request->has('is_draft')) {
            $draft = TransactionDraft::where('user_id', $user->id)->find($id);
        }

        if ($draft) {
            try {
                DB::transaction(function () use ($draft, $user, $draftService) {
                    // a. Panggil logic Batal di Chat (DraftConfirmationService::cancel) untuk sinkronisasi riwayat chat
                    $draftService->cancel($draft, $user);
                    // b. Hapus draft dari tabel transaction_drafts
                    $draft->delete();
                });

                return redirect()->route('dashboard')->with('success', 'Draft berhasil dibatalkan dan dihapus!');
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        // 2. Kalau tidak ada di drafts, cari di transaction_logs
        $transaction = $user->transactionLogs()->findOrFail($id);
        $this->authorizeOwnership($transaction);

        try {
            $action->delete($transaction);

            return redirect()->route('dashboard')->with('success', 'Transaksi dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ==========================================
    // HELPER METHODS (INTERNAL CONTROLLER LOGIC)
    // ==========================================

    /**
     * Validasi kepemilikan transaksi untuk mencegah cross-user modification.
     */
    private function authorizeOwnership(TransactionLog $transaction): void
    {
        abort_if($transaction->user_id !== Auth::id(), 403, 'Anda tidak memiliki akses ke transaksi ini.');
    }

    /**
     * Standarisasi validasi form transaksi.
     *
     * Transfer, Debt, dan Receivable TIDAK membutuhkan category_id dari form
     * karena sistem akan auto-resolve kategori sistem yang sesuai berdasarkan
     * transaction_type di ProcessTransactionAction.
     *
     * Kategori Income/Expense tetap dipilih manual oleh user.
     */
    private function validateTransaction(Request $request): array
    {
        $type = strtolower($request->input('transaction_type', ''));
        $isSystemManagedCategory = in_array($type, ['transfer', 'debt', 'receivable']);

        return $request->validate([
            'date' => 'required|date',
            'category_id' => $isSystemManagedCategory ? 'nullable' : 'required|exists:categories,id',
            'source_wallet_id' => 'required|exists:wallets,id',
            'destination_wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|gt:0',
            'transaction_type' => 'nullable|string',
            'subject' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'due_date' => 'nullable|date',
            'due_date_type' => 'nullable|in:fixed,monthly,daily',
            'due_date_interval' => 'nullable|integer',
            // debt_sub_type memberi tahu backend apakah ini "loan" atau "debt_payment"
            // (dan "receivable" atau "receivable_payment")
            'debt_sub_type' => 'nullable|string',
        ]);
    }

    /**
     * Menarik seluruh data master dan kalkulasi yang dibutuhkan oleh form Create & Edit.
     */
    private function getFormData(): array
    {
        $user = Auth::user();

        // 1. Ambil data dompet
        $wallets = $user->wallets()->where('group_type', '!=', 'System')->get();
        $systemWallets = $user->wallets()->where('group_type', 'System')->get();

        // 2. Ambil kategori dan urutkan berdasarkan frekuensi pemakaian
        $categoryCounts = $user->transactionLogs()
            ->selectRaw('category_id, count(*) as count')
            ->groupBy('category_id')
            ->pluck('count', 'category_id');

        $categories = $user->categories()->with('type')->get()
            ->sortByDesc(fn ($cat) => $categoryCounts->get($cat->id, 0))
            ->values();

        // 3. Kalkulasi data subjek Hutang/Piutang aktif menggunakan system_key
        // Diproses KRONOLOGIS per subject (bukan SUM biasa) agar "since" reset
        // saat saldo mencapai 0 — overpay tidak mengurangi siklus baru.
        $transactions = $user->transactionLogs()->with('category')
            ->where('is_cleared', true)
            ->whereHas('category', fn ($q) => $q->whereIn('system_key', [
                'LOAN', 'DEBT_PAYMENT', 'RECEIVABLE', 'RECEIVABLE_PAYMENT',
            ]))
            ->whereNotNull('subject')
            ->where('subject', '!=', '-')
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        $debtLedger = [];
        $receivableLedger = [];

        foreach ($transactions as $tx) {
            $subject = strtoupper(trim($tx->subject));
            $amount = (float) $tx->amount;

            switch ($tx->category->system_key) {
                case 'LOAN':
                    if (! isset($debtLedger[$subject]) || $debtLedger[$subject]['balance'] <= 0) {
                        $debtLedger[$subject] = ['name' => $subject, 'balance' => 0.0];
                    }
                    $debtLedger[$subject]['balance'] += $amount;
                    break;
                case 'DEBT_PAYMENT':
                    if (! isset($debtLedger[$subject])) {
                        $debtLedger[$subject] = ['name' => $subject, 'balance' => 0.0];
                    }
                    $debtLedger[$subject]['balance'] -= $amount;
                    if ($debtLedger[$subject]['balance'] < 0) {
                        $debtLedger[$subject]['balance'] = 0.0;
                    }
                    break;
                case 'RECEIVABLE':
                    if (! isset($receivableLedger[$subject]) || $receivableLedger[$subject]['balance'] <= 0) {
                        $receivableLedger[$subject] = ['name' => $subject, 'balance' => 0.0];
                    }
                    $receivableLedger[$subject]['balance'] += $amount;
                    break;
                case 'RECEIVABLE_PAYMENT':
                    if (! isset($receivableLedger[$subject])) {
                        $receivableLedger[$subject] = ['name' => $subject, 'balance' => 0.0];
                    }
                    $receivableLedger[$subject]['balance'] -= $amount;
                    if ($receivableLedger[$subject]['balance'] < 0) {
                        $receivableLedger[$subject]['balance'] = 0.0;
                    }
                    break;
            }
        }

        return [
            'wallets' => $wallets,
            'systemWallets' => $systemWallets,
            'categories' => $categories,
            'debtSubjects' => collect($debtLedger)->filter(fn ($i) => $i['balance'] > 0)->values()->all(),
            'receivableSubjects' => collect($receivableLedger)->filter(fn ($i) => $i['balance'] > 0)->values()->all(),
        ];
    }
}
