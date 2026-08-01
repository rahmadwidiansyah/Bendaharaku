<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BudgetGenerationStatus;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateBudgetJob;
use App\Models\BudgetGenerationStatus as BudgetGenerationStatusModel;
use App\Models\BudgetGroup;
use App\Models\Category;
use App\Services\Budgeting\BudgetingServiceInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private BudgetingServiceInterface $budgetingService) {}

    public function show(int $year, int $month)
    {
        $budgetGroup = BudgetGroup::where('user_id', Auth::id())
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->with(['items.budgetable', 'expenseGroups'])
            ->first();

        if (! $budgetGroup) {
            return response()->json(['message' => 'Budget not found for this period.'], 404);
        }

        $summary = $this->budgetingService->getBudgetSummary($budgetGroup);

        $data = $budgetGroup->toArray();
        $data['summary'] = $summary;

        return response()->json($data);
    }

    /**
     * POST /api/v1/budget/generate
     * Dispatch GenerateBudgetJob ke background queue dan balas segera (202).
     * LLM dipanggil di worker, sehingga proses tidak batal saat user pindah halaman.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $targetMonth = Carbon::create($validated['year'], $validated['month'], 1)->startOfMonth();

        if (! $targetMonth->eq(now()->startOfMonth())) {
            return response()->json([
                'message' => 'Budget AI hanya dapat dibuat untuk bulan berjalan. Pilih bulan sekarang.',
            ], 422);
        }

        BudgetGenerationStatusModel::updateOrCreate(
            ['user_id' => Auth::id(), 'year' => $validated['year'], 'month' => $validated['month']],
            ['status' => BudgetGenerationStatus::Pending->value, 'error_message' => null],
        );

        GenerateBudgetJob::dispatch(Auth::id(), $validated['month'], $validated['year']);

        return response()->json([
            'queued' => true,
            'status' => BudgetGenerationStatus::Pending->value,
        ], 202);
    }

    /**
     * GET /api/v1/budget/generate/status
     * Status proses generate budget untuk periode tertentu (polling frontend).
     */
    public function generationStatus(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $targetMonth = Carbon::create($validated['year'], $validated['month'], 1)->startOfMonth();

        if (! $targetMonth->eq(now()->startOfMonth())) {
            return response()->json([
                'message' => 'Budget AI hanya dapat dibuat untuk bulan berjalan. Pilih bulan sekarang.',
            ], 422);
        }

        $status = BudgetGenerationStatusModel::where('user_id', Auth::id())
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->first();

        if (! $status) {
            return response()->json(['status' => 'idle', 'error_message' => null]);
        }

        // Jika status masih pending/processing tapi sudah lebih dari 5 menit,
        // anggap job stuck (mati di queue) → kembalikan sebagai failed agar frontend tidak polling selamanya
        $isStuck = in_array($status->status, ['pending', 'processing'])
            && $status->updated_at->diffInMinutes(now()) > 5;

        if ($isStuck) {
            $status->update(['status' => 'failed', 'error_message' => 'Proses generate timeout. Silakan coba lagi.']);

            return response()->json([
                'status' => 'failed',
                'error_message' => 'Proses generate timeout. Silakan coba lagi.',
            ]);
        }

        return response()->json([
            'status' => $status->status,
            'error_message' => $status->error_message,
        ]);
    }

    public function update(Request $request, BudgetGroup $budgetGroup)
    {
        $this->authorize('update', $budgetGroup);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:budget_items,id',
            'items.*.target_amount' => 'required|numeric|min:0',
        ]);

        $totalAmount = 0;
        foreach ($validated['items'] as $itemData) {
            $item = $budgetGroup->items()->find($itemData['id']);
            if ($item && $item->budgetable_type === Category::class) {
                $item->update(['target_amount' => $itemData['target_amount']]);
                $totalAmount += $itemData['target_amount'];
            }
        }

        $budgetGroup->update(['total_budget_amount' => $totalAmount]);

        return response()->json($budgetGroup->fresh('items.budgetable'));
    }

    public function settings()
    {
        $user = Auth::user();

        return response()->json([
            'auto_budget_enabled' => (bool) $user->auto_budget_enabled,
            'bot_name' => $user->bot_display_name,
            'bot_avatar' => $user->bot_avatar_url,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'auto_budget_enabled' => 'required|boolean',
        ]);

        Auth::user()->update(['auto_budget_enabled' => $validated['auto_budget_enabled']]);

        return response()->json(['message' => 'Settings updated successfully.']);
    }
}
