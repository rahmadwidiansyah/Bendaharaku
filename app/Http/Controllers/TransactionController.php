<?php

namespace App\Http\Controllers;

use App\Models\TransactionLog;
use App\Models\Wallet;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();

        // 1. Setup Default Tanggal (Bulan Ini)
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // 2. Mulai Query dasar
        $query = $user->transactionLogs()->with(['type', 'category', 'sourceWallet', 'destinationWallet']);

        // 3. Filter berdasarkan Tanggal
        $query->whereBetween('date', [$startDate, $endDate]);

        // 4. Filter berdasarkan Tipe
        if ($request->has('type') && $request->type != '') {
            $query->whereHas('type', function($q) use ($request) {
                $q->where('name', $request->type);
            });
        }

        // 5. Filter berdasarkan Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->whereRaw('LOWER(notes) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(subject) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('category', function($qCat) use ($search) {
                      $qCat->whereRaw('LOWER(category_name) LIKE ?', ["%{$search}%"]);
                  });
            });
        }

        $transactions = $query->orderBy('date', 'desc')
                              ->orderBy('created_at', 'desc')
                              ->get()
                              ->map(function ($trx) {
                                  return [
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
                                  ];
                              });

        return Inertia::render('Transactions/Index', [
            'transactions' => [
                'data' => $transactions
            ],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    public function create(): Response
    {
        $wallets = Auth::user()->wallets()->where('group_type', '!=', 'System')->get();
        $systemWallets = Auth::user()->wallets()->where('group_type', 'System')->get();
        $categories = Auth::user()->categories()->with('type')->get();
        
        $categoryCounts = Auth::user()->transactionLogs()
            ->selectRaw('category_id, count(*) as count')
            ->groupBy('category_id')
            ->pluck('count', 'category_id');

        $categories = $categories->sortByDesc(function($cat) use ($categoryCounts) {
            return $categoryCounts->get($cat->id, 0);
        })->values();

        return Inertia::render('Transactions/Create', [
            'wallets' => $wallets,
            'categories' => $categories,
            'systemWallets' => $systemWallets,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'category_id' => 'required|exists:categories,id',
            'source_wallet_id' => 'required|exists:wallets,id',
            'destination_wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:0',
            'subject' => 'nullable|string|max:255',
        ]);

        $category = Category::findOrFail($request->category_id);

        try {
            DB::transaction(function () use ($request, $category) {
                $source = Wallet::findOrFail($request->source_wallet_id);
                $destination = Wallet::findOrFail($request->destination_wallet_id);

                $mainWallet = ($source->group_type !== 'System') ? $source : $destination;
                $balanceBefore = $mainWallet->balance;

                Wallet::where('id', $source->id)->decrement('balance', $request->amount);
                Wallet::where('id', $destination->id)->increment('balance', $request->amount);

                $balanceAfter = Wallet::where('id', $mainWallet->id)->value('balance');

                TransactionLog::create([
                    'reference_number' => 'TRX-' . strtoupper(Str::random(10)),
                    'user_id' => Auth::id(),
                    'date' => $request->date,
                    'type_id' => $category->type_id,
                    'category_id' => $category->id,
                    'source_wallet_id' => $source->id,
                    'destination_wallet_id' => $destination->id,
                    'amount' => $request->amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'subject' => $request->subject ?? '-',
                    'notes' => $request->notes,
                    'is_cleared' => true,
                ]);
            });

            return redirect()->route('transactions.index')->with('success', 'Transaksi Berhasil!');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(TransactionLog $transaction)
    {
        try {
            DB::transaction(function () use ($transaction) {
                if ($transaction->is_cleared) {
                    Wallet::where('id', $transaction->source_wallet_id)->increment('balance', $transaction->amount);
                    Wallet::where('id', $transaction->destination_wallet_id)->decrement('balance', $transaction->amount);
                }
                $transaction->delete();
            });

            return redirect()->route('transactions.index')->with('success', 'Transaksi dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function edit(TransactionLog $transaction): Response
    {
        $wallets = Auth::user()->wallets()->where('group_type', '!=', 'System')->get();
        $systemWallets = Auth::user()->wallets()->where('group_type', 'System')->get();
        $categories = Auth::user()->categories()->with('type')->get();

        return Inertia::render('Transactions/Edit', [
            'transaction' => $transaction,
            'wallets' => $wallets,
            'systemWallets' => $systemWallets,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, TransactionLog $transaction)
    {
        $request->validate([
            'date' => 'required|date',
            'category_id' => 'required|exists:categories,id',
            'source_wallet_id' => 'required|exists:wallets,id',
            'destination_wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:0',
            'subject' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request, $transaction) {
                if ($transaction->is_cleared) {
                    Wallet::where('id', $transaction->source_wallet_id)->increment('balance', $transaction->amount);
                    Wallet::where('id', $transaction->destination_wallet_id)->decrement('balance', $transaction->amount);
                }

                Wallet::where('id', $request->source_wallet_id)->decrement('balance', $request->amount);
                Wallet::where('id', $request->destination_wallet_id)->increment('balance', $request->amount);

                $newSource = Wallet::find($request->source_wallet_id);
                $newDest = Wallet::find($request->destination_wallet_id);
                $mainWallet = ($newSource->group_type !== 'System') ? $newSource : $newDest;
                
                $transaction->update([
                    'date' => $request->date,
                    'category_id' => $request->category_id,
                    'type_id' => Category::find($request->category_id)->type_id,
                    'source_wallet_id' => $newSource->id,
                    'destination_wallet_id' => $newDest->id,
                    'amount' => $request->amount,
                    'balance_before' => $mainWallet->balance + $request->amount,
                    'balance_after' => $mainWallet->balance,
                    'subject' => $request->subject ?? '-',
                    'notes' => $request->notes,
                    'is_cleared' => true,
                ]);
            });

            return redirect()->route('transactions.index')->with('success', 'Transaksi diupdate!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}