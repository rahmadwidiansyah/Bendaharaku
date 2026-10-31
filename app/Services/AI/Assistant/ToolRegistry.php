<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant;

use App\Models\AiToolLog;
use App\Models\User;
use App\Services\AI\Assistant\Tools\ComparePeriodsTool;
use App\Services\AI\Assistant\Tools\GetCategoriesTool;
use App\Services\AI\Assistant\Tools\GetFinancialOverviewTool;
use App\Services\AI\Assistant\Tools\GetMonthlySummaryTool;
use App\Services\AI\Assistant\Tools\GetTransactionsTool;
use App\Services\AI\Assistant\Tools\GetWalletsTool;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * ToolRegistry — satu pintu eksekusi tool assistant.
 *
 * Tanggung jawab:
 * - menyediakan definisi tool provider-agnostic untuk agent loop;
 * - memvalidasi parameter dengan Laravel Validator;
 * - menegakkan scoping user (tool hanya menerima User terautentikasi);
 * - mencatat setiap eksekusi ke ai_tool_logs tanpa secret/payload besar.
 */
final class ToolRegistry
{
    /** @var array<string, AssistantTool> */
    private array $tools = [];

    public function __construct(
        GetWalletsTool $wallets,
        GetCategoriesTool $categories,
        GetTransactionsTool $transactions,
        GetMonthlySummaryTool $monthlySummary,
        ComparePeriodsTool $comparePeriods,
        GetFinancialOverviewTool $financialOverview,
    ) {
        foreach ([$wallets, $categories, $transactions, $monthlySummary, $comparePeriods, $financialOverview] as $tool) {
            $this->tools[$tool->name()] = $tool;
        }
    }

    /**
     * Definisi tool untuk provider. User diterima agar tool user-specific
     * dapat ditambahkan di masa depan tanpa mengubah kontrak agent loop.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forUser(User $user): array
    {
        return array_values(array_map(
            fn (AssistantTool $tool) => $tool->schema(),
            $this->tools
        ));
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * Eksekusi tool dengan validasi + logging.
     *
     * @param  array<string, mixed>  $params
     * @return array{ok:bool,data?:mixed,error?:string,message?:string,errors?:array}
     */
    public function execute(
        User $user,
        string $name,
        array $params,
        ?string $traceId = null,
        ?int $conversationId = null,
        ?int $chatMessageId = null,
    ): array {
        $traceId = $traceId !== null && $traceId !== '' ? $traceId : (string) Str::uuid();
        $started = microtime(true);

        if (! isset($this->tools[$name])) {
            $this->log(
                user: $user,
                traceId: $traceId,
                conversationId: $conversationId,
                chatMessageId: $chatMessageId,
                tool: $name,
                params: [],
                status: 'error',
                durationMs: 0,
                resultRows: null,
                errorMessage: 'Tool tidak dikenal.',
            );

            return [
                'ok' => false,
                'error' => 'unknown_tool',
                'message' => "Tool '{$name}' tidak dikenal.",
            ];
        }

        $tool = $this->tools[$name];
        $validator = Validator::make($params, $tool->rules());

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $this->log(
                user: $user,
                traceId: $traceId,
                conversationId: $conversationId,
                chatMessageId: $chatMessageId,
                tool: $name,
                params: $this->safeParams($params, $tool),
                status: 'validation_error',
                durationMs: $this->durationMs($started),
                resultRows: null,
                errorMessage: $validator->errors()->first(),
            );

            return [
                'ok' => false,
                'error' => 'validation_error',
                'message' => $validator->errors()->first(),
                'errors' => $errors,
            ];
        }

        $validated = $validator->validated();

        try {
            $result = $tool->execute($user, $validated);
            $this->log(
                user: $user,
                traceId: $traceId,
                conversationId: $conversationId,
                chatMessageId: $chatMessageId,
                tool: $name,
                params: $validated,
                status: 'success',
                durationMs: $this->durationMs($started),
                resultRows: $this->countRows($result),
                errorMessage: null,
            );

            return ['ok' => true, 'data' => $result];
        } catch (ValidationException $e) {
            $message = $e->validator->errors()->first();
            $this->log(
                user: $user,
                traceId: $traceId,
                conversationId: $conversationId,
                chatMessageId: $chatMessageId,
                tool: $name,
                params: $validated,
                status: 'validation_error',
                durationMs: $this->durationMs($started),
                resultRows: null,
                errorMessage: $message,
            );

            return [
                'ok' => false,
                'error' => 'validation_error',
                'message' => $message,
                'errors' => $e->validator->errors()->toArray(),
            ];
        } catch (Throwable $e) {
            $this->log(
                user: $user,
                traceId: $traceId,
                conversationId: $conversationId,
                chatMessageId: $chatMessageId,
                tool: $name,
                params: $validated,
                status: 'error',
                durationMs: $this->durationMs($started),
                resultRows: null,
                errorMessage: mb_substr($e->getMessage(), 0, 500),
            );

            return [
                'ok' => false,
                'error' => 'tool_error',
                'message' => 'Tool gagal dijalankan.',
            ];
        }
    }

    /** @param array<string, mixed> $params */
    private function safeParams(array $params, AssistantTool $tool): array
    {
        $allowed = array_keys($tool->rules());

        return array_intersect_key($params, array_flip($allowed));
    }

    private function durationMs(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }

    /** @param array<string, mixed> $result */
    private function countRows(array $result): ?int
    {
        foreach (['count', 'transactions', 'wallets', 'categories'] as $key) {
            if (! isset($result[$key])) {
                continue;
            }
            if (is_array($result[$key])) {
                return count($result[$key]);
            }
            if (is_int($result[$key])) {
                return $result[$key];
            }
        }

        return null;
    }

    private function log(
        User $user,
        string $traceId,
        ?int $conversationId,
        ?int $chatMessageId,
        string $tool,
        array $params,
        string $status,
        int $durationMs,
        ?int $resultRows,
        ?string $errorMessage,
    ): void {
        AiToolLog::create([
            'user_id' => $user->id,
            'conversation_id' => $conversationId,
            'chat_message_id' => $chatMessageId,
            'trace_id' => $traceId,
            'tool' => $tool,
            'params' => $params,
            'status' => $status,
            'duration_ms' => $durationMs,
            'result_rows' => $resultRows,
            'error_message' => $errorMessage,
        ]);
    }
}
