<?php

namespace Tests\Feature\Chat;

use App\Chat\Services\AiReportClient;
use App\Chat\Services\MonthlyReportService;
use App\Models\MonthlyReport;
use App\Models\User;
use App\Services\AI\AiProviderFactory;
use App\Services\AI\Routing\AiRoutingExecutor;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class MonthlyReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    private function serviceWithExecutor(AiRoutingExecutor $executor): MonthlyReportService
    {
        return new MonthlyReportService(new AiReportClient, $executor, app(AiProviderFactory::class));
    }

    public function test_generate_monthly_report_without_routing_preference_does_not_throw(): void
    {
        $executor = Mockery::mock(AiRoutingExecutor::class);
        $executor->shouldReceive('executeText')->once()->andReturn([
            'text' => 'Ringkasan AI',
            'routing' => [
                'selected_model' => 'gpt-test',
                'selected_provider' => 'openai',
            ],
        ]);

        $result = $this->serviceWithExecutor($executor)->generateGeminiMonthlyReport(
            $this->user,
            collect(),
            'Ringkasan lokal',
            now()->startOfMonth(),
        );

        $this->assertSame('Ringkasan AI', $result['summary']);
        $this->assertSame('gpt-test', $result['model']);
        $this->assertSame('openai', $result['provider']);
    }

    public function test_generate_monthly_report_falls_back_when_pool_throws(): void
    {
        $executor = Mockery::mock(AiRoutingExecutor::class);
        $executor->shouldReceive('executeText')->once()->andThrow(new \RuntimeException('pool down'));

        $result = $this->serviceWithExecutor($executor)->generateGeminiMonthlyReport(
            $this->user,
            collect(),
            'Ringkasan lokal',
            now()->startOfMonth(),
        );

        $this->assertNull($result);
    }

    public function test_ensure_monthly_report_exists_uses_actual_routing_provider(): void
    {
        $executor = Mockery::mock(AiRoutingExecutor::class);
        $executor->shouldReceive('executeText')->once()->andReturn([
            'text' => 'Ringkasan AI',
            'routing' => [
                'selected_model' => 'gpt-test',
                'selected_provider' => 'openai',
            ],
        ]);

        $this->serviceWithExecutor($executor)->ensureMonthlyReportExists($this->user, now()->startOfMonth());

        $report = MonthlyReport::where('user_id', $this->user->id)
            ->whereDate('period_month', now()->startOfMonth()->toDateString())
            ->first();

        $this->assertNotNull($report);
        $this->assertSame('openai', $report->provider);
        $this->assertSame('gpt-test', $report->model);
    }

    public function test_monthly_report_payload_comparison_uses_current_metrics(): void
    {
        $ref = new \ReflectionClass(MonthlyReportService::class);
        $service = $ref->newInstanceWithoutConstructor();

        $method = $ref->getMethod('buildMonthlyReportPayload');
        $method->setAccessible(true);

        $makeTransaction = fn (string $type, float $amount) => (object) [
            'date' => Carbon::parse('2026-10-05'),
            'type' => (object) ['name' => $type],
            'category' => (object) ['category_name' => 'Makan & Minum'],
            'sourceWallet' => (object) ['name' => 'Dompet Utama'],
            'destinationWallet' => null,
            'amount' => $amount,
            'notes' => null,
        ];

        $previousReport = new MonthlyReport([
            'period_month' => Carbon::parse('2026-09-01'),
            'final_summary' => 'Ringkasan September',
            'metrics' => [
                'transaction_count' => 2,
                'income' => 1000.0,
                'expense' => 400.0,
                'net' => 600.0,
            ],
        ]);

        $payload = $method->invoke(
            $service,
            collect([$makeTransaction('Income', 2000.0), $makeTransaction('Expense', 500.0)]),
            'Ringkasan lokal',
            Carbon::parse('2026-10-01'),
            $previousReport,
        );

        $comparison = $payload['pembanding_bulan_sebelumnya']['comparison'];

        $this->assertSame(1000.0, (float) $comparison['income_diff']);
        $this->assertSame(100.0, (float) $comparison['expense_diff']);
        $this->assertSame(900.0, (float) $comparison['net_diff']);
        $this->assertSame(0, $comparison['transaction_count_diff']);
        $this->assertSame('up', $comparison['trend']);
    }
}
