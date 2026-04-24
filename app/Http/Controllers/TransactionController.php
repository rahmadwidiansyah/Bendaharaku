<?php

namespace App\Http\Controllers;

use App\Models\TransactionLog;
use App\Models\Wallet;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    public function index(Request $request)
{
    $user = Auth::user();

    // 1. Setup Default Tanggal (Bulan Ini)
    $startDate = $request->input('start_date', \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'));
    $endDate = $request->input('end_date', \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d'));

    // 2. Mulai Query dasar
    $query = $user->transactionLogs()->with(['type', 'category', 'sourceWallet', 'destinationWallet']);

    // 3. Filter berdasarkan Tanggal
    $query->whereBetween('date', [$startDate, $endDate]);

    // --- TAMBAHAN: FILTER BERDASARKAN TIPE (BIAR SORT JALAN) ---
    if ($request->has('type') && $request->type != '') {
        $query->whereHas('type', function($q) use ($request) {
            $q->where('name', $request->type);
        });
    }
    // ----------------------------------------------------------

    // 4. Filter berdasarkan Pencarian (Search)
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

    // Urutkan dari yang terbaru, lalu jalankan pagination
    $transactions = $query->orderBy('date', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->paginate(20)
                          ->appends($request->all());

    return view('transactions.index', compact('transactions', 'startDate', 'endDate'));
}

    public function create()
{
    $wallets = Auth::user()->wallets()->where('group_type', '!=', 'System')->get();
    $systemWallets = Auth::user()->wallets()->where('group_type', 'System')->get();
    
    // Ambil semua kategori user beserta tipenya
    $categories = Auth::user()->categories()->with('type')->get();
    
    // Hitung frekuensi penggunaan kategori dari tabel transaction_logs
    $categoryCounts = Auth::user()->transactionLogs()
        ->selectRaw('category_id, count(*) as count')
        ->groupBy('category_id')
        ->pluck('count', 'category_id');

    // Urutkan kategori: Yang paling sering dipakai ada di paling atas
    $categories = $categories->sortByDesc(function($cat) use ($categoryCounts) {
        return $categoryCounts->get($cat->id, 0);
    })->values(); // values() dipakai supaya array-nya rapi saat dikirim ke Javascript

    return view('transactions.create', compact('wallets', 'categories', 'systemWallets'));
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
        // Bungkus proses database saja dalam transaksi
        DB::transaction(function () use ($request, $category) {
            $source = Wallet::findOrFail($request->source_wallet_id);
            $destination = Wallet::findOrFail($request->destination_wallet_id);

            $mainWallet = ($source->group_type !== 'System') ? $source : $destination;
            $balanceBefore = $mainWallet->balance;

            $source->balance -= $request->amount;
            $destination->balance += $request->amount;

            $source->save();
            $destination->save();

            $balanceAfter = $mainWallet->balance;

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

        // PINDAHKAN REDIRECT KE LUAR TRANSACTION
        return redirect()->route('transactions.index')->with('success', 'Transaksi Berhasil!');

    } catch (\Exception $e) {
        dd('Error ditangkap: ' . $e->getMessage()); 
    }
}

public function destroy(TransactionLog $transaction)
{
    try {
        DB::transaction(function () use ($transaction) {
            $source = Wallet::findOrFail($transaction->source_wallet_id);
            $destination = Wallet::findOrFail($transaction->destination_wallet_id);

            // BALIKKAN SALDO: 
            // Source yang tadinya dikurang, sekarang ditambah balik.
            // Destination yang tadinya ditambah, sekarang dikurang balik.
            $source->balance += $transaction->amount;
            $destination->balance -= $transaction->amount;

            $source->save();
            $destination->save();

            // Hapus log transaksi
            $transaction->delete();
        });

        return redirect()->route('transactions.index')->with('success', 'Transaksi dihapus dan saldo dikembalikan!');
    } catch (\Exception $e) {
        dd('Gagal hapus: ' . $e->getMessage());
    }
}
public function edit(TransactionLog $transaction)
{
    $wallets = Auth::user()->wallets()->where('group_type', '!=', 'System')->get();
    $systemWallets = Auth::user()->wallets()->where('group_type', 'System')->get();
    $categories = Auth::user()->categories()->with('type')->get();

    return view('transactions.edit', compact('transaction', 'wallets', 'systemWallets', 'categories'));
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
            // 1. BALIKKAN SALDO LAMA (Reset)
            $oldSource = Wallet::findOrFail($transaction->source_wallet_id);
            $oldDest = Wallet::findOrFail($transaction->destination_wallet_id);
            $oldSource->balance += $transaction->amount;
            $oldDest->balance -= $transaction->amount;
            $oldSource->save();
            $oldDest->save();

            // 2. HITUNG SALDO BARU
            $newSource = Wallet::findOrFail($request->source_wallet_id);
            $newDest = Wallet::findOrFail($request->destination_wallet_id);
            
            $newSource->balance -= $request->amount;
            $newDest->balance += $request->amount;
            $newSource->save();
            $newDest->save();

            // 3. UPDATE LOG
            $mainWallet = ($newSource->group_type !== 'System') ? $newSource : $newDest;
            
            $transaction->update([
                'date' => $request->date,
                'category_id' => $request->category_id,
                'type_id' => Category::find($request->category_id)->type_id,
                'source_wallet_id' => $newSource->id,
                'destination_wallet_id' => $newDest->id,
                'amount' => $request->amount,
                'balance_before' => $mainWallet->balance + $request->amount, // Estimasi sederhana
                'balance_after' => $mainWallet->balance,
                'subject' => $request->subject ?? '-',
                'notes' => $request->notes,
            ]);
        });

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil diupdate!');
    } catch (\Exception $e) {
        dd($e->getMessage());
    }
}
}