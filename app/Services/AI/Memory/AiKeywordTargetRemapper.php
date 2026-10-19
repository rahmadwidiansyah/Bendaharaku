<?php

declare(strict_types=1);

namespace App\Services\AI\Memory;

/**
 * Memetakan ulang keyword hasil LLM ke target FINAL (kategori/wallet) saat
 * transaksi benar-benar disimpan. Ini mencegah memory menyimpan mapping usang
 * ketika user mengubah kategori/wallet di form konfirmasi draft.
 *
 * Kebijakan: koreksi user = ground truth — keyword diarahkan ke pilihan final.
 * Entry yang targetnya tidak bisa dipetakan dibuang (tidak menebak).
 */
class AiKeywordTargetRemapper
{
    private const VALID_TYPES = ['category', 'wallet'];

    /**
     * Remap aiKeywords dari payload draft chat ke data final yang divalidasi.
     *
     * @param  list<array{keyword?:string,target_type?:string,target_id?:int|string|null,target_name?:string|null}>  $keywords
     * @param  array<string, mixed>  $payload  Payload draft saat parse (untuk deteksi sisi wallet lama)
     * @return list<array{keyword:string,target_type:string,target_id:int,target_name:?string}>
     */
    public function remapForDraft(
        array $keywords,
        array $payload,
        ?int $finalCategoryId,
        ?string $finalCategoryName,
        ?int $finalSourceWalletId = null,
        ?string $finalSourceWalletName = null,
        ?int $finalDestinationWalletId = null,
        ?string $finalDestinationWalletName = null,
    ): array {
        $oldSourceId = (int) ($payload['source_wallet_id'] ?? 0);
        $oldDestinationId = (int) ($payload['destination_wallet_id'] ?? 0);

        $remapped = [];
        foreach ($keywords as $entry) {
            $keyword = mb_strtolower(trim((string) ($entry['keyword'] ?? '')));
            $type = (string) ($entry['target_type'] ?? '');

            if (! $this->isValid($keyword, $type)) {
                continue;
            }

            if ($type === 'category') {
                if ($finalCategoryId === null || $finalCategoryId <= 0) {
                    continue;
                }

                $this->push($remapped, $keyword, 'category', $finalCategoryId, $finalCategoryName);

                continue;
            }

            $oldTargetId = (int) ($entry['target_id'] ?? 0);
            if ($oldTargetId <= 0 || $oldTargetId !== $oldSourceId && $oldTargetId !== $oldDestinationId) {
                continue;
            }

            $isDestination = $oldTargetId === $oldDestinationId && $oldDestinationId !== $oldSourceId;
            $finalId = $isDestination ? $finalDestinationWalletId : $finalSourceWalletId;
            $finalName = $isDestination ? $finalDestinationWalletName : $finalSourceWalletName;

            if ($finalId === null || $finalId <= 0) {
                continue;
            }

            $this->push($remapped, $keyword, 'wallet', $finalId, $finalName);
        }

        return $remapped;
    }

    /**
     * Bangun aiKeywords dari memory_candidates LLM (alur evidence/OCR) ke data final.
     * Wallet candidate selalu diarahkan ke wallet user yang dipakai transaksi.
     *
     * @param  list<array{keyword?:string,target_type?:string,target_name?:string|null}>  $candidates
     * @return list<array{keyword:string,target_type:string,target_id:int,target_name:?string}>
     */
    public function remapForEvidence(
        array $candidates,
        ?int $finalCategoryId,
        ?string $finalCategoryName,
        ?int $userWalletId,
        ?string $userWalletName,
    ): array {
        $remapped = [];
        foreach ($candidates as $candidate) {
            $keyword = mb_strtolower(trim((string) ($candidate['keyword'] ?? '')));
            $type = (string) ($candidate['target_type'] ?? '');

            if (! $this->isValid($keyword, $type)) {
                continue;
            }

            if ($type === 'category') {
                if ($finalCategoryId === null || $finalCategoryId <= 0) {
                    continue;
                }

                $this->push($remapped, $keyword, 'category', $finalCategoryId, $finalCategoryName);

                continue;
            }

            if ($userWalletId === null || $userWalletId <= 0) {
                continue;
            }

            $this->push($remapped, $keyword, 'wallet', $userWalletId, $userWalletName);
        }

        return $remapped;
    }

    private function isValid(string $keyword, string $type): bool
    {
        return mb_strlen($keyword) >= 2 && in_array($type, self::VALID_TYPES, true);
    }

    /**
     * @param  list<array{keyword:string,target_type:string,target_id:int,target_name:?string}>  $remapped
     */
    private function push(array &$remapped, string $keyword, string $type, int $targetId, ?string $targetName): void
    {
        $key = $type.'|'.$keyword;
        foreach ($remapped as $existing) {
            if ($key === $existing['target_type'].'|'.$existing['keyword']) {
                return;
            }
        }

        $remapped[] = [
            'keyword' => $keyword,
            'target_type' => $type,
            'target_id' => $targetId,
            'target_name' => $targetName,
        ];
    }
}
