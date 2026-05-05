<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class WalletController extends Controller
{
    // Menampilkan daftar Wallet (opsional jika dibutuhkan)
    public function index()
    {
        $wallets = Auth::user()->wallets;
        return Inertia::render('Wallets/Index', ['wallets' => $wallets]);
    }

    // Tampilkan Form Tambah Wallet
    public function create()
    {
        return Inertia::render('Wallets/Create');
    }

    // Proses Simpan Wallet Baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'balance' => 'required|numeric',
            'icon' => 'nullable|string|max:255',
            'icon_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'keyword' => 'nullable|string|max:255',
            'group_type' => 'required|string'
        ]);

        if ($request->hasFile('icon_file')) {
            $path = $request->file('icon_file')->store('icons/wallets', 'public');
            $validated['icon'] = $path;
        }

        Auth::user()->wallets()->create($validated);

        return redirect()->route('dashboard')->with('success', 'Dompet berhasil ditambahkan!');
    }

    // DETAIL WALLET
    public function show(Wallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) abort(403);

        $transactions = Auth::user()->transactionLogs()
            ->with(['type', 'category', 'sourceWallet', 'destinationWallet'])
            ->where(function ($q) use ($wallet) {
                $q->where('source_wallet_id', $wallet->id)
                  ->orWhere('destination_wallet_id', $wallet->id);
            })
            ->orderBy('date', 'desc')->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('Wallets/Show', [
            'wallet' => $wallet,
            'transactions' => $transactions
        ]);
    }

    // Tampilkan Form Edit
    public function edit(Wallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) abort(403);
        return Inertia::render('Wallets/Edit', [
            'wallet' => $wallet
        ]);
    }

    // Proses Update
    public function update(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'balance' => 'required|numeric',
            'icon' => 'nullable|string|max:255',
            'icon_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'keyword' => 'nullable|string|max:255',
        ]);

        // Logika Update Gambar (Hapus yang lama jika ada upload baru)
        if ($request->hasFile('icon_file')) {
            if ($wallet->icon && Storage::disk('public')->exists($wallet->icon)) {
                Storage::disk('public')->delete($wallet->icon);
            }
            $path = $request->file('icon_file')->store('icons/wallets', 'public');
            $validated['icon'] = $path;
        }

        $wallet->update($validated);
        return redirect()->route('wallets.show', $wallet)->with('success', 'Dompet diupdate!');
    }

    /**
     * PROSES HAPUS (Solusi Error 500)
     */
    public function destroy(Wallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) abort(403);

        try {
            // Hapus file icon jika itu adalah hasil upload (bukan emoji)
            if ($wallet->icon && Storage::disk('public')->exists($wallet->icon)) {
                Storage::disk('public')->delete($wallet->icon);
            }

            $wallet->delete();
            return redirect()->route('dashboard')->with('success', 'Dompet berhasil dihapus!');
            
        } catch (\Exception $e) {
            // Jika gagal karena ada relasi transaksi, kirim pesan error
            return back()->withErrors(['error' => 'Gagal menghapus dompet. Pastikan tidak ada transaksi yang terhubung.']);
        }
    }
}