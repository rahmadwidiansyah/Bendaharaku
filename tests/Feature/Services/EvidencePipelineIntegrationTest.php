<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\DocumentType;
use App\Enums\EvidenceStatus;
use App\Evidence\DTO\EvidenceData;
use App\Evidence\DTO\TransactionDraft;
use App\Evidence\Jobs\ProcessEvidenceJob;
use App\Models\Evidence;
use App\Models\User;
use App\Services\Evidence\EvidencePipelineService;
use App\Services\Evidence\ProcessingLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EvidencePipelineIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * Test pipeline dari UPLOAD sampai COMPLETED untuk transfer receipt.
     */
    public function test_full_pipeline_transfer_receipt(): void
    {
        // Arrange
        $evidence = Evidence::factory()->create([
            'user_id' => $this->user->id,
            'status' => EvidenceStatus::Uploaded,
            'ocr_text' => 'Transfer Rp 500.000 ke BCA 1234567890 Ref: TXN20260715001',
        ]);

        $pipeline = app(EvidencePipelineService::class);
        $processingLog = app(ProcessingLogService::class);

        // Act - jalankan pipeline secara manual
        $pipeline->queue($evidence);
        $evidence->refresh();
        $this->assertEquals(EvidenceStatus::Queued, $evidence->status);

        $pipeline->startProcessing($evidence);
        $evidence->refresh();
        $this->assertEquals(EvidenceStatus::Processing, $evidence->status);

        $pipeline->ocrCompleted($evidence, 'Transfer Rp 500.000 ke BCA 1234567890 Ref: TXN20260715001', 'PaddleOCR', 1500);
        $evidence->refresh();
        $this->assertEquals(EvidenceStatus::OcrCompleted, $evidence->status);
        $this->assertEquals('PaddleOCR', $evidence->ocr_engine);

        $pipeline->classified($evidence, DocumentType::TransferReceipt, 0.95, 'RuleBased');
        $evidence->refresh();
        $this->assertEquals(EvidenceStatus::Classified, $evidence->status);
        $this->assertEquals(DocumentType::TransferReceipt, $evidence->document_type);

        $parsedData = new EvidenceData(
            documentType: DocumentType::TransferReceipt,
            rawText: 'Transfer BCA ke rekening tujuan Rp500.000',
            amount: 500000.0,
            walletName: 'BCA',
            referenceNumber: 'TXN20260715001',
            date: now()->toDateString(),
            destinationAccount: '1234567890',
            confidence: 0.9,
        );

        $pipeline->parsed($evidence, $parsedData, 'TransferReceiptParser');
        $evidence->refresh();
        $this->assertEquals(EvidenceStatus::Parsed, $evidence->status);

        $draft = new TransactionDraft(
            transactionType: 'EXPENSE',
            walletId: 1,
            categoryId: 1,
            amount: 500000.0,
            description: 'Transfer',
            transactionDate: now()->toDateString(),
            confidence: 0.85,
            resolved: true,
        );

        $pipeline->resolved($evidence, $draft, 'EvidenceResolver');
        $evidence->refresh();
        $this->assertEquals(EvidenceStatus::Resolved, $evidence->status);

        $pipeline->complete($evidence);
        $evidence->refresh();
        $this->assertEquals(EvidenceStatus::Ready, $evidence->status);

        // Assert - cek processing logs
        $timeline = $processingLog->getTimeline($evidence);
        $this->assertCount(7, $timeline);
        $this->assertEquals('QUEUE', $timeline[0]['stage']);
        $this->assertEquals('PROCESS', $timeline[1]['stage']);
        $this->assertEquals('OCR', $timeline[2]['stage']);
        $this->assertEquals('CLASSIFY', $timeline[3]['stage']);
        $this->assertEquals('PARSE', $timeline[4]['stage']);
        $this->assertEquals('RESOLVE', $timeline[5]['stage']);
        $this->assertEquals('COMPLETE', $timeline[6]['stage']);
    }

    /**
     * Test pipeline resume setelah stage tertentu.
     */
    public function test_pipeline_resume_from_ocr_stage(): void
    {
        // Arrange - evidence sudah selesai OCR
        $evidence = Evidence::factory()->create([
            'user_id' => $this->user->id,
            'status' => EvidenceStatus::OcrCompleted,
            'ocr_text' => 'Transfer Rp 500.000',
            'ocr_engine' => 'PaddleOCR',
            'ocr_duration_ms' => 1500,
        ]);

        $pipeline = app(EvidencePipelineService::class);

        // Log stage yang sudah selesai
        $pipeline->queue($evidence);
        $pipeline->startProcessing($evidence);
        $pipeline->ocrCompleted($evidence, 'Transfer Rp 500.000', 'PaddleOCR', 1500);

        // Act - resume dari stage CLASSIFY
        $processingLog = app(ProcessingLogService::class);
        $lastStage = $processingLog->getLastSuccessfulStage($evidence);

        $this->assertEquals('OCR', $lastStage);

        // Verify bisa melanjutkan dari CLASSIFY
        $pipeline->classified($evidence, DocumentType::TransferReceipt, 0.95, 'RuleBased');
        $evidence->refresh();
        $this->assertEquals(EvidenceStatus::Classified, $evidence->status);
    }

    /**
     * Test fail dengan stage info.
     */
    public function test_fail_records_stage(): void
    {
        // Arrange
        $evidence = Evidence::factory()->create([
            'user_id' => $this->user->id,
            'status' => EvidenceStatus::Processing,
        ]);

        $pipeline = app(EvidencePipelineService::class);

        // Act
        $pipeline->fail($evidence, 'OCR service timeout', 'OCR');

        // Assert
        $evidence->refresh();
        $this->assertEquals(EvidenceStatus::Failed, $evidence->status);
        $this->assertEquals('OCR service timeout', $evidence->error_message);

        $processingLog = app(ProcessingLogService::class);
        $timeline = $processingLog->getTimeline($evidence);
        $this->assertCount(1, $timeline);
        $this->assertEquals('OCR', $timeline[0]['stage']);
        $this->assertEquals('FAILED', $timeline[0]['status']);
        $this->assertEquals('OCR service timeout', $timeline[0]['message']);
    }

    /**
     * Test retry count increment.
     */
    public function test_retry_increments_count(): void
    {
        // Arrange
        $evidence = Evidence::factory()->create([
            'user_id' => $this->user->id,
            'status' => EvidenceStatus::Failed,
            'retry_count' => 1,
        ]);

        $pipeline = app(EvidencePipelineService::class);

        // Act
        $pipeline->retry($evidence);

        // Assert
        $evidence->refresh();
        $this->assertEquals(EvidenceStatus::Queued, $evidence->status);
        $this->assertEquals(2, $evidence->retry_count);
        $this->assertNull($evidence->error_message);
    }

    /**
     * Test ProcessEvidenceJob dispatches correctly.
     */
    public function test_process_evidence_job_dispatches(): void
    {
        Queue::fake();

        // Arrange
        $evidence = Evidence::factory()->create([
            'user_id' => $this->user->id,
            'status' => EvidenceStatus::Queued,
        ]);

        // Act
        ProcessEvidenceJob::dispatch($evidence->id);

        // Assert
        Queue::assertPushed(ProcessEvidenceJob::class, function ($job) use ($evidence) {
            return $job->evidenceId === $evidence->id;
        });
    }

    /**
     * Test metrics calculation.
     */
    public function test_metrics_calculation(): void
    {
        // Arrange - buat beberapa evidence dengan status berbeda
        Evidence::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => EvidenceStatus::Completed,
            'ocr_duration_ms' => 1500,
            'resolver_confidence' => 0.85,
        ]);

        Evidence::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => EvidenceStatus::Failed,
        ]);

        // Act
        $processingLog = app(ProcessingLogService::class);
        $metrics = $processingLog->getMetrics(24);

        // Assert
        $this->assertEquals(5, $metrics['total_evidence']);
        $this->assertEquals(3, $metrics['completed']);
        $this->assertEquals(2, $metrics['failed']);
        $this->assertEquals(60.0, $metrics['success_rate']);
        $this->assertEquals(40.0, $metrics['failure_rate']);
        $this->assertEquals(1500, $metrics['avg_ocr_duration_ms']);
        $this->assertEqualsWithDelta(0.85, $metrics['avg_confidence'], 0.01);
    }

    /**
     * Test health status returns healthy when services OK.
     */
    public function test_health_status_healthy(): void
    {
        $processingLog = app(ProcessingLogService::class);
        $health = $processingLog->getHealthStatus();

        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('checks', $health);
        $this->assertArrayHasKey('timestamp', $health);
        $this->assertContains($health['status'], ['healthy', 'warning', 'error']);
    }
}
