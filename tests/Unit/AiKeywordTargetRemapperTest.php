<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AI\Memory\AiKeywordTargetRemapper;
use Tests\TestCase;

class AiKeywordTargetRemapperTest extends TestCase
{
    private AiKeywordTargetRemapper $remapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->remapper = new AiKeywordTargetRemapper;
    }

    public function test_draft_keyword_category_diarahkan_ke_kategori_final(): void
    {
        $result = $this->remapper->remapForDraft(
            keywords: [
                ['keyword' => 'Bakso', 'target_type' => 'category', 'target_id' => 1, 'target_name' => 'Makan'],
            ],
            payload: ['category_id' => 1, 'source_wallet_id' => 10, 'destination_wallet_id' => 20],
            finalCategoryId: 2,
            finalCategoryName: 'Transport',
        );

        $this->assertCount(1, $result);
        $this->assertSame('bakso', $result[0]['keyword']);
        $this->assertSame('category', $result[0]['target_type']);
        $this->assertSame(2, $result[0]['target_id']);
        $this->assertSame('Transport', $result[0]['target_name']);
    }

    public function test_draft_keyword_wallet_mengikuti_sisi_final(): void
    {
        $result = $this->remapper->remapForDraft(
            keywords: [
                ['keyword' => 'cash', 'target_type' => 'wallet', 'target_id' => 10, 'target_name' => 'Dompet Cash'],
                ['keyword' => 'gopay', 'target_type' => 'wallet', 'target_id' => 20, 'target_name' => 'GoPay'],
            ],
            payload: ['source_wallet_id' => 10, 'destination_wallet_id' => 20],
            finalCategoryId: null,
            finalCategoryName: null,
            finalSourceWalletId: 11,
            finalSourceWalletName: 'Dompet Tunai',
            finalDestinationWalletId: 21,
            finalDestinationWalletName: 'OVO',
        );

        $this->assertCount(2, $result);
        $this->assertSame(11, $result[0]['target_id']);
        $this->assertSame('Dompet Tunai', $result[0]['target_name']);
        $this->assertSame(21, $result[1]['target_id']);
        $this->assertSame('OVO', $result[1]['target_name']);
    }

    public function test_wallet_sama_untuk_source_dan_destination_dianggap_source(): void
    {
        $result = $this->remapper->remapForDraft(
            keywords: [
                ['keyword' => 'cash', 'target_type' => 'wallet', 'target_id' => 10],
            ],
            payload: ['source_wallet_id' => 10, 'destination_wallet_id' => 10],
            finalCategoryId: null,
            finalCategoryName: null,
            finalSourceWalletId: 11,
            finalSourceWalletName: 'Baru',
            finalDestinationWalletId: 21,
            finalDestinationWalletName: 'Lain',
        );

        $this->assertCount(1, $result);
        $this->assertSame(11, $result[0]['target_id']);
    }

    public function test_wallet_stale_target_dibuang(): void
    {
        $result = $this->remapper->remapForDraft(
            keywords: [
                ['keyword' => 'usang', 'target_type' => 'wallet', 'target_id' => 99, 'target_name' => 'Hilang'],
            ],
            payload: ['source_wallet_id' => 10, 'destination_wallet_id' => 20],
            finalCategoryId: null,
            finalCategoryName: null,
            finalSourceWalletId: 11,
            finalSourceWalletName: 'Baru',
        );

        $this->assertSame([], $result);
    }

    public function test_kategori_dibuang_bila_tidak_ada_kategori_final(): void
    {
        $result = $this->remapper->remapForDraft(
            keywords: [
                ['keyword' => 'bakso', 'target_type' => 'category', 'target_id' => 1],
            ],
            payload: [],
            finalCategoryId: null,
            finalCategoryName: null,
        );

        $this->assertSame([], $result);
    }

    public function test_keyword_tidak_valid_dibuang(): void
    {
        $result = $this->remapper->remapForDraft(
            keywords: [
                ['keyword' => 'a', 'target_type' => 'category', 'target_id' => 1],
                ['keyword' => 'valid', 'target_type' => 'unknown', 'target_id' => 1],
                ['keyword' => '  ', 'target_type' => 'category', 'target_id' => 1],
            ],
            payload: [],
            finalCategoryId: 5,
            finalCategoryName: 'Makan',
        );

        $this->assertSame([], $result);
    }

    public function test_keyword_duplikat_di_dedupe(): void
    {
        $result = $this->remapper->remapForDraft(
            keywords: [
                ['keyword' => 'bakso', 'target_type' => 'category', 'target_id' => 1],
                ['keyword' => 'BAKSO', 'target_type' => 'category', 'target_id' => 2],
            ],
            payload: [],
            finalCategoryId: 5,
            finalCategoryName: 'Makan',
        );

        $this->assertCount(1, $result);
        $this->assertSame('bakso', $result[0]['keyword']);
        $this->assertSame(5, $result[0]['target_id']);
    }

    public function test_evidence_kandidat_kategori_dan_wallet_dipetakan(): void
    {
        $result = $this->remapper->remapForEvidence(
            candidates: [
                ['keyword' => 'Kopi', 'target_type' => 'category'],
                ['keyword' => 'QRIS', 'target_type' => 'wallet'],
                ['keyword' => 'x', 'target_type' => 'wallet'],
            ],
            finalCategoryId: 7,
            finalCategoryName: 'Minuman',
            userWalletId: 3,
            userWalletName: 'GoPay',
        );

        $this->assertCount(2, $result);
        $this->assertSame(['kopi', 'category', 7, 'Minuman'], [
            $result[0]['keyword'], $result[0]['target_type'], $result[0]['target_id'], $result[0]['target_name'],
        ]);
        $this->assertSame(['qris', 'wallet', 3, 'GoPay'], [
            $result[1]['keyword'], $result[1]['target_type'], $result[1]['target_id'], $result[1]['target_name'],
        ]);
    }

    public function test_evidence_tanpa_target_final_dibuang(): void
    {
        $result = $this->remapper->remapForEvidence(
            candidates: [
                ['keyword' => 'kopi', 'target_type' => 'category'],
                ['keyword' => 'qris', 'target_type' => 'wallet'],
            ],
            finalCategoryId: null,
            finalCategoryName: null,
            userWalletId: null,
            userWalletName: null,
        );

        $this->assertSame([], $result);
    }
}
