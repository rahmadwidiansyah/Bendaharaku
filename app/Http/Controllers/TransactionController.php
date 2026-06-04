<?php

namespace App\Http\Controllers;

use App\Models\TransactionLog;
use App\Actions\ProcessTransactionAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

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
        $query->whereBetween('date', [$startDate, $endDate]);

        if ($request->filled('type')) {
            $query->whereHas('type', fn($q) => $q->where('name', $request->type));
        }

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->whereRaw('LOWER(notes) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(subject) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('category', fn($qCat) => $qCat->whereRaw('LOWER(category_name) LIKE ?', ["%{$search}%"]));
            });
        }

        $transactions = $query->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($trx) => [
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
            $action->create($validated, Auth::id());
            return redirect()->route('dashboard')->with('success', 'Transaksi Berhasil!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Menampilkan form edit transaksi.
     */
    public function edit(TransactionLog $transaction): Response
    {
        $this->authorizeOwnership($transaction);

        return Inertia::render('Transactions/Edit', array_merge(
            ['transaction' => $transaction],
            $this->getFormData()
        ));
    }

    /**
     * Memperbarui transaksi yang sudah ada.
     */
    public function update(Request $request, TransactionLog $transaction, ProcessTransactionAction $action)
    {
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
     * Menghapus transaksi.
     */
    public function destroy(TransactionLog $transaction, ProcessTransactionAction $action)
    {
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
     */
    private function validateTransaction(Request $request): array
    {
        return $request->validate([
            'date' => 'required|date',
            'category_id' => 'required|exists:categories,id',
            'source_wallet_id' => 'required|exists:wallets,id',
            'destination_wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|gt:0', // Lebih baik dari min:0 (mencegah nominal 0)
            'subject' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'due_date' => 'nullable|date',
            'due_date_type' => 'nullable|in:fixed,monthly,daily',
            'due_date_interval' => 'nullable|integer',
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
            ->sortByDesc(fn($cat) => $categoryCounts->get($cat->id, 0))
            ->values();

        // 3. Kalkulasi data subjek Hutang/Piutang aktif menggunakan PHP 8.4 match expression
        $transactions = $user->transactionLogs()->with('category')
            ->whereHas('category', fn($q) => $q->whereIn('category_name', [
                'Dapat Hutangan', 'Bayar Cicilan Hutang', 'Ngasih Piutang', 'Terima Bayar Piutang'
            ]))
            ->whereNotNull('subject')
            ->where('subject', '!=', '-')
            ->get();

        $debtBalances = [];
        $receivableBalances = [];

        foreach ($transactions as $tx) {
            $subject = trim($tx->subject);
            $subjectKey = strtolower($subject);
            $catName = $tx->category->category_name;

            $debtBalances[$subjectKey] ??= ['name' => $subject, 'balance' => 0];
            $receivableBalances[$subjectKey] ??= ['name' => $subject, 'balance' => 0];

            match ($catName) {
                'Dapat Hutangan' => $debtBalances[$subjectKey]['balance'] += $tx->amount,
                'Bayar Cicilan Hutang' => $debtBalances[$subjectKey]['balance'] -= $tx->amount,
                'Ngasih Piutang' => $receivableBalances[$subjectKey]['balance'] += $tx->amount,
                'Terima Bayar Piutang' => $receivableBalances[$subjectKey]['balance'] -= $tx->amount,
                default => null,
            };
        }

        return [
            'wallets' => $wallets,
            'systemWallets' => $systemWallets,
            'categories' => $categories,
            'debtSubjects' => collect($debtBalances)->filter(fn($i) => $i['balance'] > 0)->pluck('name')->values()->all(),
            'receivableSubjects' => collect($receivableBalances)->filter(fn($i) => $i['balance'] > 0)->pluck('name')->values()->all(),
        ];
    }
}