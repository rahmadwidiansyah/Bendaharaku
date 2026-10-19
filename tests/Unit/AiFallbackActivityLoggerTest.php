<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\AI\AiFallbackActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiFallbackActivityLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_writes_one_activity_entry_for_fallback_with_metadata(): void
    {
        $user = User::factory()->create();

        AiFallbackActivityLogger::log($user, 'gemini/gemini-2.0-flash', 'deepseek/deepseek-chat', [
            'trace_id' => 'tr-1',
            'selection_method' => 'fallback',
            'fallback_reason' => 'rate_limit',
            'final_status' => 'success',
        ]);

        $logs = UserActivityLog::where('user_id', $user->id)->where('type', 'ai')->get();
        $this->assertCount(1, $logs);

        $log = $logs->first();
        $this->assertSame('fallback', $log->action);
        $this->assertSame('AI fallback: gemini/gemini-2.0-flash → deepseek/deepseek-chat', $log->title);
        $this->assertSame('Alasan: rate_limit', $log->description);
        $this->assertSame('tr-1', $log->metadata['trace_id']);
        $this->assertSame('gemini/gemini-2.0-flash', $log->metadata['fallback_from']);
        $this->assertSame('deepseek/deepseek-chat', $log->metadata['fallback_to']);
    }

    public function test_it_skips_log_when_from_or_to_is_empty(): void
    {
        $user = User::factory()->create();

        AiFallbackActivityLogger::log($user, '', 'deepseek/deepseek-chat');
        AiFallbackActivityLogger::log($user, 'gemini/gemini-2.0-flash', '');

        $this->assertSame(0, UserActivityLog::where('user_id', $user->id)->count());
    }
}
