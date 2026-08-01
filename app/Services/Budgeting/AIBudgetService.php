<?php

namespace App\Services\Budgeting;

use App\Exceptions\AiProviderException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Models\BudgetGroup;
use App\Models\Category;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\AI\AiCredentialManager;
use App\Services\AI\AiPreferenceManager;
use App\Services\AI\AiProviderFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AIBudgetService implements BudgetingServiceInterface
{
    public function __construct(
        private AiProviderFactory $aiProviderFactory,
        private AiPreferenceManager $aiPreferenceManager,
        private AiCredentialManager $aiCredentialManager,
    ) {}

    public function generate(User $user, int $month, int $year): BudgetGroup
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->subMonths(3);
        $endDate = Carbon::create($year, $month, 1)->startOfMonth()->subDay();

        $expenseTypeId = TransactionType::where('name', 'Expense')->value('id');
        if ($expenseTypeId === null) {
            throw new RuntimeException('Tipe transaksi "Expense" tidak ditemukan.');
        }

        $categories = Category::where('user_id', $user->id)
            ->where('type_id', $expenseTypeId)
            ->get(['id', 'category_name', 'icon']);

        $transactions = TransactionLog::where('user_id', $user->id)
            ->where('type_id', $expenseTypeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $spendingByCategory = $transactions->groupBy('category_id')->map(fn ($group) => $group->sum('amount'));

        $prompt = $this->constructPrompt($categories, $spendingByCategory);

        $aiPreference = $this->aiPreferenceManager->resolveActivePreference($user);
        if ($aiPreference === null) {
            throw new RuntimeException('Tidak ada preferensi AI aktif. Silakan atur model AI di pengaturan.');
        }

        $credential = $this->aiCredentialManager->getCredential($user, $aiPreference->provider);
        if ($credential === null || blank($credential->api_key)) {
            throw new RuntimeException('API Key AI tidak ditemukan atau tidak valid.');
        }

        $adapter = $this->aiProviderFactory->make($aiPreference->provider);

        try {
            $response = $adapter->generateText(
                prompt: $prompt,
                apiKey: $credential->api_key,
                model: $aiPreference->selected_model ?? $aiPreference->provider->defaultModel(),
            );
        } catch (AiTimeoutException|AiRateLimitException|AiProviderException $e) {
            Log::error('Budget AI generate gagal', [
                'user_id' => $user->id,
                'provider' => $aiPreference->provider->value,
                'model' => $aiPreference->selected_model ?? $aiPreference->provider->defaultModel(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $budgetData = json_decode($response, true);

        if (! is_array($budgetData) || ! isset($budgetData['notes']) || ! is_array($budgetData['by_category'] ?? null)) {
            throw new \UnexpectedValueException('AI returned an invalid budget format.');
        }

        $categoryIds = $categories->pluck('id')->all();
        $validCategories = collect($budgetData['by_category'])->filter(fn ($item) => isset($item['id'], $item['amount']) && in_array($item['id'], $categoryIds) && is_numeric($item['amount']) && $item['amount'] >= 0);

        if ($validCategories->isEmpty()) {
            throw new \UnexpectedValueException('AI returned no valid budget items.');
        }

        $groupKeys = array_keys((array) config('bendaharaku.budget.expense_groups', []));
        $groupAssignments = [];
        foreach ((array) ($budgetData['expense_groups'] ?? []) as $group) {
            if (! is_array($group) || ! isset($group['group'], $group['category_ids']) || ! is_array($group['category_ids'])) {
                continue;
            }
            if (! in_array($group['group'], $groupKeys, true)) {
                continue;
            }
            $validIds = array_values(array_intersect(array_map('intval', $group['category_ids']), $categoryIds));
            if ($validIds !== []) {
                $groupAssignments[$group['group']] = $validIds;
            }
        }

        return DB::transaction(function () use ($user, $month, $year, $budgetData, $validCategories, $groupAssignments) {
            $totalAmount = $validCategories->sum('amount');

            $budgetGroup = BudgetGroup::firstOrNew([
                'user_id' => $user->id,
                'period_month' => $month,
                'period_year' => $year,
            ]);

            $budgetGroup->total_budget_amount = $totalAmount;
            $budgetGroup->ai_notes = $budgetData['notes'];
            $budgetGroup->generated_by = 'ai';
            $budgetGroup->save();

            $budgetGroup->items()->delete();
            $budgetGroup->expenseGroups()->delete();

            foreach ($validCategories as $item) {
                $budgetGroup->items()->create([
                    'budgetable_id' => $item['id'],
                    'budgetable_type' => Category::class,
                    'target_amount' => $item['amount'],
                ]);
            }

            foreach ($groupAssignments as $groupKey => $categoryIds) {
                $budgetGroup->expenseGroups()->create([
                    'group_key' => $groupKey,
                    'group_name' => (string) config("bendaharaku.budget.expense_groups.{$groupKey}", $groupKey),
                    'category_ids' => $categoryIds,
                ]);
            }

            return $budgetGroup->fresh(['items', 'expenseGroups']);
        });
    }

    private function constructPrompt($categories, $spendingByCategory): string
    {
        $categoryData = $categories->map(fn ($cat) => [
            'id' => $cat->id,
            'name' => $cat->category_name,
            'icon' => $cat->icon,
            'spent_last_3_months' => (float) ($spendingByCategory[$cat->id] ?? 0),
        ])->values()->toJson();

        $groupKeys = array_keys((array) config('bendaharaku.budget.expense_groups', []));
        $groupList = json_encode($groupKeys);

        $taxonomy = [
            'fixed' => 'Fixed expenses: same amount every month and mandatory — basic needs (rent/mortgage, basic electricity, water, neighborhood dues), connectivity & work (home internet, mobile data), routine subscriptions (VPS, cloud backup, monthly software).',
            'variable' => 'Variable expenses: amount changes monthly based on usage — daily consumption (groceries, dine-out, coffee), transportation (fuel, tolls, ride-hailing), fluctuating utilities (extra electricity from 24/7 homelab servers).',
            'sinking_fund' => 'Sinking funds: expenses that certainly happen but not every month (quarterly, semester, yearly) — annual homelab & dev costs (domain renewal, yearly VPS/hosting, paid SSL), vehicle tax & maintenance (annual tax, periodic service, oil change), health & care (annual dentist, new glasses).',
            'investment' => 'Savings & capital expenditure: money set aside to grow or protect assets — emergency fund, investments (mutual funds, stocks, gold), hardware upgrade fund (new server parts, RAM, SSD/HDD).',
            'discretionary' => 'Discretionary expenses: fully optional, lifestyle — entertainment (cinema, streaming subscriptions, games), hobbies (gadgets beyond work needs, aesthetic homelab accessories).',
        ];

        return "Based on the following expense categories and their spending for the last 3 months, create a monthly budget recommendation. Return only valid JSON. Preserve category IDs exactly and use non-negative numeric amounts. Classify EVERY category into exactly one expense group using this taxonomy: {$taxonomy[$groupKeys[0]]} / {$taxonomy[$groupKeys[1]]} / {$taxonomy[$groupKeys[2]]} / {$taxonomy[$groupKeys[3]]} / {$taxonomy[$groupKeys[4]]}. As allocation guidance, follow the 50/20/30 rule: 50% to fixed & variable essentials, 20% to investment & sinking funds, 30% to discretionary. Format: { \"notes\": \"...\", \"by_category\": [{\"id\": 123, \"amount\": 123.45}], \"expense_groups\": [{\"group\": \"fixed\", \"category_ids\": [123, 456]}] }. Allowed groups: {$groupList}. Categories: {$categoryData}";
    }

    public function getBudgetSummary(BudgetGroup $budgetGroup): array
    {
        $summary = [];
        $startDate = Carbon::create($budgetGroup->period_year, $budgetGroup->period_month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $expenseTypeId = TransactionType::where('name', 'Expense')->value('id');
        if ($expenseTypeId === null) {
            throw new RuntimeException('Tipe transaksi "Expense" tidak ditemukan.');
        }

        foreach ($budgetGroup->items as $item) {
            $spentQuery = TransactionLog::where('user_id', $budgetGroup->user_id)
                ->where('type_id', $expenseTypeId)
                ->whereBetween('date', [$startDate, $endDate]);

            if ($item->budgetable_type === Category::class) {
                $spentQuery->where('category_id', $item->budgetable_id);
            } elseif ($item->budgetable_type === TransactionType::class) {
                $spentQuery->where('type_id', $item->budgetable_id);
            } else {
                $spentQuery->whereRaw('1 = 0');
            }

            $spent = (float) $spentQuery->sum('amount');
            $target = (float) $item->target_amount;

            $summary[$item->id] = [
                'target' => $target,
                'spent' => $spent,
                'remaining' => $target - $spent,
                'percentage' => $target > 0 ? min(100, round(($spent / $target) * 100, 1)) : 0,
            ];
        }

        return $summary;
    }
}
