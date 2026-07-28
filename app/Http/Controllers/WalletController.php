<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Support\SettingsChangeLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class WalletController extends Controller
{
    // Menampilkan daftar Wallet (opsional jika dibutuhkan)
    public function index()
    {
        $userId = Auth::id();
        $user = Auth::user();

        // Hanya ambil kolom yang benar-benar dipakai di kartu wallet frontend
        // FIX: Blokir dompet 'System' agar tidak tampil di list UI frontend
        $wallets = $user->wallets()
            ->select(['id', 'name', 'balance', 'icon', 'keyword', 'group_type', 'is_pinned'])
            ->where('group_type', '!=', 'System')
            ->orderBy('id')
            ->get();

        // LOGIKA HITUNG HUTANG DAN PIUTANG BERDASARKAN SUBJEK
        // Dihitung lewat 1 query agregasi SQL (GROUP BY subject), bukan menarik
        // seluruh baris transaksi + relasi kategori ke PHP lalu di-groupBy di memory.
        $totalHutang = 0;
        $totalPiutang = 0;

        $balancesRaw = DB::table('transaction_logs')
            ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
            ->where('transaction_logs.user_id', $userId)
            ->where('transaction_logs.is_cleared', true)
            ->whereNotNull('transaction_logs.subject')
            ->where('transaction_logs.subject', '!=', '-')
            ->whereIn('categories.category_name', [
                'Dapat Hutangan', 'Bayar Cicilan Hutang', 'Ngasih Piutang', 'Terima Bayar Piutang',
            ])
            ->select('transaction_logs.subject')
            ->selectRaw("
                SUM(CASE WHEN categories.category_name = 'Dapat Hutangan' THEN amount ELSE 0 END) as debt_borrowed,
                SUM(CASE WHEN categories.category_name = 'Bayar Cicilan Hutang' THEN amount ELSE 0 END) as debt_paid,
                SUM(CASE WHEN categories.category_name = 'Ngasih Piutang' THEN amount ELSE 0 END) as rec_borrowed,
                SUM(CASE WHEN categories.category_name = 'Terima Bayar Piutang' THEN amount ELSE 0 END) as rec_paid
            ")
            ->groupBy('transaction_logs.subject')
            ->get();

        foreach ($balancesRaw as $row) {
            if ($row->debt_borrowed > 0) {
                $totalHutang += max(0, $row->debt_borrowed - $row->debt_paid);
            }
            if ($row->rec_borrowed > 0) {
                $totalPiutang += max(0, $row->rec_borrowed - $row->rec_paid);
            }
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
            'is_pinned' => 'nullable|boolean',
        ]);

        if ($request->hasFile('icon_file')) {
            $path = $request->file('icon_file')->store('icons/wallets', 'public');
            $validated['icon'] = $path;
        }

        $wallet = Auth::user()->wallets()->create($validated);

        // Log wallet creation
        SettingsChangeLogger::logChange(
            Auth::user(),
            'wallet_created',
            'settings.finance.wallets',
            null,
            ['id' => $wallet->id, 'name' => $wallet->name]
        );

        return redirect()->route('dashboard')->with('success', 'Dompet berhasil ditambahkan!');
    }

    // DETAIL WALLET
    public function show(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) {
            abort(403);
        }

        // FIX: Blokir akses direct URL ke dompet System
        if ($wallet->group_type === 'System') {
            abort(403, 'Akses ke Dompet Sistem tidak diizinkan.');
        }

        $defaultStartDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $defaultEndDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $validated['start_date'] ?? $defaultStartDate;
        $endDate = $validated['end_date'] ?? $defaultEndDate;

        $transactions = Auth::user()->transactionLogs()
            ->with(['type', 'category', 'sourceWallet', 'destinationWallet'])
            ->where(function ($q) use ($wallet) {
                $q->where('source_wallet_id', $wallet->id)
                    ->orWhere('destination_wallet_id', $wallet->id);
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('Wallets/Show', [
            'wallet' => $wallet,
            'transactions' => $transactions,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    // Tampilkan Form Edit
    public function edit(Wallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) {
            abort(403);
        }

        // FIX: Blokir edit dompet System via URL
        if ($wallet->group_type === 'System') {
            abort(403, 'Dompet Sistem tidak boleh diedit.');
        }

        return Inertia::render('Wallets/Edit', [
            'wallet' => $wallet,
        ]);
    }

    // Proses Update
    public function update(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) {
            abort(403);
        }
        if ($wallet->group_type === 'System') {
            abort(403, 'Dompet Sistem tidak boleh diedit.');
        }

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

        $original = $wallet->getOriginal();
        $wallet->update($validated);

        // Log per-field changes
        foreach ($validated as $key => $value) {
            $oldVal = $original[$key] ?? null;
            if ((string) $oldVal !== (string) $value) {
                SettingsChangeLogger::logChange(
                    Auth::user(),
                    'wallet:'.$key,
                    'settings.finance.wallets',
                    $oldVal,
                    $value
                );
            }
        }

        return redirect()->route('wallets.show', $wallet)->with('success', 'Dompet diupdate!');
    }

    /**
     * PROSES HAPUS (Solusi Error 500)
     */
    public function destroy(Wallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) {
            abort(403);
        }

        // FIX: Blokir hapus dompet System
        if ($wallet->group_type === 'System') {
            abort(403, 'Dompet Sistem tidak boleh dihapus.');
        }

        try {
            // Hapus file icon jika itu adalah hasil upload (bukan emoji)
            if ($wallet->icon && Storage::disk('public')->exists($wallet->icon)) {
                Storage::disk('public')->delete($wallet->icon);
            }

            $old = $wallet->toArray();
            $wallet->delete();

            // Log deletion
            SettingsChangeLogger::logChange(
                Auth::user(),
                'wallet_deleted',
                'settings.finance.wallets',
                $old,
                null
            );

            return redirect()->route('dashboard')->with('success', 'Dompet berhasil dihapus!');

        } catch (\Exception $e) {
            // Jika gagal karena ada relasi transaksi, kirim pesan error
            return back()->withErrors(['error' => 'Gagal menghapus dompet. Pastikan tidak ada transaksi yang terhubung.']);
        }
    }

    // SET PIN
    public function setPin(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) {
            abort(403);
        }
        if ($wallet->group_type === 'System') {
            abort(403);
        }

        $validated = $request->validate([
            'state' => 'required|boolean',
        ]);

        $oldVal = $wallet->is_pinned;
        $wallet->update(['is_pinned' => $validated['state']]);

        // Log pin/unpin action
        SettingsChangeLogger::logChange(
            Auth::user(),
            'wallet:is_pinned',
            'settings.finance.wallets',
            $oldVal,
            $validated['state']
        );

        $message = $validated['state'] ? 'Dompet berhasil ditambahkan ke Dashboard!' : 'Dompet berhasil dilepas dari Dashboard!';

        return back()->with('success', $message);
    }
}