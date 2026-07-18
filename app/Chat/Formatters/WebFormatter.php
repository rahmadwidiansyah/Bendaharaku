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
use App\Chat\DTOs\ChatResponse;
use App\Chat\DTOs\ChatContext;
use App\Chat\Errors\ErrorDetail;
use App\Enums\ChatIntent;
use App\Support\MoneyFormatter;

/**
 * WebFormatter — Serialize ChatResponse ke JSON array untuk Vue frontend.
 *
 * Output: array of component objects, masing-masing memiliki 'type'
 * yang digunakan MessageRenderer.vue untuk dispatch ke komponen Vue yang benar.
 *
 * Berbeda dengan TelegramFormatter yang menghasilkan Markdown string,
 * WebFormatter menghasilkan structured data yang dirender oleh Vue.
 *
 * Setiap komponen mengikuti format:
 * {
 *   type: 'text' | 'transaction_card' | 'error' | ...
 *   // field spesifik per tipe
 * }
 *
 * Tidak ada HTML, tidak ada CSS class di sini.
 * Semua presentasi diserahkan sepenuhnya ke Vue component.
 */
class WebFormatter implements ChatFormatterInterface
{
    public function supports(string $platform): bool
    {
        return $platform === 'web';
    }

    /**
     * Format ChatResponse → array yang siap di-JSON-encode.
     *
     * @return array{ components: array, errors: array, metadata: array }
     */
    public function format(ChatResponse $response, ChatContext $context): array
    {
        $locale     = $context->locale;
        $components = [];

        // Error-only response (AI gagal, konfigurasi salah, dll)
        if ($response->intent === ChatIntent::Error && $response->hasErrors()) {
            foreach ($response->errors as $error) {
                $components[] = $this->formatError($error, $locale);
            }
            return [
                'components' => $components,
                'errors'     => array_map(fn ($e) => $e->toArray(), $response->errors),
                'metadata'   => $response->metadata,
            ];
        }

        // Render tiap komponen secara berurutan
        foreach ($response->components as $component) {
            $rendered = $this->renderComponent($component, $locale);
            if ($rendered !== null) {
                $components[] = $rendered;
            }
        }

        // Error inline (partial multi-transaction)
        foreach ($response->errors as $error) {
            $components[] = $this->formatError($error, $locale);
        }

        return [
            'components' => $components,
            'errors'     => array_map(fn ($e) => $e->toArray(), $response->errors),
            'metadata'   => $response->metadata,
        ];
    }

    // ── Component dispatch ────────────────────────────────────────

    private function renderComponent(ChatComponentInterface $component, string $locale): ?array
    {
        return match ($component->type()) {
            'text'             => $this->renderText($component, $locale),
            'divider'          => $this->renderDivider(),
            'transaction_card' => $this->renderTransactionCard($component, $locale),
            'summary_card'     => $this->renderSummaryCard($component, $locale),
            'error'            => $this->renderErrorComponent($component, $locale),
            'warning'          => $this->renderWarning($component, $locale),
            'suggestion'       => $this->renderSuggestion($component, $locale),
            'quick_reply'      => $this->renderQuickReply($component, $locale),
            default            => null,
        };
    }

    // ── Renderers ─────────────────────────────────────────────────

    private function renderText(TextComponent $c, string $locale): array
    {
        return [
            'type' => 'text',
            'text' => trans($c->translationKey, $c->params, $locale),
            'bold' => $c->bold,
        ];
    }

    private function renderDivider(): array
    {
        return ['type' => 'divider'];
    }

    private function renderTransactionCard(TransactionCardComponent $c, string $locale): array
    {
        $trx = $c->transaction;

        $typeKey = match (strtolower($trx->type?->name ?? '')) {
            'income'             => 'income',
            'expense'            => 'expense',
            'transfer'           => 'transfer',
            'debt', 'receivable' => 'debt',
            default              => 'other',
        };

        return [
            'type'         => 'transaction_card',
            'index'        => $c->index,
            'show_details' => $c->showDetails,
            'transaction'  => [
                'id'               => $trx->id,
                'reference_number' => $trx->reference_number,
                'amount'           => $trx->amount,
                'amount_formatted' => MoneyFormatter::rupiah($trx->amount),
                'is_cleared'       => $trx->is_cleared,
                'needs_wallet'     => !$trx->is_cleared && $trx->sourceWallet?->group_type === 'System',
                'type_key'         => $typeKey,
                'type_label'       => trans("chat.transaction.type_{$typeKey}", [], $locale),
                'category'         => $trx->category?->category_name,
                'source_wallet'    => $trx->sourceWallet?->name,
                'dest_wallet'      => $trx->destinationWallet?->name,
                'subject'          => $trx->subject,
                'notes'            => $trx->notes,
                'date'             => $trx->date?->toDateString(),
                'created_at'       => $trx->created_at?->toIso8601String(),
            ],
        ];
    }

    private function renderSummaryCard(SummaryCardComponent $c, string $locale): array
    {
        return [
            'type'       => 'summary_card',
            'total'      => $c->total,
            'success'    => $c->success,
            'failed'     => $c->failed,
            'confidence' => round($c->confidence * 100),
            'all_success' => $c->allSuccess(),
            'all_failed'  => $c->allFailed(),
            'label'       => $c->allSuccess()
                ? trans('chat.multi.all_success', ['count' => $c->total], $locale)
                : ($c->allFailed()
                    ? trans('chat.multi.all_failed', ['count' => $c->total], $locale)
                    : trans('chat.multi.partial', ['success' => $c->success, 'failed' => $c->failed], $locale)),
        ];
    }

    private function renderErrorComponent(ErrorComponent $c, string $locale): array
    {
        return [
            'type'        => 'error',
            'index'       => $c->index,
            'message'     => trans($c->messageKey, $c->params, $locale),
            'raw'         => $c->raw,
            'severity'    => $c->severity->value,
            'recoverable' => $c->recoverable,
        ];
    }

    private function renderWarning(WarningComponent $c, string $locale): array
    {
        return [
            'type'    => 'warning',
            'message' => trans($c->messageKey, $c->params, $locale),
        ];
    }

    private function renderSuggestion(SuggestionComponent $c, string $locale): array
    {
        return [
            'type'    => 'suggestion',
            'message' => trans($c->messageKey, $c->params, $locale),
        ];
    }

    private function renderQuickReply(QuickReplyComponent $c, string $locale): array
    {
        return [
            'type'    => 'quick_reply',
            'options' => $c->options,
        ];
    }

    private function formatError(ErrorDetail $error, string $locale): array
    {
        return [
            'type'     => 'error',
            'index'    => null,
            'message'  => trans($error->messageKey, $error->params, $locale),
            'raw'      => null,
            'severity' => $error->severity->value,
        ];
    }
}
