<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\TransactionLog;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Search/Index', [
            'query' => $request->input('q', ''),
        ]);
    }

    public function search(Request $request)
    {
        $request->validate(['q' => 'required|string|max:100']);
        $q = strtolower($request->q);
        $userId = Auth::id();

        $results = [];

        // Wallets
        $wallets = Wallet::where('user_id', $userId)
            ->where('group_type', '!=', 'System')
            ->where(function ($query) use ($q) {
                $query->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(keyword) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(account_name) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(account_number) LIKE ?', ["%{$q}%"]);
            })
            ->limit(5)
            ->get(['id', 'name', 'icon']);

        foreach ($wallets as $w) {
            $results[] = [
                'type' => 'Wallet',
                'label' => $w->name,
                'description' => __('Buka dompet'),
                'route' => route('wallets.show', $w->id),
                'icon' => $w->icon ?: 'wallet',
                'id' => "wallet-{$w->id}",
                'color' => 'blue',
            ];
        }

        // Categories
        $categories = Category::where('user_id', $userId)
            ->where(function ($query) use ($q) {
                $query->whereRaw('LOWER(category_name) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(keyword) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(custom_name) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(custom_keyword) LIKE ?', ["%{$q}%"]);
            })
            ->where('is_active', true)
            ->limit(5)
            ->get(['id', 'category_name', 'icon']);

        foreach ($categories as $c) {
            $results[] = [
                'type' => 'Kategori',
                'label' => $c->category_name,
                'description' => __('Lihat transaksi kategori ini'),
                'route' => route('categories.show', $c->id),
                'icon' => $c->icon ?: 'folder',
                'id' => "category-{$c->id}",
                'color' => 'emerald',
            ];
        }

        // Transactions
        $transactions = TransactionLog::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->where(function ($query) use ($q) {
                $query->whereRaw('LOWER(subject) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(notes) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(reference_number) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('CAST(id AS CHAR) LIKE ?', ["%{$q}%"]);
            })
            ->with(['type', 'category', 'sourceWallet', 'destinationWallet'])
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        foreach ($transactions as $t) {
            $results[] = [
                'type' => 'Transaksi',
                'id' => "transaction-{$t->id}",
                'transaction_id' => $t->id,
                'label' => $t->subject,
                'amount' => (float) $t->amount,
                'date' => $t->date,
                'is_cleared' => (bool) $t->is_cleared,
                'transaction_type' => $t->type ? ['id' => $t->type->id, 'name' => $t->type->name] : null,
                'category' => $t->category ? ['id' => $t->category->id, 'category_name' => $t->category->category_name, 'icon' => $t->category->icon] : null,
                'source_wallet' => $t->sourceWallet ? ['id' => $t->sourceWallet->id, 'name' => $t->sourceWallet->name, 'group_type' => $t->sourceWallet->group_type] : null,
                'destination_wallet' => $t->destinationWallet ? ['id' => $t->destinationWallet->id, 'name' => $t->destinationWallet->name, 'group_type' => $t->destinationWallet->group_type] : null,
                'notes' => $t->notes,
                'description' => 'Rp' . number_format($t->amount, 0, ',', '.') . ' — ' . $t->date,
                'route' => route('transactions.edit', $t->id),
                'icon' => $t->category?->icon ?: 'receipt',
                'color' => 'purple',
            ];
        }

        return response()->json([
            'results' => $results,
            'total' => count($results),
        ]);
    }
}
