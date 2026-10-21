<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_error_page_uses_default_theme_tokens(): void
    {
        $response = $this->get('/halaman-tidak-ada');

        $response->assertNotFound()
            ->assertSee('err-page', false)
            ->assertSee('err-card', false)
            ->assertSee('Halaman tidak ditemukan')
            ->assertDontSee('— Bendaharaku', false)
            ->assertDontSee('ui-m3', false)
            ->assertDontSee('cdn.tailwindcss.com', false)
            ->assertDontSee('#1f2937', false);
    }

    public function test_m3_error_page_includes_ui_theme_and_color_scheme(): void
    {
        $user = User::factory()->create([
            'ui_theme' => 'm3',
            'ui_m3_color_scheme' => 'ocean',
            'theme' => 'dark',
        ]);

        $response = $this->actingAs($user)->get('/halaman-tidak-ada');

        $response->assertNotFound()
            ->assertSee('class="ui-m3"', false)
            ->assertSee('data-m3-scheme="ocean"', false)
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('var preference = "dark";', false)
            ->assertDontSee('cdn.tailwindcss.com', false);
    }

    public function test_error_page_boot_script_respects_light_preference(): void
    {
        $user = User::factory()->create([
            'ui_theme' => 'm3',
            'ui_m3_color_scheme' => 'rose',
            'theme' => 'light',
        ]);

        $this->actingAs($user)
            ->get('/halaman-tidak-ada')
            ->assertNotFound()
            ->assertSee('data-m3-scheme="rose"', false)
            ->assertSee('var preference = "light";', false)
            ->assertSee("classList.add('light')", false);
    }

    public function test_every_supported_error_status_renders_the_shared_layout(): void
    {
        Route::middleware('web')->get('/__errors/{code}', fn (int $code) => abort($code));

        foreach ([401, 402, 403, 404, 419, 429, 500, 503] as $code) {
            $this->get("/__errors/{$code}")
                ->assertStatus($code)
                ->assertSee('err-card', false)
                ->assertSee("Error {$code}", false)
                ->assertSee('Kembali')
                ->assertSee('Halaman Utama');
        }
    }

    public function test_unknown_4xx_and_5xx_statuses_use_fallback_pages(): void
    {
        Route::middleware('web')->get('/__errors/{code}', fn (int $code) => abort($code));

        $this->get('/__errors/418')
            ->assertStatus(418)
            ->assertSee('Permintaan tidak dapat diproses')
            ->assertSee('Error 418', false);

        $this->get('/__errors/507')
            ->assertStatus(507)
            ->assertSee('Terjadi kesalahan pada server')
            ->assertSee('Error 507', false);
    }

    public function test_403_page_uses_exception_message_when_available(): void
    {
        Route::middleware('web')->get('/__errors/forbidden', fn () => abort(403, 'Pesan khusus untuk pengujian'));

        $this->get('/__errors/forbidden')
            ->assertForbidden()
            ->assertSee('Pesan khusus untuk pengujian')
            ->assertSee('Akses dibatasi');
    }
}
