<?php

declare(strict_types=1);

namespace App\Chat\Formatters;

use App\Chat\Contracts\ChatFormatterInterface;
use App\Chat\Components\ChatComponentInterface;
use App\Chat\Components\TextComponent;
use App\Chat\Components\DividerComponent;
use App\Chat\Components\TransactionCardComponent;
use App\Chat\Components\SummaryCardComponent;
use App\Chat\Components\ErrorComponent;
use App\Chat\Components\WarningComponent;
use App\Chat\Components\SuggestionComponent;
use App\Chat\Components\QuickReplyComponent;
use App\Chat\Components\ReportSectionComponent;
use App\Chat\DTOs\ChatResponse;
use App\Chat\DTOs\ChatContext;
use App\Chat\Errors\ErrorDetail;
use App\Enums\ChatIntent;
use App\Support\MoneyFormatter;

/**
 * Formatter Telegram — mengubah ChatResponse menjadi Telegram Markdown string.
 *
 * Satu-satunya tempat yang boleh mengandung Telegram Markdown syntax.
 * Tidak ada business logic di sini — hanya rendering.
 * Semua teks diambil dari translation files via trans($key, $params, $locale).
 *
 * Markdown yang dipakai: MarkdownV1 (bold = *text*, italic = _text_)
 * agar kompatibel dengan bot Telegram lama.
 */
class TelegramFormatter implements ChatFormatterInterface
{
    public function supports(string $platform): bool
    {
        return $platform === 'telegram';
    }

    /**
     * Format ChatResponse → string Telegram Markdown.
     */
    public function format(ChatResponse $response, ChatContext $context): string
    {
        $locale = $context->locale;
        $lines  = [];

        // Error-only response (AI gagal, konfigurasi salah, dll)
        if ($response->intent === ChatIntent::Error && $response->hasErrors()) {
            return $this->renderError($response->firstError(), $locale);
        }

        // Render tiap komponen secara berurutan
        foreach ($response->components as $component) {
            $rendered = $this->renderComponent($component, $locale);
            if ($rendered !== null) {
                $lines[] = $rendered;
            }
        }

        // Tambah error inline jika ada (misal partial multi)
        foreach ($response->errors as $error) {
            $lines[] = $this->renderError($error, $locale);
        }

        return implode("\n", array_filter($lines, fn($l) => $l !== ''));
    }

    // ── Component dispatch ────────────────────────────────────────

    private function renderComponent(ChatComponentInterface $component, string $locale): ?string
    {
        return match ($component->type()) {
            'text'             => $this->renderText($component, $locale),
            'divider'          => $this->renderDivider(),
            'transaction_card' => $this->renderTransactionCard($component, $locale),
            'summary_card'     => $this->renderSummaryCard($component, $locale),
            'error'            => $this->renderErrorComponent($component, $locale),
            'warning'          => $this->renderWarning($component, $locale),
            'suggestion'       => $this->renderSuggestion($component, $locale),
            'report_section'   => $this->renderReportSection($component, $locale),
            'quick_reply'      => null, // Telegram quick reply = Reply Keyboard, handled by Adapter
            default            => null,
        };
    }

    // ── Render methods ────────────────────────────────────────────

    private function renderText(TextComponent $c, string $locale): string
    {
        $text = trans($c->translationKey, $c->params, $locale);
        return $c->bold ? $text : $text;  // Bold sudah di-embed di translation string
    }

    private function renderDivider(): string
    {
        return '─────────────────────';
    }

