<?php

declare(strict_types=1);

namespace App\Services\Chat\Formatters;

use App\DTO\MultiTransactionItem;
use App\DTO\MultiTransactionResult;
use App\Support\MoneyFormatter;

/**
 * Formatter khusus Telegram untuk hasil multi-transaction.
 *
 * Tanggung jawab kelas ini HANYA presentasi: mengubah MultiTransactionResult
 * menjadi string Telegram Markdown. Tidak ada business logic di sini.
 *
 * Format output:
 *
 * ✅ Berhasil memproses 4 transaksi.           ← semua sukses
 *
 * ── atau ──
 *
 * ✅ 3 berhasil · ❌ 1 gagal                    ← sebagian
 *
 * 1. ✅ Bensin Rp20.000 (Cash)
 * 2. ✅ Makan Rp10.000 (Dana)
 * 3. ❌ Kopi Rp15.000
 *    Dompet "spay" tidak ditemukan.
 * 4. ✅ Gaji Rp5.000.000 (ShopeePay)
 *
 * ── atau ──
 *
 * ❌ Semua 4 transaksi gagal diproses.         ← semua gagal
 */
class TelegramMultiTransactionFormatter
{
    /**
     * Format MultiTransactionResult menjadi string Telegram Markdown.
     */
    public function format(MultiTransactionResult $result): string
    {
        $lines = [];

        // ── Header ────────────────────────────────────────────────
        $lines[] = $this->buildHeader($result);
        $lines[] = '';

        // ── Daftar item berurutan ─────────────────────────────────
        foreach ($result->results as $item) {
            $lines[] = $this->buildItemLine($item);
        }

        // ── Footer (provider info) ─────────────────────────────────
        if ($result->hasAnySuccess()) {
            $lines[] = '';
            $lines[] = $this->buildFooter($result);
        }

        return implode("\n", $lines);
    }

    // ── Private helpers ───────────────────────────────────────────

    private function buildHeader(MultiTransactionResult $result): string
    {
        $total = $result->totalCount();
        $success = $result->successCount();
        $failed = $result->failedCount();

        if ($result->allSuccess()) {
            return "✅ *Berhasil memproses {$total} transaksi.*";
        }

        if ($result->allFailed()) {
            return "❌ *Semua {$total} transaksi gagal diproses.*";
        }

        // Sebagian sukses
        return "✅ *{$success} berhasil* · ❌ *{$failed} gagal*";
    }

    /**
     * Satu baris per item, contoh:
     *   "1. ✅ Bensin Rp20.000 (Cash)"
     *   "3. ❌ Kopi Rp15.000\n   Dompet \"spay\" tidak ditemukan."
     */
    private function buildItemLine(MultiTransactionItem $item): string
    {
        $num = $item->index;

        if ($item->isSuccess()) {
            $trx = $item->transaction;
            $amount = MoneyFormatter::rupiahCompact($trx->amount); // sudah float karena cast di model TransactionLog
            $cat = $trx->category?->category_name ?? '?';
            $wallet = $trx->sourceWallet?->name ?? $trx->destinationWallet?->name ?? '?';
            $emoji = $this->typeEmoji($trx->type?->name ?? '');

            // "1. ✅ Bensin Rp20.000 (Cash)"
            return "{$num}. ✅ {$emoji} _{$cat}_ *{$amount}* ({$wallet})";
        }

        // Gagal: dua baris
        $amount = $this->extractAmountFromRaw($item);
        $label = $item->raw ?? "Transaksi #{$num}";

        // Baris 1: nomor + ❌ + label/raw
        $line1 = "{$num}. ❌ _{$label}_";

        // Baris 2: alasan (indent 3 spasi agar terlihat sub-item)
        $line2 = "   {$item->reason}";

        return $line1."\n".$line2;
    }

    private function buildFooter(MultiTransactionResult $result): string
    {
        $providerLabel = match (strtoupper($result->provider)) {
            'GEMINI' => '✨ Gemini',
            'OPENAI' => '🤖 OpenAI',
            'DEEPSEEK' => '🔍 DeepSeek',
            'PYTHON-NLP' => '🐍 Python NLP',
            default => '🤖 '.strtoupper($result->provider),
        };

        $confidencePct = round($result->confidence * 100).'%';

        return "🧠 _{$providerLabel} · Keyakinan AI: {$confidencePct}_";
    }

    private function typeEmoji(string $typeName): string
    {
        return match (strtolower($typeName)) {
            'income' => '💰',
            'expense' => '💸',
            'transfer' => '🔄',
            'debt', 'receivable' => '🤝',
            default => '',
        };
    }

    /**
     * Coba ekstrak representasi nominal dari item jika tersedia di transaction.
     * Dipakai opsional jika ingin raw lebih kaya — saat ini tidak dipakai langsung.
     */
    private function extractAmountFromRaw(MultiTransactionItem $item): string
    {
        // raw sudah mengandung teks asli dari parsed->notes atau fallback
        return $item->raw ?? '';
    }
}
