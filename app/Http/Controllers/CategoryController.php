<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreCategoryRequest;
use Illuminate\Support\Facades\Storage;

use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $groupedCategories = Auth::user()->categories()
            ->with('type')
            ->orderBy('category_name')
            ->get()
            ->groupBy(function($data) {
                return $data->type->name;
            });

        $totalCategories = Auth::user()->categories()->count();

        return Inertia::render('Categories/Index', [
            'groupedCategories' => $groupedCategories,
            'totalCategories' => $totalCategories,
        ]);
    }
    
    public function create(): Response
    {
        $types = \App\Models\TransactionType::whereIn('name', ['Income', 'Expense'])->get();
        return Inertia::render('Categories/Create', [
            'types' => $types,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'type_id' => 'required|exists:transaction_types,id',
            'icon' => 'nullable|string|max:255',
            'icon_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'keyword' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('icon_file')) {
            $path = $request->file('icon_file')->store('icons/categories', 'public');
            $validated['icon'] = $path;
        }

        Auth::user()->categories()->create($validated);
        return redirect()->route('categories.index')->with('success', 'Kategori ditambahkan!');
    }

    public function edit(Category $category): Response
    {
        if ($category->user_id !== Auth::id()) abort(403);

        $systemTypes = ['Transfer', 'Debt', 'Receivable'];
        if (in_array($category->type->name, $systemTypes)) {
            return redirect()->route('categories.index')->with('error', 'Kategori sistem tidak boleh diubah.');
        }

        $types = \App\Models\TransactionType::whereIn('name', ['Income', 'Expense'])->get();
        return Inertia::render('Categories/Edit', [
            'category' => $category,
            'types' => $types,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        if ($category->user_id !== Auth::id()) abort(403);
        
        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'type_id'       => 'required|exists:transaction_types,id',
            'icon'          => 'nullable|string|max:255',
            'icon_file'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'keyword'       => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('icon_file')) {
            if ($category->icon && \Str::contains($category->icon, '/')) {
                Storage::disk('public')->delete($category->icon);
            }
            $path = $request->file('icon_file')->store('icons/categories', 'public');
            $validated['icon'] = $path;
        }

        $category->update($validated);
        return redirect()->route('categories.index')->with('success', 'Kategori diupdate!');
    }

    public function destroy(Category $category)
    {
        if ($category->user_id !== Auth::id()) abort(403);

        if (in_array($category->type->name, ['Transfer', 'Debt', 'Receivable'])) {
            return redirect()->route('categories.index')->with('error', 'Kategori sistem tidak bisa dihapus.');
        }
        
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Kategori dihapus!');
    }

    public function show(Category $category): Response
    {
        if ($category->user_id !== Auth::id()) abort(403);

        $transactions = Auth::user()->transactionLogs()
            ->where('category_id', $category->id)
            ->with(['type', 'sourceWallet', 'destinationWallet'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalUsage = (float) $transactions->sum('amount');
        $isSystem = in_array($category->type->name, ['Transfer', 'Debt', 'Receivable']);

        return Inertia::render('Categories/Show', [
            'category' => $category->load('type'),
            'transactions' => $transactions,
            'totalUsage' => $totalUsage,
            'isSystem' => $isSystem,
        ]);
    }
}