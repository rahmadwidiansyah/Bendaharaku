<?php

namespace Tests\Feature\Chat;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TelegramWebhookSecretTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_rejects_wrong_secret_with_403()
    {
        Config::set('services.telegram.webhook_secret', 's3cr3t-nyata');

        $this->postJson('/api/telegram/webhook', ['update_id' => 1], ['X-Telegram-Bot-Api-Secret-Token' => 'salah'])
            ->assertStatus(403)
            ->assertJson(['status' => 'forbidden']);
    }

    public function test_rejects_missing_secret_header_with_403()
    {
        Config::set('services.telegram.webhook_secret', 's3cr3t-nyata');

        $this->postJson('/api/telegram/webhook', ['update_id' => 1])
            ->assertStatus(403);
    }

    public function test_accepts_correct_secret()
    {
        Config::set('services.telegram.webhook_secret', 's3cr3t-nyata');

        // Payload tanpa message.text → adapter mengabaikan tanpa DB/API eksternal.
        $this->postJson('/api/telegram/webhook', ['update_id' => 7], ['X-Telegram-Bot-Api-Secret-Token' => 's3cr3t-nyata'])
            ->assertOk()
            ->assertJson(['status' => 'ignored']);
    }

    public function test_allows_through_when_secret_unconfigured()
    {
        Config::set('services.telegram.webhook_secret', '');

        $this->postJson('/api/telegram/webhook', ['update_id' => 7])
            ->assertOk()
            ->assertJson(['status' => 'ignored']);
    }
}
