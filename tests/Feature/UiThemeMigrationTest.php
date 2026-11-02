<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiThemeMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_defaults_to_m3_ui_theme()
    {
        $user = User::factory()->create();

        // Sejak migrasi 2026_10_31: default kolom users.ui_theme = 'm3'.
        $this->assertSame('m3', $user->fresh()->ui_theme);
    }

    public function test_ui_theme_is_mass_assignable()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->assertSame('m3', $user->fresh()->ui_theme);
    }

    public function test_only_default_and_m3_are_valid_values()
    {
        $this->assertContains('ui_theme', (new User)->getFillable());

        $user = User::factory()->create();
        $user->update(['ui_theme' => 'm3']);

        $this->assertSame('m3', $user->fresh()->ui_theme);
    }
}
