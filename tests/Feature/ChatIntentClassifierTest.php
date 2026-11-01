<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\AI\AiProviderFactory;
use App\Services\AI\Assistant\ChatIntentClassifier;
use App\Services\AI\Routing\AiRoutingExecutor;
use Tests\TestCase;

class ChatIntentClassifierTest extends TestCase
{
    private function classifier(?AiRoutingExecutor $executor = null): ChatIntentClassifier
    {
        return new ChatIntentClassifier(
            $executor ?? \Mockery::mock(AiRoutingExecutor::class),
            \Mockery::mock(AiProviderFactory::class),
        );
    }

    /** @test */
    public function test_nominal_dan_kata_kerja_jadi_transaction(): void
    {
        $result = $this->classifier()->classify(new User, 'Beli kopi 25rb');

        $this->assertSame('transaction', $result['intent']);
        $this->assertSame('heuristic', $result['source']);
        $this->assertGreaterThanOrEqual(0.9, $result['confidence']);
    }

    /** @test */
    public function test_pertanyaan_keuangan_jadi_question(): void
    {
        $result = $this->classifier()->classify(new User, 'Berapa saldo dompet saya?');

        $this->assertSame('question', $result['intent']);
        $this->assertSame('heuristic', $result['source']);
    }

    /** @test */
    public function test_sapaan_jadi_conversation(): void
    {
        $result = $this->classifier()->classify(new User, 'Halo, apa kabar?');

        $this->assertSame('conversation', $result['intent']);
        $this->assertSame('heuristic', $result['source']);
    }

    /** @test */
    public function test_pesan_ambigu_tanpa_llm_tetap_conversation(): void
    {
        config()->set('bendaharaku.ai.assistant.intent_llm', false);

        $result = $this->classifier()->classify(new User, 'Aku mau cerita panjang lebar soal hari ini');

        $this->assertSame('conversation', $result['intent']);
        $this->assertSame('heuristic', $result['source']);
    }

    /** @test */
    public function test_llm_gagal_memakai_heuristik(): void
    {
        config()->set('bendaharaku.ai.assistant.intent_llm', true);

        $executor = \Mockery::mock(AiRoutingExecutor::class);
        $executor->shouldReceive('executeText')->once()->andThrow(new \RuntimeException('timeout'));

        $result = $this->classifier($executor)->classify(new User, 'Aku mau cerita panjang lebar soal hari ini');

        $this->assertSame('conversation', $result['intent']);
        $this->assertSame('heuristic', $result['source']);
    }

    /** @test */
    public function test_output_llm_diparse_dan_dipakai(): void
    {
        config()->set('bendaharaku.ai.assistant.intent_llm', true);

        $executor = \Mockery::mock(AiRoutingExecutor::class);
        $executor->shouldReceive('executeText')->once()->andReturn([
            'text' => '{"intent":"transaction","confidence":0.91}',
            'routing' => [],
        ]);

        $result = $this->classifier($executor)->classify(new User, 'Aku mau cerita panjang lebar soal hari ini');

        $this->assertSame('transaction', $result['intent']);
        $this->assertSame('llm', $result['source']);
        $this->assertSame(0.91, $result['confidence']);
    }
}
