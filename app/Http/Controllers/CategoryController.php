<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\TransactionType;
use App\Support\SettingsChangeLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            ->groupBy(function ($data) {
                return $data->type->name;
            });

        $totalCategories = Auth::user()->categories()->count();

        return Inertia::render('Categories/Index', [
            'groupedCategories' => $groupedCategories,
            'totalCategories' => $totalCategories,
        ]);
    }

    public function create(Request $request): Response
    {
        $types = TransactionType::whereIn('name', ['Income', 'Expense'])->get();

        return Inertia::render('Categories/Create', [
            'types' => $types,
            'defaultType' => $request->query('type', 'Expense'),
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

        $category = Auth::user()->categories()->create($validated);

        // Log category creation
        SettingsChangeLogger::logChange(
            Auth::user(),
            'category_created',
            'settings.finance.categories',
            null,
            ['id' => $category->id, 'name' => $category->category_name]
        );

        return redirect()->route('transactions.create')->with('success', 'Kategori ditambahkan!');
    }

    public function edit(Category $category): Response
    {
        if ($category->user_id !== Auth::id()) {
            abort(403);
        }

        $types = TransactionType::all(); // Provide all types, but UI can disable changes for system categories

        return Inertia::render('Categories/Edit', [
            'category' => $category,
            'types' => $types,
            'isSystem' => $category->system_key !== null,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        if ($category->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'type_id' => 'required|exists:transaction_types,id',
            'icon' => 'nullable|string|max:255',
            'icon_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'keyword' => 'nullable|string|max:255',
        ]);

        if ($category->system_key !== null) {
            // Cannot change transaction type of system categories
            unset($validated['type_id']);
        }

        if ($request->hasFile('icon_file')) {
            if ($category->icon && \Str::contains($category->icon, '/')) {
                Storage::disk('public')->delete($category->icon);
            }
            $path = $request->file('icon_file')->store('icons/categories', 'public');
            $validated['icon'] = $path;
        }

        $original = $category->getOriginal();
        $category->update($validated);

        // Log per-field changes
        foreach ($validated as $key => $value) {
            $oldVal = $original[$key] ?? null;
            if ((string) $oldVal !== (string) $value) {
                SettingsChangeLogger::logChange(
                    Auth::user(),
                    'category:'.$key,
                    'settings.finance.categories',
                    $oldVal,
                    $value
                );
            }
        }

        return redirect()->route('categories.index')->with('success', 'Kategori diupdate!');
    }

    public function destroy(Category $category)
    {
        if ($category->user_id !== Auth::id()) {
            abort(403);
        }

        if ($category->system_key !== null || in_array($category->type->name, ['Transfer', 'Debt', 'Receivable'])) {
            return redirect()->route('categories.index')->with('error', 'Kategori sistem tidak bisa dihapus.');
        }

        $old = $category->toArray();
        $category->delete();

        // Log deletion
        SettingsChangeLogger::logChange(
            Auth::user(),
            'category_deleted',
            'settings.finance.categories',
            $old,
            null
        );

        return redirect()->route('categories.index')->with('success', 'Kategori dihapus!');
    }

    public function show(Request $request, Category $category): Response
    {
        if ($category->user_id !== Auth::id()) {
            abort(403);
        }

        // Detail dari Vault selalu fokus ke bulan berjalan. Saat dibuka dari
        // Analytics, rentang tanggal pada URL dipakai supaya angkanya konsisten.
        $defaultStartDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $defaultEndDate = Carbon::now()->format('Y-m-d');
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $validated['start_date'] ?? $defaultStartDate;
        $endDate = $validated['end_date'] ?? $defaultEndDate;

        $transactions = Auth::user()->transactionLogs()
            ->where('category_id', $category->id)
            ->with(['type', 'sourceWallet', 'destinationWallet'])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalUsage = (float) $transactions->sum('amount');
        $isSystem = $category->system_key !== null;

        return Inertia::render('Categories/Show', [
            'category' => $category->load('type'),
            'transactions' => $transactions,
            'totalUsage' => $totalUsage,
            'isSystem' => $isSystem,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
