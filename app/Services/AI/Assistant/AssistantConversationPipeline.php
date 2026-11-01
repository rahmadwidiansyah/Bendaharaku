<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant;

use App\Chat\Components\TextComponent;
use App\Chat\DTOs\ChatContext;
use App\Chat\DTOs\ChatResponse;
use App\DTO\AgentTurn;
use App\Enums\ChatIntent;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AssistantConversationPipeline — jembatan AgentLoop → ChatResponse.
 *
 * Dipakai untuk intent conversation/question sebelum orchestrator transaksi.
 * Tidak pernah menulis ke tabel transaksi.
 */
final class AssistantConversationPipeline
{
    public function __construct(
        private readonly AgentLoop $agentLoop,
    ) {}

    public function handle(
        User $user,
        string $text,
        string $intent,
        ChatContext $context,
        float $startTime,
    ): ChatResponse {
        $conversationId = $this->conversationId($context);
        $userMessageId = $this->userMessageId($context);

        // Intent percakapan TIDAK boleh fatal: bila agent loop gagal total
        // (tanpa kandidat LLM, provider down), user tetap dibalas dengan
        // fallback template conversation (spesifikasi §12 degradasi anggun).
        try {
            $result = $this->agentLoop->run(
                user: $user,
                text: $text,
                intent: $intent,
                conversationId: $conversationId,
                userMessageId: $userMessageId,
                traceId: $context->traceId,
            );
        } catch (Throwable $e) {
            Log::warning('AssistantConversationPipeline: agent loop gagal, memakai fallback', [
                'user_id' => $user->id,
                'intent' => $intent,
                'error' => mb_substr($e->getMessage(), 0, 300),
            ]);

            $result = [
                'response' => AssistantResponse::fallback('conversation'),
                'turn' => new AgentTurn(content: null),
                'routing' => [
                    'selection_method' => 'conversation_fallback',
                    'final_status' => 'fallback',
                    'fallback_reason' => mb_substr($e->getMessage(), 0, 200),
                ],
                'iterations' => 0,
                'tool_calls' => 0,
                'fallback' => true,
            ];
        }

        $latency = (int) round((microtime(true) - $startTime) * 1000);
        $routing = is_array($result['routing'] ?? null) ? $result['routing'] : [];

        $metadata = [
            'trace_id' => $context->traceId,
            'platform' => $context->platform->value,
            'provider' => $routing['selected_provider'] ?? null,
            'model' => $routing['selected_model'] ?? null,
            'latency_ms' => $latency,
            'total_tokens' => $result['turn']->usage['total'] ?? null,
            'intent' => $intent,
            'confidence' => null,
            'tool_calls' => $result['tool_calls'],
            'tool_iterations' => $result['iterations'],
            'assistant_fallback' => $result['fallback'],
            'routing' => $this->summarizeRouting($routing),
        ];

        return ChatResponse::assistant(
            intent: $intent === 'question' ? ChatIntent::Question : ChatIntent::Conversation,
            components: [
                new TextComponent(
                    translationKey: $result['response']->message,
                    literal: true,
                ),
            ],
            metadata: $metadata,
        );
    }

    private function conversationId(ChatContext $context): ?int
    {
        $fromMetadata = $context->metadata['conversation_db_id'] ?? null;
        if (is_numeric($fromMetadata)) {
            return (int) $fromMetadata;
        }

        return is_numeric($context->conversationId) ? (int) $context->conversationId : null;
    }

    private function userMessageId(ChatContext $context): ?int
    {
        $id = $context->metadata['web_message_id'] ?? null;

        return is_numeric($id) ? (int) $id : null;
    }

    /** @param array<string, mixed> $routing */
    private function summarizeRouting(array $routing): array
    {
        if ($routing === []) {
            return [];
        }

        $summary = array_filter([
            'selected_provider' => $routing['selected_provider'] ?? null,
            'selected_model' => $routing['selected_model'] ?? null,
            'selected_account_id' => $routing['selected_account_id'] ?? null,
            'attempt_count' => $routing['attempt_count'] ?? null,
            'final_status' => $routing['final_status'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $attempts = $routing['attempts'] ?? [];
        if (is_array($attempts) && $attempts !== []) {
            $summary['attempts'] = array_values(array_map(
                fn ($attempt) => array_filter([
                    'provider' => $attempt['provider'] ?? null,
                    'model' => $attempt['model'] ?? null,
                    'category' => $attempt['category'] ?? null,
                    'latency_ms' => $attempt['latency_ms'] ?? null,
                ], fn ($value) => $value !== null && $value !== ''),
                array_slice($attempts, 0, 8),
            ));
        }

        return $summary;
    }
}
