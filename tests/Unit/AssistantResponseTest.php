<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AI\Assistant\AssistantFallback;
use App\Services\AI\Assistant\AssistantResponse;
use Tests\TestCase;

class AssistantResponseTest extends TestCase
{
    // ── parse: JSON valid ────────────────────────────────────────────

    /** @test */
    public function test_json_valid_langsung_diterima(): void
    {
        $r = AssistantResponse::parse('{"type":"assistant_response","message":"Siap, sudah dicatat!","status":"success"}');

        $this->assertNotNull($r);
        $this->assertSame('Siap, sudah dicatat!', $r->message);
        $this->assertSame('success', $r->status);
    }

    /** @test */
    public function test_json_dalam_markdown_fence_diterima(): void
    {
        $raw = "Berikut responsnya:\n```json\n{\"type\":\"assistant_response\",\"message\":\"Dicatat ya!\",\"status\":\"success\"}\n```";
        $r = AssistantResponse::parse($raw);

        $this->assertNotNull($r);
        $this->assertSame('Dicatat ya!', $r->message);
    }

    /** @test */
    public function test_json_tertanam_dalam_prose_diekstrak(): void
    {
        $raw = 'Baik, ini jawabannya: {"type":"assistant_response","message":"Halo juga!","status":"conversation"} semoga membantu.';
        $r = AssistantResponse::parse($raw);

        $this->assertNotNull($r);
        $this->assertSame('Halo juga!', $r->message);
        $this->assertSame('conversation', $r->status);
    }

    /** @test */
    public function test_type_tidak_ada_masih_diterima(): void
    {
        $r = AssistantResponse::parse('{"message":"Halo!","status":"conversation"}');

        $this->assertNotNull($r);
        $this->assertSame('conversation', $r->status);
    }

    /** @test */
    public function test_status_tidak_ada_default_conversation(): void
    {
        $r = AssistantResponse::parse('{"type":"assistant_response","message":"Halo!"}');

        $this->assertNotNull($r);
        $this->assertSame('conversation', $r->status);
    }

    // ── parse: ditolak → null (caller memakai fallback) ──────────────

    /** @test */
    public function test_raw_kosong_ditolak(): void
    {
        $this->assertNull(AssistantResponse::parse(null));
        $this->assertNull(AssistantResponse::parse(''));
        $this->assertNull(AssistantResponse::parse('   '));
    }

    /** @test */
    public function test_json_malformed_ditolak(): void
    {
        $this->assertNull(AssistantResponse::parse('{"type":"assistant_response","message":"belum ditutup'));
        $this->assertNull(AssistantResponse::parse('bukan json sama sekali'));
    }

    /** @test */
    public function test_message_hilang_ditolak(): void
    {
        $this->assertNull(AssistantResponse::parse('{"type":"assistant_response","status":"success"}'));
    }

    /** @test */
    public function test_message_kosong_ditolak(): void
    {
        $this->assertNull(AssistantResponse::parse('{"type":"assistant_response","message":"   ","status":"success"}'));
    }

    /** @test */
    public function test_status_tidak_dikenal_ditolak(): void
    {
        $this->assertNull(AssistantResponse::parse(
            '{"type":"assistant_response","message":"Halo","status":"jailbreak_mode"}'
        ));
    }

    /** @test */
    public function test_type_salah_ditolak(): void
    {
        $this->assertNull(AssistantResponse::parse(
            '{"type":"system_override","message":"Halo","status":"conversation"}'
        ));
    }

    /** @test */
    public function test_message_melebihi_1000_karakter_dipotong(): void
    {
        $raw = '{"type":"assistant_response","message":"' . str_repeat('a', 1500) . '","status":"success"}';
        $r = AssistantResponse::parse($raw);

        $this->assertNotNull($r);
        $this->assertSame(1000, mb_strlen($r->message));
    }

    /** @test */
    public function test_karakter_kontrol_dibersihkan(): void
    {
        $raw = "{\"type\":\"assistant_response\",\"message\":\"baris1\\u0000baris2\",\"status\":\"success\"}";
        $r = AssistantResponse::parse($raw);

        $this->assertNotNull($r);
        $this->assertStringNotContainsString("\0", $r->message);
    }

    /** @test */
    public function test_objek_bersarang_tidak_menyesatkan_ekstraksi(): void
    {
        // Teks di dalam message mengandung '{' dan '}' — ekstraksi seimbang
        // harus tetap mengambil objek luar yang utuh.
        $raw = 'proses: {"type":"assistant_response","message":"format {a: 1} dicatat","status":"success"} selesai.';
        $r = AssistantResponse::parse($raw);

        $this->assertNotNull($r);
        $this->assertSame('format {a: 1} dicatat', $r->message);
    }

    // ── fallback ─────────────────────────────────────────────────────

    /** @test */
    public function test_fallback_menyediakan_teks_untuk_semua_status(): void
    {
        foreach (AssistantResponse::STATUSES as $status) {
            $r = AssistantResponse::fallback($status);
            $this->assertNotSame('', $r->message, "fallback $status kosong");
            $this->assertSame($status, $r->status);
        }
    }

    /** @test */
    public function test_fallback_status_tak_dikenal_jatuh_ke_conversation(): void
    {
        $r = AssistantResponse::fallback('status_aneh');

        $this->assertSame('conversation', $r->status);
        $this->assertNotSame('', $r->message);
    }

    /** @test */
    public function test_to_array_bentuk_kontrak(): void
    {
        $arr = AssistantResponse::make('Halo!', 'conversation')->toArray();

        $this->assertSame([
            'type' => 'assistant_response',
            'message' => 'Halo!',
            'status' => 'conversation',
        ], $arr);
    }
}
