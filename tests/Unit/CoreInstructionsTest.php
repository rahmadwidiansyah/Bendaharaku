<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AI\Assistant\CoreInstructions;
use Tests\TestCase;

class CoreInstructionsTest extends TestCase
{
    /** @test */
    public function test_system_prompt_memuat_konteks_waktu_saat_ini(): void
    {
        $prompt = CoreInstructions::systemPrompt('Ken-Chan', 'Asia/Jakarta');

        $this->assertStringContainsString('Hari ini: '.now('Asia/Jakarta')->format('Y-m-d'), $prompt);
        $this->assertStringContainsString('zona waktu Asia/Jakarta', $prompt);
        $this->assertStringContainsString('"bulan ini" = '.now('Asia/Jakarta')->format('Y-m'), $prompt);
        $this->assertStringContainsString('"bulan lalu" = '.now('Asia/Jakarta')->subMonth()->format('Y-m'), $prompt);
    }

    /** @test */
    public function test_system_prompt_tanpa_timezone_memakai_timezone_app(): void
    {
        $prompt = CoreInstructions::systemPrompt('Ken-Chan');

        $this->assertStringContainsString(
            'zona waktu '.config('app.timezone', 'UTC'),
            $prompt,
        );
    }

    /** @test */
    public function test_system_prompt_mempertahankan_aturan_inti(): void
    {
        $prompt = CoreInstructions::systemPrompt('Ken-Chan', 'Asia/Jakarta');

        $this->assertStringContainsString('JANGAN PERNAH mengarang angka', $prompt);
        $this->assertStringContainsString('transfer_count', $prompt);
        $this->assertStringContainsString('WAJIB isi parameter period', $prompt);
    }
}
