<?php

declare(strict_types=1);

namespace App\Services\AI\Adapters;

use App\DTO\AgentRequest;
use App\DTO\AgentTurn;
use App\DTO\AIParseResult;
use App\DTO\AIParseResultMulti;
use App\DTO\MemoryCandidate;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use App\Exceptions\AiProviderException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\AiTokenLimitException;
use App\Services\AI\Adapters\Contracts\LLMAdapterInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseAdapter implements LLMAdapterInterface
{
    abstract protected function getProviderName(): string;

    abstract protected function getBaseUrl(string $model): string;

    abstract protected function buildRequestBody(string $model, string $prompt): array;

    abstract protected function extractResponseContent(array $responseData): ?string;

    abstract protected function extractUsage(array $responseData): array;

    /** Bangun body request untuk satu turn agent (provider-specific). */
    abstract protected function buildAgentRequestBody(AgentRequest $request): array;

    /** Ekstrak content + tool calls dari response provider (provider-specific). */
    abstract protected function extractAgentTurn(array $responseData): AgentTurn;

    protected function getSingleTimeout(): int
    {
        return 15;
    }

    protected function getAgentTimeout(): int
    {
        // Loop agent sendiri dibatasi 45 dtk oleh AgentLoop; satu HTTP call
        // harus lebih pendek agar masih ada ruang untuk iterasi berikutnya.
        return 30;
    }

    protected function getMultiTimeout(): int
    {
        return 20;
    }

    protected function sendRequest(string $url, string $apiKey, array $body, int $timeout): Response
    {
        return Http::timeout($timeout)
            ->withToken($apiKey)
            ->post($url, $body);
    }

    public function generateText(
        string $prompt,
        string $apiKey,
        string $model,
        ?int $timeoutSeconds = null,
    ): string {
        $responseData = [];

        try {
            $url = $this->getBaseUrl($model);
            $body = $this->buildRequestBody($model, $prompt);

            $response = $this->sendRequest($url, $apiKey, $body, $timeoutSeconds ?? $this->getSingleTimeout());

            $this->assertSuccessful($response);

            $responseData = $response->json() ?? [];
            $content = $this->extractResponseContent($responseData);

            if (blank($content)) {
                throw new AiProviderException($this->getProviderName(), 'Response LLM kosong.');
            }

            return $content;
        } catch (AiRateLimitException|AiTimeoutException|AiProviderException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            Log::warning($this->getProviderName().' Connection Timeout', ['message' => $e->getMessage()]);
            throw new AiTimeoutException($this->getProviderName());
        } catch (Throwable $e) {
            $this->logProviderCrash($e, $model, $responseData);
            throw new AiProviderException($this->getProviderName(), $e->getMessage());
        }
    }

    public function parseTransaction(
        string $prompt,
        string $apiKey,
        string $model,
        string $fallbackText = '',
    ): AIParseResult {
        $responseData = [];

        try {
            $url = $this->getBaseUrl($model);
            $body = $this->buildRequestBody($model, $prompt);

            $response = $this->sendRequest($url, $apiKey, $body, $this->getSingleTimeout());

            $this->assertSuccessful($response);

            $responseData = $response->json() ?? [];
            $jsonString = $this->extractResponseContent($responseData);
            $aiRaw = $this->decodeJson((string) $jsonString);

            if (isset($aiRaw['is_transaction']) && $aiRaw['is_transaction'] === false) {
                return AIParseResult::failure(
                    $aiRaw['reply_message'] ?? 'Maaf Bos, saya hanya bisa membantu mencatat keuangan di Bendaharaku.',
                    $this->getProviderName(),
                    $model,
                );
            }

            if (! isset($aiRaw['amount']) || ! is_numeric($aiRaw['amount'])) {
                throw new AiProviderException($this->getProviderName(), 'Response tidak mengandung format transaksi yang valid.');
            }

            $this->validateTransactionFields($aiRaw);

            return new AIParseResult(
                success: true,
                confidence: (float) ($aiRaw['confidence'] ?? 0.85),
                error: null,
                transaction: $this->buildParsedTransaction($aiRaw, $fallbackText),
                usage: $this->normalizeUsage($this->extractUsage($responseData)),
                provider: $this->getProviderName(),
                model: $model,
            );

        } catch (AiRateLimitException|AiTimeoutException|AiProviderException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            Log::warning($this->getProviderName().' Connection Timeout', ['message' => $e->getMessage()]);
            throw new AiTimeoutException($this->getProviderName());
        } catch (Throwable $e) {
            $this->logProviderCrash($e, $model, $responseData);
            throw new AiProviderException($this->getProviderName(), $e->getMessage());
        }
    }

    public function parseMultiTransaction(
        string $prompt,
        string $apiKey,
        string $model,
        string $fallbackText = '',
    ): AIParseResultMulti {
        $responseData = [];

        try {
            $url = $this->getBaseUrl($model);
            $body = $this->buildRequestBody($model, $prompt);
            $headers = ['Authorization' => 'Bearer '.$apiKey];

            $response = $this->sendRequest($url, $apiKey, $body, $this->getMultiTimeout());

            $this->assertSuccessful($response);

            $responseData = $response->json() ?? [];
            $jsonString = $this->extractResponseContent($responseData);
            $aiRaw = $this->decodeJson((string) $jsonString);

            if (isset($aiRaw['is_transaction']) && $aiRaw['is_transaction'] === false) {
                return new AIParseResultMulti(
                    success: false,
                    transactions: [],
                    confidence: 0.0,
                    error: $aiRaw['reply_message'] ?? 'Maaf Bos, saya hanya bisa membantu mencatat keuangan di Bendaharaku.',
                    isOutOfScope: true,
                    replyMessage: $aiRaw['reply_message'] ?? null,
                    usage: [],
                    provider: $this->getProviderName(),
                    model: $model,
                );
            }

            if (! isset($aiRaw['transactions']) || ! is_array($aiRaw['transactions'])) {
                throw new AiProviderException($this->getProviderName(), 'Response multi-transaksi tidak mengandung array "transactions".');
            }

            foreach ($aiRaw['transactions'] as $transaction) {
                if (! is_array($transaction) || ! isset($transaction['amount']) || ! is_numeric($transaction['amount'])) {
                    throw new AiProviderException($this->getProviderName(), 'Item response multi-transaksi tidak valid.');
                }

                $this->validateTransactionFields($transaction);
            }

            return new AIParseResultMulti(
                success: true,
                transactions: $this->buildParsedTransactions($aiRaw['transactions'], $fallbackText),
                confidence: $this->averageConfidence($aiRaw['transactions']),
                error: null,
                usage: $this->normalizeUsage($this->extractUsage($responseData)),
                provider: $this->getProviderName(),
                model: $model,
            );

        } catch (AiRateLimitException|AiTimeoutException|AiProviderException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            Log::warning($this->getProviderName().' Multi Connection Timeout', ['message' => $e->getMessage()]);
            throw new AiTimeoutException($this->getProviderName());
        } catch (Throwable $e) {
            $this->logProviderCrash($e, $model, $responseData);
            throw new AiProviderException($this->getProviderName(), $e->getMessage());
        }
    }

    public function chatWithTools(AgentRequest $request): AgentTurn
    {
        $responseData = [];

        try {
            $url = $this->getBaseUrl($request->model);
            $body = $this->buildAgentRequestBody($request);

            $response = $this->sendRequest($url, $request->apiKey, $body, $this->getAgentTimeout());

            $this->assertSuccessful($response);

            $responseData = $response->json() ?? [];
            $turn = $this->extractAgentTurn($responseData);

            if ($turn->content === null && ! $turn->hasToolCalls()) {
                throw new AiProviderException($this->getProviderName(), 'Response agent kosong.');
            }

            return $turn;
        } catch (AiRateLimitException|AiTimeoutException|AiProviderException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            Log::warning($this->getProviderName().' Agent Connection Timeout', ['message' => $e->getMessage()]);
            throw new AiTimeoutException($this->getProviderName());
        } catch (Throwable $e) {
            $this->logProviderCrash($e, $request->model, $responseData);
            throw new AiProviderException($this->getProviderName(), $e->getMessage());
        }
    }

    // ── Shared Helpers ────────────────────────────────────────────────

    /**
     * Body request keluarga OpenAI (OpenAI, DeepSeek, OpenAI-compatible).
     * response_format sengaja TIDAK dipasang saat tools aktif karena beberapa
     * server menolak kombinasi tersebut.
     */
    protected function buildOpenAiAgentRequestBody(AgentRequest $request, bool $stream = false): array
    {
        $messages = [];

        foreach ($request->messages as $message) {
            $role = (string) ($message['role'] ?? 'user');

            if ($role === 'assistant') {
                $mapped = ['role' => 'assistant', 'content' => $message['content'] ?? null];
                $toolCalls = $message['tool_calls'] ?? [];

                if (is_array($toolCalls) && $toolCalls !== []) {
                    $mapped['tool_calls'] = array_map(function (array $call): array {
                        return [
                            'id' => (string) ($call['id'] ?? ''),
                            'type' => 'function',
                            'function' => [
                                'name' => (string) ($call['name'] ?? ''),
                                'arguments' => json_encode($call['arguments'] ?? new \stdClass, JSON_UNESCAPED_UNICODE),
                            ],
                        ];
                    }, $toolCalls);
                }

                $messages[] = $mapped;

                continue;
            }

            if ($role === 'tool') {
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => (string) ($message['tool_call_id'] ?? ''),
                    'content' => (string) ($message['content'] ?? ''),
                ];

                continue;
            }

            $messages[] = [
                'role' => $role,
                'content' => (string) ($message['content'] ?? ''),
            ];
        }

        $body = [
            'model' => $request->model,
            'messages' => $messages,
        ];

        if ($stream) {
            $body['stream'] = false;
        }

        if ($request->tools !== []) {
            $body['tools'] = array_map(function (array $tool): array {
                return [
                    'type' => 'function',
                    'function' => [
                        'name' => (string) ($tool['name'] ?? ''),
                        'description' => (string) ($tool['description'] ?? ''),
                        'parameters' => $tool['parameters'] ?? [
                            'type' => 'object',
                            'properties' => new \stdClass,
                            'required' => [],
                        ],
                    ],
                ];
            }, $request->tools);
        }

        return $body;
    }

    protected function extractOpenAiAgentTurn(array $responseData): AgentTurn
    {
        $message = $responseData['choices'][0]['message'] ?? [];
        $toolCalls = [];

        foreach (($message['tool_calls'] ?? []) as $index => $call) {
            $arguments = $call['function']['arguments'] ?? [];
            if (is_string($arguments)) {
                $decoded = json_decode($arguments, true);
                $arguments = is_array($decoded) ? $decoded : [];
            }

            $name = (string) ($call['function']['name'] ?? '');
            $toolCalls[] = [
                'id' => (string) ($call['id'] ?? $this->agentToolCallId($index, $name, is_array($arguments) ? $arguments : [])),
                'name' => $name,
                'arguments' => is_array($arguments) ? $arguments : [],
            ];
        }

        $content = $message['content'] ?? null;

        return new AgentTurn(
            content: is_string($content) && trim($content) !== '' ? $content : null,
            toolCalls: $toolCalls,
            usage: $this->normalizeUsage($this->extractUsage($responseData)),
            finishReason: $responseData['choices'][0]['finish_reason'] ?? null,
            raw: $responseData,
        );
    }

    /**
     * Body request Gemini: systemInstruction terpisah, contents berisi turn
     * user/model/functionResponse, dan functionDeclarations untuk tools.
     */
    protected function buildGeminiAgentRequestBody(AgentRequest $request): array
    {
        $systemParts = [];
        $contents = [];

        foreach ($request->messages as $message) {
            $role = (string) ($message['role'] ?? 'user');

            if ($role === 'system') {
                $systemParts[] = ['text' => (string) ($message['content'] ?? '')];

                continue;
            }

            if ($role === 'assistant') {
                $parts = [];
                if (! empty($message['content'])) {
                    $parts[] = ['text' => (string) $message['content']];
                }
                foreach (($message['tool_calls'] ?? []) as $call) {
                    $functionCall = [
                        'name' => (string) ($call['name'] ?? ''),
                        'args' => (object) ($call['arguments'] ?? []),
                    ];
                    // Echo HANYA id native dari provider (Gemini 3.x menuntut
                    // konsistensi dengan functionResponse); id sintetis aplikasi
                    // tidak pernah dikirim balik ke provider.
                    if (is_string($call['provider_id'] ?? null) && $call['provider_id'] !== '') {
                        $functionCall['id'] = $call['provider_id'];
                    }
                    $callPart = ['functionCall' => $functionCall];
                    // thoughtSignature wajib di-echo verbatim pada level part —
                    // tanpa itu Gemini 3.x menolak request dengan 400.
                    $signature = $call['thought_signature'] ?? null;
                    if (is_string($signature) && $signature !== '') {
                        $callPart['thoughtSignature'] = $signature;
                    }
                    $parts[] = $callPart;
                }
                if ($parts !== []) {
                    $contents[] = ['role' => 'model', 'parts' => $parts];
                }

                continue;
            }

            if ($role === 'tool') {
                $content = $message['content'] ?? '';
                $decoded = is_string($content) ? json_decode($content, true) : $content;
                $response = is_array($decoded) ? $decoded : ['result' => (string) $content];

                $contents[] = [
                    'role' => 'user',
                    'parts' => [[
                        'functionResponse' => [
                            'name' => (string) ($message['name'] ?? 'tool'),
                            'response' => $response,
                        ],
                    ]],
                ];

                continue;
            }

            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => (string) ($message['content'] ?? '')]],
            ];
        }

        $body = ['contents' => $contents];

        if ($systemParts !== []) {
            $body['systemInstruction'] = ['parts' => $systemParts];
        }

        if ($request->tools !== []) {
            $body['tools'] = [[
                'functionDeclarations' => array_map(function (array $tool): array {
                    return [
                        'name' => (string) ($tool['name'] ?? ''),
                        'description' => (string) ($tool['description'] ?? ''),
                        'parameters' => $tool['parameters'] ?? [
                            'type' => 'object',
                            'properties' => new \stdClass,
                            'required' => [],
                        ],
                    ];
                }, $request->tools),
            ]];
        }

        return $body;
    }

    protected function extractGeminiAgentTurn(array $responseData): AgentTurn
    {
        $parts = $responseData['candidates'][0]['content']['parts'] ?? [];
        $text = '';
        $toolCalls = [];

        foreach ($parts as $index => $part) {
            if (isset($part['text']) && is_string($part['text'])) {
                $text .= $part['text'];
            }

            if (! isset($part['functionCall'])) {
                continue;
            }

            $name = (string) ($part['functionCall']['name'] ?? '');
            $arguments = $part['functionCall']['args'] ?? [];
            if (is_string($arguments)) {
                $decoded = json_decode($arguments, true);
                $arguments = is_array($decoded) ? $decoded : [];
            }

            // Gemini 3.x menuntut thoughtSignature di-echo balik pada
            // functionCall part saat riwayat dikirim ulang — tanpa itu
            // request berikutnya ditolak 400 INVALID_ARGUMENT. Id asli
            // dari provider dipertahankan terpisah agar hanya id native
            // yang di-echo (id sintetis tidak pernah dikirim balik).
            $providerId = (string) ($part['functionCall']['id'] ?? '');
            $signature = $part['thoughtSignature'] ?? $part['thought_signature'] ?? null;

            $toolCalls[] = [
                'id' => $providerId !== ''
                    ? $providerId
                    : $this->agentToolCallId($index, $name, is_array($arguments) ? $arguments : []),
                'name' => $name,
                'arguments' => is_array($arguments) ? $arguments : [],
                'provider_id' => $providerId !== '' ? $providerId : null,
                'thought_signature' => is_string($signature) && $signature !== '' ? $signature : null,
            ];
        }

        return new AgentTurn(
            content: trim($text) !== '' ? $text : null,
            toolCalls: $toolCalls,
            usage: $this->normalizeUsage($this->extractUsage($responseData)),
            finishReason: $responseData['candidates'][0]['finishReason'] ?? null,
            raw: $responseData,
        );
    }

    protected function agentToolCallId(int $index, string $name, array $arguments): string
    {
        return 'call_'.$index.'_'.substr(hash('sha256', $name.json_encode($arguments)), 0, 12);
    }

    protected function assertSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $statusCode = $response->status();
        $provider = $this->getProviderName();

        // Body error provider wajib tercatat: AiErrorClassifier mengganti
        // pesan exception dengan safe message sehingga tanpa log ini alasan
        // persis (mis. field yang ditolak) hilang dari jejak produksi.
        Log::warning($provider.' HTTP error body', [
            'status' => $statusCode,
            'body' => substr($response->body(), 0, 500),
        ]);

        if ($statusCode === 429) {
            throw new AiRateLimitException($provider);
        }
        if (in_array($statusCode, [408, 503, 504])) {
            throw new AiTimeoutException($provider, $statusCode);
        }
        if (in_array($statusCode, [401, 403])) {
            throw new AiProviderException($provider, "API Key tidak valid (HTTP {$statusCode}).");
        }

        if ($statusCode === 400) {
            $bodyLower = mb_strtolower($response->body());
            if (str_contains($bodyLower, 'token')
                || str_contains($bodyLower, 'context')
                || str_contains($bodyLower, 'input too long')
                || str_contains($bodyLower, 'exceeded')
                || str_contains($bodyLower, 'maximum')) {
                throw new AiTokenLimitException($provider, 0, $response->body());
            }
        }

        throw new AiProviderException($provider, 'HTTP '.$statusCode.': '.substr($response->body(), 0, 200));
    }

    protected function decodeJson(string $jsonString): array
    {
        $clean = trim(preg_replace('/\A\s*```(?:json)?\s*|\s*```\s*\z/i', '', $jsonString));
        $data = json_decode($clean, true);

        if (! is_array($data)) {
            throw new AiProviderException($this->getProviderName(), 'Gagal decode JSON dari response LLM.');
        }

        return $data;
    }

    protected function buildParsedTransaction(array $raw, string $fallbackNotes): ParsedTransaction
    {
        $intentString = $raw['transaction_type'] ?? $raw['transactionType'] ?? null;

        return new ParsedTransaction(
            amount: (float) ($raw['amount'] ?? 0),
            transactionType: $intentString ? TransactionIntent::tryFrom(strtolower(trim($intentString))) : null,
            category: $raw['category'] ?? null,
            categoryKeyword: $raw['category_keyword'] ?? null,
            sourceWallet: $raw['source_wallet'] ?? $raw['sourceWallet'] ?? null,
            sourceWalletKeyword: $raw['source_wallet_keyword'] ?? null,
            destinationWallet: $raw['destination_wallet'] ?? $raw['destinationWallet'] ?? null,
            destinationWalletKeyword: $raw['destination_wallet_keyword'] ?? null,
            memoryCandidates: $this->buildMemoryCandidates($raw),
            subject: $raw['subject'] ?? null,
            notes: $raw['notes'] ?? $fallbackNotes,
            isCleared: (bool) ($raw['is_cleared'] ?? $raw['isCleared'] ?? true),
            useAllBalance: (bool) ($raw['use_all_balance'] ?? false),
        );
    }

    private function validateTransactionFields(array $raw): void
    {
        foreach (['category_keyword', 'source_wallet_keyword', 'destination_wallet_keyword'] as $field) {
            if (array_key_exists($field, $raw) && $raw[$field] !== null && ! is_string($raw[$field])) {
                throw new AiProviderException($this->getProviderName(), 'Field '.$field.' harus berupa string atau null.');
            }
        }

        if (array_key_exists('memory_candidates', $raw) && ! is_array($raw['memory_candidates'])) {
            throw new AiProviderException($this->getProviderName(), 'Field memory_candidates harus berupa array.');
        }
    }

    protected function buildMemoryCandidates(array $raw): array
    {
        $candidates = $raw['memory_candidates'] ?? $raw['memoryCandidates'] ?? [];

        if (! is_array($candidates)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static function (mixed $candidate): ?MemoryCandidate {
                if (! is_array($candidate) || ! is_string($candidate['keyword'] ?? null) || ! is_string($candidate['target_type'] ?? $candidate['targetType'] ?? null)) {
                    return null;
                }

                return new MemoryCandidate(
                    keyword: trim($candidate['keyword']),
                    targetType: trim($candidate['target_type'] ?? $candidate['targetType']),
                    targetName: isset($candidate['target_name']) ? (string) $candidate['target_name'] : ($candidate['targetName'] ?? null),
                );
            },
            $candidates
        )));
    }

    /** @return ParsedTransaction[] */
    protected function buildParsedTransactions(array $items, string $fallbackNotes): array
    {
        return array_values(array_map(
            fn (array $item) => $this->buildParsedTransaction($item, $fallbackNotes),
            $items
        ));
    }

    protected function averageConfidence(array $items): float
    {
        if (empty($items)) {
            return 0.0;
        }

        $total = array_sum(array_map(fn ($i) => (float) ($i['confidence'] ?? 0.85), $items));

        return round($total / count($items), 4);
    }

    /** @return array{prompt: int, completion: int, total: int} */
    protected function normalizeUsage(array $usage): array
    {
        $prompt = (int) ($usage['prompt'] ?? 0);
        $completion = (int) ($usage['completion'] ?? 0);
        $total = (int) ($usage['total'] ?? ($prompt + $completion));

        return [
            'prompt' => max(0, $prompt),
            'completion' => max(0, $completion),
            'total' => max(0, $total),
        ];
    }

    protected function logProviderCrash(Throwable $e, string $model, array $responseData = []): void
    {
        Log::error('LLM Provider Crash', [
            'provider' => $this->getProviderName(),
            'model' => $model,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'usageMetadata' => $responseData['usageMetadata'] ?? null,
            'raw_response' => $this->sanitizeProviderResponse($responseData),
        ]);
    }

    protected function sanitizeProviderResponse(array $data): array
    {
        $sensitiveKeys = ['api_key', 'apikey', 'key', 'token', 'authorization', 'access_token'];
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $sanitized[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeProviderResponse($value);

                continue;
            }

            if (is_string($value) && strlen($value) > 2000) {
                $sanitized[$key] = substr($value, 0, 2000).'... [truncated]';

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
