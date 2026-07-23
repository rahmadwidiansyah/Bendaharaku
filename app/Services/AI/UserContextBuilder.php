<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\TransactionLog;
use App\Models\User;
use App\Support\StringUtils;

/**
 * UserContextBuilder
 *
 * Menyusun context lengkap user untuk dikirim ke LLM.
 * Berisi: wallet, kategori, keyword alias, merchant, preferensi.
 *
 * Prinsip:
 * - Tidak ada side effect — hanya baca data, return array
 * - Context disusun fresh setiap request (data selalu up-to-date)
 * - Token-efficient: hanya kirim data relevan, bukan seluruh histori
 */
class UserContextBuilder
{
    /**
     * Bangun context lengkap untuk satu user.
     *
     * @return array{
     *   wallets: array,
     *   categories: array,
     *   wallet_keywords: array<string, string>,
     *   category_keywords: array<string, string>,
     *   recent_merchants: array,
     *   locale: string,
     *   timezone: string,
     * }
     */
    public function build(User $user): array
    {
        $wallets = $user->wallets()
            ->where('group_type', '!=', 'System')
            ->orderByDesc('is_pinned')
            ->orderBy('name')
            ->get(['id', 'name', 'keyword', 'is_pinned', 'balance'])
            ->toArray();

        $categories = $user->categories()
            ->get(['id', 'category_name', 'type_id', 'keyword'])
            ->toArray();

        // Bangun flat keyword → nama map untuk quick alias lookup
        $walletKeywords = $this->buildKeywordMap($wallets, 'name');
        $categoryKeywords = $this->buildKeywordMap($categories, 'category_name');

        // Merchant dari notes transaksi terbaru (unik, max 20, token-efficient)
        $recentMerchants = TransactionLog::where('user_id', $user->id)
            ->whereNotNull('notes')
            ->where('notes', '!=', '')
            ->where('notes', '!=', '-')
            ->orderByDesc('created_at')
            ->limit(50)
            ->pluck('notes')
            ->unique()
            ->take(20)
            ->values()
            ->toArray();

        return [
            // Daftar wallet user (non-System) dengan keyword dan saldo-nya
            'wallets' => array_map(fn ($w) => [
                'id' => $w['id'],
                'name' => $w['name'],
                'balance' => (float) ($w['balance'] ?? 0),
                'keywords' => $this->parseKeywords($w['keyword'] ?? ''),
            ], $wallets),

            // Daftar kategori user dengan keyword-nya
            'categories' => array_map(fn ($c) => [
                'id' => $c['id'],
                'name' => $c['category_name'],
                'keywords' => $this->parseKeywords($c['keyword'] ?? ''),
            ], $categories),

            // Flat alias map: 'spay' => 'ShopeePay', 'dana' => 'Dana', dst.
            'wallet_keywords' => $walletKeywords,
            'category_keywords' => $categoryKeywords,

            // Merchant yang pernah dipakai (untuk referensi AI)
            'recent_merchants' => $recentMerchants,

            // Preferensi user
            'locale' => $user->locale ?? 'id',
            'timezone' => $user->timezone ?? 'Asia/Jakarta',
        ];
    }

    /**
     * Build flat keyword → name map.
     * Contoh output: ['spay' => 'ShopeePay', 'shopee' => 'ShopeePay', 'dana' => 'Dana']
     */
    private function buildKeywordMap(array $items, string $nameField): array
    {
        $map = [];
        foreach ($items as $item) {
            $name = $item[$nameField] ?? '';
            if (empty($name)) {
                continue;
            }

            // Nama itu sendiri sebagai keyword (lowercase)
            $map[strtolower(trim($name))] = $name;

            // Keyword tambahan dari field keyword
            foreach ($this->parseKeywords($item['keyword'] ?? '') as $kw) {
                if (! empty($kw)) {
                    $map[strtolower(trim($kw))] = $name;
                }
            }
        }

        return $map;
    }

    /**
     * Parse keyword string multi-delimiter menjadi array bersih.
     * 'spay,shopee;s-pay|sp' → ['spay', 'shopee', 's-pay', 'sp']
     */
    private function parseKeywords(string $raw): array
    {
        return StringUtils::splitKeywords($raw);
    }
}
