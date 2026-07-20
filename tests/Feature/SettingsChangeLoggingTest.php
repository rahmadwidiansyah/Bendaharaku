<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class SettingsChangeLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_change_logs_entry_and_recent_changes_api()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/settings/locale', ['locale' => 'en'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_settings_changes', [
            'user_id' => $user->id,
            'setting_key' => 'locale',
            'setting_page' => 'settings.application.language',
            'new_value' => 'en',
        ]);

        $resp = $this->actingAs($user)->getJson('/settings/recent-changes');
        $resp->assertStatus(200);
        $data = $resp->json();
        $this->assertIsArray($data);
        $this->assertStringContainsString('locale', collect($data)->pluck('setting_key')->join(','));
    }

    public function test_bot_profile_update_logs_entry()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patch('/settings/chat/bot-profile', ['bot_name' => 'KenTest']);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Debug: direct logger call to ensure logger works in test environment
        \App\Support\SettingsChangeLogger::logChange($user, 'test_direct', 'settings.test', null, 'ok');
        $this->assertDatabaseHas('user_settings_changes', [
            'user_id' => $user->id,
            'setting_key' => 'test_direct',
        ]);

        // Record redirect location from response so we can inspect where request went
        $location = $response->headers->get('Location');
        \App\Support\SettingsChangeLogger::logChange($user, 'test_loc', 'settings.test', null, $location);

        // At least one change related to bot-profile should be recorded
        $this->assertDatabaseHas('user_settings_changes', [
            'user_id' => $user->id,
            'setting_page' => 'settings.chat.bot-profile',
        ]);
    }

    public function test_wallet_creation_logs_and_recent_changes_api()
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Test Wallet',
            'balance' => 10000,
            'group_type' => 'Personal',
        ];

        $this->actingAs($user)
            ->post('/wallets', $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('user_settings_changes', [
            'user_id' => $user->id,
            'setting_key' => 'wallet_created',
            'setting_page' => 'settings.finance.wallets',
        ]);

        $resp = $this->actingAs($user)->getJson('/settings/recent-changes');
        $resp->assertStatus(200);
        $resp->assertJsonFragment(['setting_key' => 'wallet_created']);
    }
}
