<?php

namespace App\Http\Controllers;

use App\Actions\ProcessTransactionAction;
use App\Enums\TransactionSource;
use App\Models\Category;
use App\Models\TransactionDraft;
use App\Models\TransactionLog;
use App\Models\Wallet;
use App\Services\AI\Memory\AiKeywordTargetRemapper;
use App\Services\Chat\DraftConfirmationService;
use App\Services\Transaction\TransactionListService;
use App\Support\ActivityLogger;
use App\Traits\CalculatesDebtAndReceivable;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class TransactionController extends Controller implements HasMiddleware
{
    use CalculatesDebtAndReceivable;

    public static function middleware(): array
    {
        // Batas tulis (<100 user): longgar untuk pemakaian normal, menahan
        // spam/double-submit dan brute force. Baca (index/show/edit) bebas.
        return [new Middleware('throttle:60,1', only: ['store', 'update', 'confirm', 'destroy'])];
    }

    /**
     * Menampilkan daftar transaksi.
     */
    public function index(Request $request, TransactionListService $lists): Response
    {
        $user = Auth::user();
        $filters = $request->only(['start_date', 'end_date', 'type', 'search']);
        $limit = $this->pageLimit($request);

        $page = $lists->paginate($user, $filters, null, $limit);
        $agg = $lists->aggregates($user, $filters);
        $norm = $this->normalizedRange($request);

        return Inertia::render('Transactions/Index', [
            'transactions' => [
                'data' => $page['data'],
                'next_cursor' => $page['next_cursor'],
                'has_more' => $page['has_more'],
            ],
            'dailyTotals' => $agg['dailyTotals'],
            'calendarMarkers' => $agg['markers'],
            'startDate' => $norm['start'],
            'endDate' => $norm['end'],
            'filters' => $request->only(['search', 'type']),
            'limit' => $limit,
        ]);
    }

    /**
     * Halaman JSON berikutnya untuk "Muat lebih banyak" (dipakai
     * Transactions/Index dan Dashboard). Filter diambil dari query saat ini;
     * cursor lama tidak dipakai ulang saat filter berubah (client me-reset).
     */
    public function page(Request $request, TransactionListService $lists): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'type' => ['nullable', 'string', 'max:30'],
            'search' => ['nullable', 'string', 'max:100'],
            'cursor' => ['nullable', 'string', 'max:500'],
            // Sengaja tanpa max: nilai berlebih di-clamp ke MAX_LIMIT di bawah.
            'limit' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $page = $lists->paginate(
                $request->user(),
                $validated,
                $validated['cursor'] ?? null,
                (int) ($validated['limit'] ?? TransactionListService::DEFAULT_LIMIT),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $agg = $lists->aggregates($request->user(), $validated);

        return response()->json($page + [
            'dailyTotals' => $agg['dailyTotals'],
            'calendarMarkers' => $agg['markers'],
        ]);
    }

    private function pageLimit(Request $request): int
    {
        $limit = (int) $request->input('limit', TransactionListService::DEFAULT_LIMIT);

        return max(1, min(TransactionListService::MAX_LIMIT, $limit));
    }

    private function normalizedRange(Request $request): array
    {
        $now = Carbon::now();
        $start = (string) $request->input('start_date', $now->copy()->startOfMonth()->toDateString());
        $end = (string) $request->input('end_date', $now->copy()->endOfMonth()->toDateString());
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
            $start = $now->copy()->startOfMonth()->toDateString();
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
            $end = $now->copy()->endOfMonth()->toDateString();
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Menampilkan form pembuatan transaksi.
     */
    public function create(): Response
    {
        return Inertia::render($this->formView('Create'), $this->getFormData());
    }

    /**
     * Menyimpan transaksi baru ke database.
     */
    public function store(Request $request, ProcessTransactionAction $action)
    {
        $validated = $this->validateTransaction($request);

        // Idempotency-Key opsional (satu UUID per klik submit dari frontend).
        // Kunci sama → kembalikan hasil pertama tanpa tulis ulang; kunci beda
        // (walau nominal identik) → transaksi sah yang terpisah. Tanpa header
        // perilakunya persis seperti sebelumnya. Gagal validasi di atas tidak
        // menghabiskan kunci; kegagalan create juga tidak (boleh retry).
        $idemKey = $this->idempotencyKey($request);
        if ($idemKey !== null) {
            if ($hit = Cache::get($idemKey['result'])) {
                return redirect()->route('dashboard')->with('success', 'Transaksi Berhasil!');
            }
            if (! Cache::add($idemKey['claim'], true, 300)) {
                return back()->with('error', 'Permintaan sedang diproses, coba lagi beberapa detik.')->withHeaders(['Retry-After' => 5]);
            }
        }

        try {
            $log = $action->create(
                data: $validated,
                userId: Auth::id(),
                source: TransactionSource::WEB,
            );

            if ($idemKey !== null) {
                Cache::put($idemKey['result'], ['transaction_id' => $log->id], 86400);
                Cache::forget($idemKey['claim']);
            }

            return redirect()->route('dashboard')->with('success', 'Transaksi Berhasil!');
        } catch (\Exception $e) {
            if ($idemKey !== null) {
                Cache::forget($idemKey['claim']);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Normalisasi Idempotency-Key: string 1–128 char apa pun diterima dan
     * di-hash agar aman sebagai cache key; kosong/tak ada = nonaktif.
     *
     * @return ?array{claim: string, result: string}
     */
    private function idempotencyKey(Request $request): ?array
    {
        $raw = $request->header('Idempotency-Key');
        if (! is_string($raw) || trim($raw) === '' || strlen($raw) > 128) {
            return null;
        }
        $hash = hash('sha256', Auth::id().':'.trim($raw));

        return ['claim' => "idempotency:txn:{$hash}:claim", 'result' => "idempotency:txn:{$hash}:result"];
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
            $category = $catId ? Category::where('user_id', $user->id)->find($catId) : null;
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
                'date' => ! empty($payload['date']) ? substr((string) $payload['date'], 0, 10) : $draft->created_at->toDateString(),
                'category_id' => $payload['category_id'] ?? null,
                'source_wallet_id' => $payload['source_wallet_id'] ?? null,
                'destination_wallet_id' => $payload['destination_wallet_id'] ?? null,
                'due_date' => $payload['due_date'] ?? null,
                'due_date_type' => $payload['due_date_type'] ?? null,
                'due_date_interval' => $payload['due_date_interval'] ?? null,
                'transaction_type' => $typeKey,
                'debt_sub_type' => $debtSubType,
            ];

            return Inertia::render($this->formView('Edit'), array_merge(
                ['transaction' => $mappedTransaction],
                $this->getFormData()
            ));
        }

        // 2. Kalau tidak ada di drafts, cari di transaction_logs
        $transaction = $user->transactionLogs()->findOrFail($id);
        $this->authorizeOwnership($transaction);

        $mappedTransaction = [
            'id' => $transaction->id,
            'amount' => (float) $transaction->amount,
            'notes' => $transaction->notes,
            'subject' => $transaction->subject,
            'date' => $transaction->date?->format('Y-m-d'),
            'category_id' => $transaction->category_id,
            'source_wallet_id' => $transaction->source_wallet_id,
            'destination_wallet_id' => $transaction->destination_wallet_id,
            'due_date' => $transaction->due_date?->format('Y-m-d'),
            'due_date_type' => $transaction->due_date_type,
            'due_date_interval' => $transaction->due_date_interval,
            'transaction_type' => $transaction->transaction_type,
            'debt_sub_type' => $transaction->debt_sub_type,
            'created_at' => $transaction->created_at?->toISOString(),
            'updated_at' => $transaction->updated_at?->toISOString(),
        ];

        return Inertia::render($this->formView('Edit'), array_merge(
            ['transaction' => $mappedTransaction],
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
                // Keyword LLM diarahkan ke target FINAL pilihan user (koreksi user = ground truth).
                $aiKeywords = $this->remapDraftKeywords($draft, $validated, $user->id);

                $transactionLog = DB::transaction(function () use ($draft, $user, $validated, $action, $aiKeywords) {
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
                        source: TransactionSource::DRAFT,
                        aiKeywords: $aiKeywords,
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

                ActivityLogger::log($user->id, 'transaction', 'created', $transactionLog->subject !== '-' ? $transactionLog->subject : 'Transaksi dari Draft', 'Draft #'.$draft->id.' dikonfirmasi menjadi transaksi #'.$transactionLog->id, [
                    'source' => 'DRAFT',
                    'draft_id' => $draft->id,
                    'transaction_id' => $transactionLog->id,
                    'amount' => (float) $transactionLog->amount,
                ]);

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
            $updated = $action->update($transaction, $validated, [], 'web');

            ActivityLogger::log(Auth::id(), 'transaction', 'updated', $updated->subject !== '-' ? $updated->subject : 'Transaksi diupdate', 'Transaksi #'.$updated->id.' diupdate via web', [
                'source' => 'WEB',
                'transaction_id' => $updated->id,
                'amount' => (float) $updated->amount,
            ]);

            return redirect()->route('dashboard')->with('success', 'Transaksi diupdate!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Petakan keyword LLM dari payload draft ke kategori/wallet final yang
     * divalidasi dari form konfirmasi. Entry yang targetnya tidak bisa
     * dipetakan akan dibuang oleh remapper.
     *
     * @param  array<string, mixed>  $validated
     * @return list<array{keyword:string,target_type:string,target_id:int,target_name:?string}>
     */
    private function remapDraftKeywords(TransactionDraft $draft, array $validated, int $userId): array
    {
        $payload = $draft->payload ?? [];

        $categoryId = isset($validated['category_id']) ? (int) $validated['category_id'] : null;
        $sourceWalletId = isset($validated['source_wallet_id']) ? (int) $validated['source_wallet_id'] : null;
        $destinationWalletId = isset($validated['destination_wallet_id']) ? (int) $validated['destination_wallet_id'] : null;

        $walletNames = Wallet::where('user_id', $userId)
            ->whereIn('id', array_filter([$sourceWalletId, $destinationWalletId]))
            ->pluck('name', 'id');

        $categoryName = $categoryId !== null
            ? Category::where('user_id', $userId)->where('id', $categoryId)->value('category_name')
            : null;

        return app(AiKeywordTargetRemapper::class)->remapForDraft(
            keywords: $payload['aiKeywords'] ?? $payload['ai_keywords'] ?? [],
            payload: $payload,
            finalCategoryId: $categoryId,
            finalCategoryName: $categoryName,
            finalSourceWalletId: $sourceWalletId,
            finalSourceWalletName: $sourceWalletId !== null ? $walletNames[$sourceWalletId] ?? null : null,
            finalDestinationWalletId: $destinationWalletId,
            finalDestinationWalletName: $destinationWalletId !== null ? $walletNames[$destinationWalletId] ?? null : null,
        );
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
            $confirmed = $action->confirm($transaction);

            ActivityLogger::log(Auth::id(), 'transaction', 'confirmed', $confirmed->subject !== '-' ? $confirmed->subject : 'Transaksi dikonfirmasi', 'Transaksi #'.$confirmed->id.' dikonfirmasi', [
                'transaction_id' => $confirmed->id,
                'amount' => (float) $confirmed->amount,
            ]);

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
            $tid = $transaction->id;
            $subject = $transaction->subject;
            $action->delete($transaction);

            ActivityLogger::log(Auth::id(), 'transaction', 'deleted', $subject !== '-' ? $subject : 'Transaksi dihapus', 'Transaksi #'.$tid.' dihapus via web', [
                'transaction_id' => $tid,
            ]);

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

        // exists: dibatasi pemilik agar ID milik pengguna lain ditolak pada
        // tahap validasi (422 konsisten, tanpa membocorkan keberadaan data).
        $userId = Auth::id();

        return $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'category_id' => [
                $isSystemManagedCategory ? 'nullable' : 'required',
                Rule::exists('categories', 'id')->where('user_id', $userId),
            ],
            'source_wallet_id' => ['required', Rule::exists('wallets', 'id')->where('user_id', $userId)],
            'destination_wallet_id' => ['required', Rule::exists('wallets', 'id')->where('user_id', $userId)],
            'amount' => 'required|numeric|gt:0',
            'transaction_type' => 'nullable|string',
            'subject' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'due_date' => 'nullable|date_format:Y-m-d',
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
    /**
     * Multi-UI: halaman form transaksi memakai varian Material saat
     * `ui_theme === 'm3'`. Kontrak props/route tidak berubah.
     */
    private function formView(string $page): string
    {
        return Auth::user()->ui_theme === 'm3'
            ? "Material/Transactions/{$page}"
            : "Transactions/{$page}";
    }

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

        // 3. Kalkulasi data subjek Hutang/Piutang aktif — pakai trait terpusat
        $balances = $this->calculateAllBalances();

        return [
            'wallets' => $wallets,
            'systemWallets' => $systemWallets,
            'categories' => $categories,
            'debtSubjects' => collect($balances['debts'])->map(fn ($d) => [
                'name' => $d['subject'],
                'balance' => $d['balance'],
            ])->values()->all(),
            'receivableSubjects' => collect($balances['receivables'])->map(fn ($r) => [
                'name' => $r['subject'],
                'balance' => $r['balance'],
            ])->values()->all(),
        ];
    }
}
