<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreCategoryRequest;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index()
    {
        $groupedCategories = Auth::user()->categories()
            ->with('type')
            ->orderBy('category_name')
            ->get()
            ->groupBy(function($data) {
                return $data->type->name;
            });

        $totalCategories = Auth::user()->categories()->count();

        return view('categories.index', compact('groupedCategories', 'totalCategories'));
    }
    
    // NAMPILIN FORM TAMBAH
    public function create()
    {
        // HANYA ambil tipe Income dan Expense untuk user
        $types = \App\Models\TransactionType::whereIn('name', ['Income', 'Expense'])->get();
        return view('categories.create', compact('types'));
    }

    // PROSES SIMPAN DATA BARU
    public function store(Request $request)
{
    $validated = $request->validate([
        'category_name' => 'required|string|max:255',
        'type_id' => 'required|exists:transaction_types,id',
        'icon' => 'nullable|string|max:255',
        'icon_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Maks 2MB
        'keyword' => 'nullable|string|max:255',
    ]);

    // Jika user upload file, timpa $validated['icon'] dengan path gambar
    if ($request->hasFile('icon_file')) {
        $path = $request->file('icon_file')->store('icons/categories', 'public');
        $validated['icon'] = $path;
    }

    Auth::user()->categories()->create($validated);
    return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan!');
}

    // NAMPILIN FORM EDIT
    public function edit(Category $category)
    {
        if ($category->user_id !== Auth::id()) abort(403);

        // Proteksi: Jika ini kategori sistem (Transfer/Debt/Receivable), arahkan balik atau kunci
        $systemTypes = ['Transfer', 'Debt', 'Receivable'];
        if (in_array($category->type->name, $systemTypes)) {
            return redirect()->route('categories.index')->with('error', 'Kategori sistem tidak boleh diubah.');
        }

        $types = \App\Models\TransactionType::whereIn('name', ['Income', 'Expense'])->get();
        return view('categories.edit', compact('category', 'types'));
    }

    // PROSES UPDATE DATA LAMA
    public function update(Request $request, Category $category)
{
    if ($category->user_id !== Auth::id()) abort(403);
    
    $validated = $request->validate([
        'category_name' => 'required|string|max:255',
        'type_id' => 'required|exists:transaction_types,id',
        'icon' => 'nullable|string|max:255',
        'icon_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
    ]);

    // Jika user upload file baru
    if ($request->hasFile('icon_file')) {
        // Hapus foto lama jika itu adalah file gambar (bukan teks emoji)
        if ($category->icon && \Str::contains($category->icon, '/')) {
            Storage::disk('public')->delete($category->icon);
        }
        // Simpan foto baru
        $path = $request->file('icon_file')->store('icons/categories', 'public');
        $validated['icon'] = $path;
    }

    $category->update($validated);
    return redirect()->route('categories.index')->with('success', 'Kategori diupdate!');
}

    // HAPUS DATA
    public function destroy(Category $category)
    {
        if ($category->user_id !== Auth::id()) abort(403);

        // Jangan izinkan hapus kategori sistem
        if (in_array($category->type->name, ['Transfer', 'Debt', 'Receivable'])) {
            return redirect()->route('categories.index')->with('error', 'Kategori sistem tidak bisa dihapus.');
        }
        
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Kategori dihapus!');
    }

    public function show(Category $category)
    {
        if ($category->user_id !== Auth::id()) abort(403);

        // Ambil riwayat transaksi khusus kategori ini
        $transactions = Auth::user()->transactionLogs()
            ->where('category_id', $category->id)
            ->with(['type', 'sourceWallet', 'destinationWallet'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Hitung total penggunaan kategori ini
        $totalUsage = $transactions->sum('amount');
        
        // Cek apakah kategori sistem (untuk sembunyikan tombol edit/hapus)
        $isSystem = in_array($category->type->name, ['Transfer', 'Debt', 'Receivable']);

        return view('categories.show', compact('category', 'transactions', 'totalUsage', 'isSystem'));
    }
}