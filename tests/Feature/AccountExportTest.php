<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    public function test_export_returns_user_transaction_logs()
    {
        $liquid = $this->user->wallets()->where('group_type', 'Liquid')->orderBy('id')->firstOrFail();
        $hutang = $this->user->wallets()->where('group_type', 'System')->orderBy('id')->firstOrFail();

        $this->actingAs($this->user)->post(route('transactions.store'), [
            'date' => now()->format('Y-m-d'),
            'category_id' => null,
            'source_wallet_id' => $hutang->id,
            'destination_wallet_id' => $liquid->id,
            'amount' => 100000,
            'transaction_type' => 'debt',
            'debt_sub_type' => 'income',
            'subject' => 'BUDI',
        ])->assertRedirect(route('dashboard'));

        $response = $this->actingAs($this->user)->postJson(route('api.account.export'));
        $response->assertOk();
        $payload = $response->json();
        $this->assertEquals($this->user->id, $payload['user']['id']);
        $subjects = collect($payload['transactions'])->pluck('subject')->all();
        $this->assertContains('BUDI', $subjects);
        foreach ($payload['transactions'] as $row) {
            $this->assertEquals($this->user->id, $row['user_id']);
        }
    }

    public function test_export_unauthenticated_is_rejected()
    {
        $this->postJson(route('api.account.export'))->assertStatus(401);
    }

    public function test_export_excludes_other_users_data()
    {
        $other = User::factory()->create();

        $response = $this->actingAs($this->user)->postJson(route('api.account.export'));
        $response->assertOk();
        foreach ($response->json('transactions') as $row) {
            $this->assertEquals($this->user->id, $row['user_id']);
        }
        $this->assertNotEquals($other->id, $response->json('user')['id']);
    }
}
