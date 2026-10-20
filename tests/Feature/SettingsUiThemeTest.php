<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SettingsUiThemeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Halaman Settings yang dirender berbeda per tema (Default vs Material).
     *
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    private function pages(): array
    {
        return [
            '/settings' => ['Index', []],
            '/settings/account/profile' => ['Account/Profile', ['user', 'status']],
            '/settings/account/security' => ['Account/Security', ['currentSession', 'otherSessions']],
            '/settings/account/preferences' => ['Account/Preferences', ['userTimezone', 'userDateFormat', 'userLanguage']],
            '/settings/application/appearance' => ['Application/Appearance', ['userAccentColor', 'userTheme', 'categoryIconColored', 'userUiTheme', 'userM3ColorScheme']],
            '/settings/application/notifications' => ['Application/Notifications', ['emailNotifications', 'pushNotifications', 'vapidPublicKey']],
            '/settings/finance/logic' => ['Finance/Defaults', []],
            '/settings/finance/budget' => ['Finance/Budget', []],
            '/settings/privacy/settings' => ['Privacy/Settings', []],
            '/settings/privacy/data' => ['Privacy/Data', []],
            '/settings/privacy/logs' => ['Privacy/Logs', ['logs', 'pagination', 'filters', 'stats']],
            '/settings/privacy/danger' => ['Privacy/Danger', []],
            '/settings/system/about' => ['System/About', ['appVersion']],
            '/settings/ai/memory' => ['AI/Memory', []],
            '/settings/ai/memory/manage' => ['AI/MemoryManage', ['memories', 'filters']],
            '/settings/ai/integrations' => ['AI/Integration', []],
            '/settings/ai/integration' => ['AI/Integration', []],
            '/settings/ai/bot' => ['AI/Bot', ['botName', 'botAvatar']],
        ];
    }

    public function test_settings_pages_follow_ui_theme(): void
    {
        foreach (['default' => '', 'm3' => 'Material/'] as $theme => $prefix) {
            foreach ($this->pages() as $path => [$component, $props]) {
                $user = User::factory()->create(['ui_theme' => $theme]);

                $this->actingAs($user)
                    ->get($path)
                    ->assertOk()
                    ->assertInertia(fn (AssertableInertia $page) => $page
                        ->component("{$prefix}Settings/{$component}")
                        ->where('auth.user.ui_theme', $theme)
                        ->hasAll($props));
            }
        }
    }
}
