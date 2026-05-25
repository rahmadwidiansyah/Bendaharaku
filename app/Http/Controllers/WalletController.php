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
        $user = Auth::user();
        
        // FIX: Blokir dompet 'System' agar tidak tampil di list UI frontend
        $wallets = $user->wallets()
            ->where('group_type', '!=', 'System')
            ->orderBy('id')
            ->get();

        // LOGIKA HITUNG HUTANG (Tetap butuh akses ke dompet System di backend)
        $systemHutang = $user->wallets()->where('name', 'like', '%Hutang%')->where('group_type', 'System')->first();
        $totalHutang = 0;
        if ($systemHutang) {
            $debtIn = $user->transactionLogs()->where('source_wallet_id', $systemHutang->id)->sum('amount');
            $debtPaid = $user->transactionLogs()->where('destination_wallet_id', $systemHutang->id)->sum('amount');
            $totalHutang = max(0, $debtIn - $debtPaid);
        }

        // LOGIKA HITUNG PIUTANG (Tetap butuh akses ke dompet System di backend)
        $systemPiutang = $user->wallets()->where('name', 'like', '%Piutang%')->where('group_type', 'System')->first();
        $totalPiutang = 0;
        if ($systemPiutang) {
            $receivableOut = $user->transactionLogs()->where('destination_wallet_id', $systemPiutang->id)->sum('amount');
            $receivableIn = $user->transactionLogs()->where('source_wallet_id', $systemPiutang->id)->sum('amount');
            $totalPiutang = max(0, $receivableOut - $receivableIn);
        }

        return Inertia::render('Wallets/Index', [
            'wallets' => $wallets,
            'totalHutang' => (int) $totalHutang,
            'totalPiutang' => (int) $totalPiutang,
        ]);
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
            'group_type' => 'required|string',
            'is_pinned' => 'nullable|boolean'
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
        
        // FIX: Blokir akses direct URL ke dompet System
        if ($wallet->group_type === 'System') abort(403, 'Akses ke Dompet Sistem tidak diizinkan.');

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
        
        // FIX: Blokir edit dompet System via URL
        if ($wallet->group_type === 'System') abort(403, 'Dompet Sistem tidak boleh diedit.');

        return Inertia::render('Wallets/Edit', [
            'wallet' => $wallet
        ]);
    }

    // Proses Update
    public function update(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) abort(403);
        if ($wallet->group_type === 'System') abort(403, 'Dompet Sistem tidak boleh diedit.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'balance' => 'required|numeric',
            'icon' => 'nullable|string|max:255',
            'icon_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'keyword' => 'nullable|string|max:255',
            'is_pinned' => 'nullable|boolean',
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
        
        // FIX: Blokir hapus dompet System
        if ($wallet->group_type === 'System') abort(403, 'Dompet Sistem tidak boleh dihapus.');

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

    // SET PIN
    public function setPin(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) abort(403);
        if ($wallet->group_type === 'System') abort(403);

        $validated = $request->validate([
            'state' => 'required|boolean'
        ]);

        $wallet->update(['is_pinned' => $validated['state']]);

        $message = $validated['state'] ? 'Dompet berhasil ditambahkan ke Dashboard!' : 'Dompet berhasil dilepas dari Dashboard!';
        return back()->with('success', $message);
    }
}