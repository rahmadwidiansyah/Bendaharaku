<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AiToolLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAiToolLogTest extends TestCase
{
    use RefreshDatabase;

    private function ownerUser(): User
    {
        Role::firstOrCreate(['name' => 'owner']);
        Permission::firstOrCreate(['name' => 'ai.logs.view']);

        $user = User::factory()->create();
        $user->assignRole('owner');
        $user->givePermissionTo('ai.logs.view');

        return $user;
    }

    private function makeToolLog(array $overrides = []): AiToolLog
    {
        return AiToolLog::create(array_merge([
            'user_id' => $this->ownerUser()->id,
            'trace_id' => 'trace-tool-'.uniqid(),
            'tool' => 'get_transactions',
            'params' => ['start_date' => '2026-10-01', 'end_date' => '2026-10-31'],
            'status' => 'success',
            'duration_ms' => 12,
            'result_rows' => 3,
            'error_message' => null,
        ], $overrides));
    }

    /** @test */
    public function test_tanpa_role_admin_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.ai_tool_logs.index'))
            ->assertForbidden();
    }

    /** @test */
    public function test_owner_melihat_daftar_tool_log(): void
    {
        $log = $this->makeToolLog();

        $response = $this->actingAs($this->ownerUser())
            ->get(route('admin.ai_tool_logs.index'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/AiToolLogs/Index')
                ->where('logs.data.0.id', $log->id)
                ->where('logs.data.0.tool', 'get_transactions')
                ->where('logs.data.0.user_email', fn ($email) => $email !== null)
        );
    }

    /** @test */
    public function test_filter_tool_dan_status_diterapkan(): void
    {
        $this->makeToolLog(['tool' => 'get_transactions', 'status' => 'success']);
        $this->makeToolLog(['tool' => 'get_wallets', 'status' => 'validation_error']);

        $response = $this->actingAs($this->ownerUser())
            ->get(route('admin.ai_tool_logs.index', ['tool' => 'get_wallets']));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->where('logs.total', 1)
                ->where('logs.data.0.tool', 'get_wallets')
                ->where('logs.data.0.status', 'validation_error')
        );
    }

    /** @test */
    public function test_detail_tool_log_tampilkan_params_terdecode(): void
    {
        $log = $this->makeToolLog();

        $response = $this->actingAs($this->ownerUser())
            ->get(route('admin.ai_tool_logs.show', $log->id));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/AiToolLogs/Show')
                ->where('log.tool', 'get_transactions')
                ->where('log.params.start_date', '2026-10-01')
        );
    }

    /** @test */
    public function test_detail_log_tidak_ada_404(): void
    {
        $this->actingAs($this->ownerUser())
            ->get(route('admin.ai_tool_logs.show', 999999))
            ->assertNotFound();
    }
}
