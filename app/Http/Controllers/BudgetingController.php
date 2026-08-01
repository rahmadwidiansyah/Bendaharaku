<?php

namespace App\Http\Controllers;

use App\Models\BudgetGroup;
use App\Models\Category;
use App\Models\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BudgetingController extends Controller
{
    public function create(): Response
    {
        $user = Auth::user();

        $categories = $user->categories()
            ->whereHas('type', fn ($q) => $q->where('name', 'Expense'))
            ->with('type')
            ->orderBy('category_name')
            ->get(['id', 'type_id', 'category_name', 'icon', 'custom_name', 'custom_icon']);

        $expenseGroups = config('bendaharaku.budget.expense_groups');

        $existingBudget = BudgetGroup::where('user_id', $user->id)
            ->where('period_month', now()->format('n'))
            ->where('period_year', now()->format('Y'))
            ->with(['items.budgetable', 'expenseGroups'])
            ->first();

        return Inertia::render('Budgeting/Create', [
            'categories' => $categories,
            'expenseGroups' => $expenseGroups,
            'existingBudget' => $existingBudget,
            'botName' => $user->bot_display_name,
            'botAvatar' => $user->bot_avatar_url,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $groupKeys = array_keys((array) config('bendaharaku.budget.expense_groups', []));

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.category_id' => ['required', 'integer', 'distinct', Rule::exists('categories', 'id')],
            'rows.*.group_key' => ['nullable', 'string', Rule::in(array_merge($groupKeys, ['custom']))],
            'rows.*.custom_group_name' => ['nullable', 'string', 'max:50', 'required_if:rows.*.group_key,custom'],
            'rows.*.target_amount' => ['required', 'numeric', 'min:1'],
            'delete_ai' => ['nullable', 'boolean'],
        ]);

        $expenseTypeId = TransactionType::where('name', 'Expense')->value('id');
        if ($expenseTypeId === null) {
            return back()->with('error', 'Tipe transaksi "Expense" tidak ditemukan.');
        }

        $allowedCategoryIds = Category::where('user_id', $user->id)
            ->where('type_id', $expenseTypeId)
            ->pluck('id')
            ->all();

        foreach ($validated['rows'] as $row) {
            if (! in_array($row['category_id'], $allowedCategoryIds, true)) {
                return back()->with('error', 'Kategori yang dipilih tidak valid.');
            }
        }

        $month = (int) now()->format('n');
        $year = (int) now()->format('Y');

        $existing = BudgetGroup::where('user_id', $user->id)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->with(['items', 'expenseGroups'])
            ->first();

        // Baris dengan grup kosong = kategori tidak dipilih (detach dari budget).
        // Grup 'custom' ditransformasi: group_key = slug nama, group_name = nama ketikan user.
        $rows = collect($validated['rows'])->map(function (array $row) {
            if (($row['group_key'] ?? null) === 'custom') {
                $customName = trim((string) ($row['custom_group_name'] ?? ''));
                $slug = Str::slug($customName);
                $row['group_key'] = 'custom-'.($slug ?: 'custom');
                $row['group_name'] = $customName;
            } elseif (filled($row['group_key'] ?? null)) {
                $row['group_name'] = (string) config("bendaharaku.budget.expense_groups.{$row['group_key']}", $row['group_key']);
            } else {
                $row['group_name'] = null;
            }

            return $row;
        });

        $selected = $rows
            ->filter(fn ($row) => filled($row['group_key']))
            ->keyBy('category_id');

        $mergeWithAi = $existing !== null
            && $existing->generated_by === 'ai'
            && ! ($validated['delete_ai'] ?? false);

        $final = collect();

        if ($mergeWithAi) {
            $aiGroupOf = function (int $categoryId) use ($existing): array {
                foreach ($existing->expenseGroups as $group) {
                    if (in_array($categoryId, $group->category_ids, true)) {
                        return ['key' => $group->group_key, 'name' => $group->group_name];
                    }
                }

                return ['key' => null, 'name' => null];
            };

            // Item AI yang tidak dipilih dipertahankan; yang dipilih memakai nilai manual
            foreach ($existing->items as $item) {
                if ($item->budgetable_type !== Category::class) {
                    continue;
                }
                $categoryId = (int) $item->budgetable_id;
                $row = $selected->get($categoryId);
                $aiGroup = $aiGroupOf($categoryId);
                $final->put($categoryId, [
                    'category_id' => (int) $categoryId,
                    'target_amount' => $row !== null ? (float) $row['target_amount'] : (float) $item->target_amount,
                    'group_key' => $row !== null ? $row['group_key'] : $aiGroup['key'],
                    'group_name' => $row !== null ? $row['group_name'] : $aiGroup['name'],
                ]);
            }

            // Kategori baru yang belum pernah ada di budget AI
            foreach ($selected as $categoryId => $row) {
                if (! $final->has($categoryId)) {
                    $final->put($categoryId, [
                        'category_id' => (int) $categoryId,
                        'target_amount' => (float) $row['target_amount'],
                        'group_key' => $row['group_key'],
                        'group_name' => $row['group_name'],
                    ]);
                }
            }
        } else {
            foreach ($selected as $categoryId => $row) {
                $final->put($categoryId, [
                    'category_id' => (int) $categoryId,
                    'target_amount' => (float) $row['target_amount'],
                    'group_key' => $row['group_key'],
                    'group_name' => $row['group_name'],
                ]);
            }
        }

        $totalAmount = $final->sum('target_amount');

        $budgetGroup = DB::transaction(function () use ($user, $month, $year, $existing, $final, $totalAmount, $validated) {
            $budgetGroup = $existing ?? new BudgetGroup([
                'user_id' => $user->id,
                'period_month' => $month,
                'period_year' => $year,
            ]);

            $budgetGroup->name = $validated['name'] ?? $budgetGroup->name;
            $budgetGroup->total_budget_amount = $totalAmount;
            $budgetGroup->ai_notes = null;
            $budgetGroup->generated_by = 'manual';
            $budgetGroup->save();

            $budgetGroup->items()->delete();
            $budgetGroup->expenseGroups()->delete();

            foreach ($final as $data) {
                $budgetGroup->items()->create([
                    'budgetable_id' => $data['category_id'],
                    'budgetable_type' => Category::class,
                    'target_amount' => $data['target_amount'],
                ]);
            }

            foreach ($final->groupBy('group_key') as $groupKey => $items) {
                $budgetGroup->expenseGroups()->create([
                    'group_key' => $groupKey,
                    'group_name' => (string) ($items->first()['group_name'] ?? $groupKey),
                    'category_ids' => $items->pluck('category_id')->all(),
                ]);
            }

            return $budgetGroup;
        });

        return redirect()->route('budgeting.index')
            ->with('success', 'Budget berhasil dibuat!');
    }
}