    private function renderTransactionCard(TransactionCardComponent $c, string $locale): string
    {
        $trx    = $c->transaction;
        $amount = MoneyFormatter::rupiah($trx->amount);

        $typeName = match (strtolower($trx->type?->name ?? '')) {
            'income'               => trans('chat.transaction.type_income', [], $locale),
            'expense'              => trans('chat.transaction.type_expense', [], $locale),
            'transfer'             => trans('chat.transaction.type_transfer', [], $locale),
            'debt', 'receivable'   => trans('chat.transaction.type_debt_receivable', [], $locale),
            default                => trans('chat.transaction.type_default', [], $locale),
        };

        $statusIcon = $trx->is_cleared
            ? trans('chat.transaction.cleared', [], $locale)
            : trans('chat.transaction.uncleared', [], $locale);

        if (!$c->showDetails) {
            // Ringkas: untuk list dalam multi-transaction
            $index      = $c->index !== null ? "{$c->index}. ✅ " : '✅ ';
            $catName    = $trx->category?->category_name ?? '?';
            $wallet     = $trx->sourceWallet?->name ?? $trx->destinationWallet?->name ?? '?';
            return "{$index}_{$catName}_ *{$amount}* ({$wallet})";
        }

        // Detail: untuk single transaction
        $labelRef    = trans('chat.transaction.label_ref', [], $locale);
        $labelAmt    = trans('chat.transaction.label_amount', [], $locale);
        $labelCat    = trans('chat.transaction.label_category', [], $locale);
        $labelSrc    = trans('chat.transaction.label_source', [], $locale);
        $labelDst    = trans('chat.transaction.label_destination', [], $locale);
        $labelSubj   = trans('chat.transaction.label_subject', [], $locale);

        $catName     = $trx->category?->category_name ?? '-';
        $srcName     = $trx->sourceWallet?->name ?? '-';
        $dstName     = $trx->destinationWallet?->name ?? '-';
        $refNumber   = $trx->reference_number ?? '-';

        return implode("\n", [
            "{$statusIcon}",
            "_{$typeName}_",
            "",
            "🏷 *{$labelRef}    :* `{$refNumber}`",
            "💰 *{$labelAmt} :* {$amount}",
            "📂 *{$labelCat} :* {$catName}",
            "📤 *{$labelSrc}  :* {$srcName}",
            "📥 *{$labelDst}  :* {$dstName}",
            "👤 *{$labelSubj}     :* {$trx->subject}",
        ]);
    }

    private function renderSummaryCard(SummaryCardComponent $c, string $locale): string
    {
        if ($c->allSuccess()) {
            return trans('chat.multi.all_success', ['count' => $c->total], $locale);
        }
        if ($c->allFailed()) {
            return trans('chat.multi.all_failed', ['count' => $c->total], $locale);
        }
        return trans('chat.multi.partial', ['success' => $c->success, 'failed' => $c->failed], $locale);
    }

    private function renderErrorComponent(ErrorComponent $c, string $locale): string
    {
        $message = trans($c->messageKey, $c->params, $locale);
        $prefix  = $c->index !== null ? "{$c->index}. ❌" : '❌';
        $raw     = $c->raw ? " _{$c->raw}_" : '';
        $reason  = trans('chat.error.reason_prefix', [], $locale) . $message;

        return "{$prefix}{$raw}\n   {$reason}";
    }

    private function renderWarning(WarningComponent $c, string $locale): string
    {
        $text = trans($c->messageKey, $c->params, $locale);
        return "⚠️ {$text}";
    }

    private function renderSuggestion(SuggestionComponent $c, string $locale): string
    {
        $text = trans($c->messageKey, $c->params, $locale);
        return "💡 {$text}";
    }

    private function renderReportSection(ReportSectionComponent $c, string $locale): string
    {
        $title = $c->title ?: ($c->translationKey ? trans($c->translationKey, [], $locale) : '');
        $emoji = $c->emoji ? $c->emoji . ' ' : '';
        $header = "{$emoji}*{$title}*";

        if (empty($c->items)) {
            return $header;
        }

        $lines = [$header];

        $isSaldoOrWallet = str_contains(strtolower($c->translationKey ?? ''), 'balance')
            || str_contains(strtolower($c->translationKey ?? ''), 'saldo')
            || str_contains(strtolower($c->translationKey ?? ''), 'wallet');

        if ($isSaldoOrWallet) {
            $walletData = [];
            $maxNameLen = 0;
            $maxBalLen  = 0;

            foreach ($c->items as $item) {
                if (is_array($item)) {
                    $name   = $item['name'] ?? $item['category'] ?? '-';
                    $balStr = $item['amount'] ?? '';
                } elseif (str_contains($item, ':')) {
                    $parts = explode(':', $item, 2);
                    $name = trim($parts[0]);
                    $balStr = trim($parts[1]);
                } elseif (str_contains($item, ' — ')) {
                    $parts = explode(' — ', $item, 2);
                    $balStr = trim($parts[0]);
                    $name = trim($parts[1]);
                } else {
                    $name = $item;
                    $balStr = '';
                }

                $nameUpper = strtoupper($name);
                if (strlen($nameUpper) > $maxNameLen) {
                    $maxNameLen = strlen($nameUpper);
                }
                if (strlen($balStr) > $maxBalLen) {
                    $maxBalLen = strlen($balStr);
                }
                $walletData[] = ['name' => $nameUpper, 'balStr' => $balStr];
            }

            if ($maxNameLen > 0) {
                $textMsg = "```text\n";
                foreach ($walletData as $wd) {
                    if ($wd['balStr'] !== '') {
                        $textMsg .= str_pad($wd['name'], $maxNameLen, ' ', STR_PAD_RIGHT)
                            . ': '
                            . str_pad($wd['balStr'], $maxBalLen, ' ', STR_PAD_LEFT) . "\n";
                    } else {
                        $textMsg .= $wd['name'] . "\n";
                    }
                }
                $textMsg .= "```";
                $lines[] = $textMsg;
            } else {
                foreach ($c->items as $item) {
                    $lines[] = "▫️ {$item}";
                }
            }
        } else {
            $isCategorySection = str_contains(strtolower($c->translationKey ?? ''), 'category');

            if ($isCategorySection && !empty($c->items) && is_array($c->items[0]) && isset($c->items[0]['categories'])) {
                // Structured category sections
                foreach ($c->items as $section) {
                    $typeIcon = $section['type_icon'] ?? '📁';
                    $typeLabel = $section['label_key'] ? trans($section['label_key'], [], $locale) : ($section['type_name'] ?? 'Other');
                    $lines[] = "{$typeIcon} *{$typeLabel}*";
                    foreach ($section['categories'] as $cat) {
                        $lines[] = "  • {$cat}";
                    }
                    $lines[] = '';
                }
            } else {
                foreach ($c->items as $item) {
                    if (is_array($item)) {
                        $icon     = $item['category_icon'] ?? '📄';
                        $category = $item['category'] ?? '-';
                        $amount   = $item['amount'] ?? '';
                        $date     = $item['date'] ?? '';
                        $wallet   = $item['wallet'] ?? '';
                        $lines[]  = "{$icon} {$category} — {$amount} ({$date} - {$wallet})";
                    } else {
                        $lines[] = "▫️ {$item}";
                    }
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Render ErrorDetail (dari ChatResponse::errors[]) sebagai pesan standalone.
     * Dipakai untuk error level ChatIntent::Error (AI gagal, dll).
     */
    private function renderError(ErrorDetail $error, string $locale): string
    {
        return trans($error->messageKey, $error->params, $locale);
    }

    // ── Footer helper ─────────────────────────────────────────────

    /**
     * Buat baris footer provider AI (dipakai ChatApplicationService saat build components).
     * Formatter menyediakan helper ini agar mudah dipanggil dari luar.
     */
    public function providerLabel(string $provider, string $locale): string
    {
        $key = match (strtoupper($provider)) {
            'PYTHON-NLP' => 'chat.ai.provider_python',
            'GEMINI'     => 'chat.ai.provider_gemini',
            'OPENAI'     => 'chat.ai.provider_openai',
            'DEEPSEEK'   => 'chat.ai.provider_deepseek',
            default      => 'chat.ai.provider_default',
        };
        return trans($key, ['provider' => strtoupper($provider)], $locale);
    }
}
