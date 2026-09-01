<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\TransactionSource;
use App\Events\TransactionPosted;
use App\Listeners\LearnFromTransaction;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\UserAiMemoryContribution;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearnFromKeywordsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    public function test_web_source_does_not_create_memory(): void
    {
        $transaction = $this->createTransaction();

        $event = new TransactionPosted($transaction, TransactionSource::WEB);

        $listener = app(LearnFromTransaction::class);
        $listener->handle($event);

        self::assertDatabaseCount('user_ai_memory_contributions', 0);
    }

    public function test_telegram_source_learns_from_keywords(): void
    {
        $transaction = $this->createTransaction();
        $category = $this->user->categories()->first();

        $event = new TransactionPosted($transaction, TransactionSource::TELEGRAM, [
            [
                'keyword' => 'bakso',
                'target_type' => 'category',
                'target_id' => $category->id,
                'target_name' => $category->category_name,
            ],
        ]);

        $listener = app(LearnFromTransaction::class);
        $listener->handle($event);

        self::assertDatabaseHas('user_ai_memories', [
            'user_id' => $this->user->id,
            'keyword_pattern' => 'bakso',
            'target_type' => 'category',
        ]);

        self::assertDatabaseHas('user_ai_memory_contributions', [
            'user_id' => $this->user->id,
            'transaction_id' => $transaction->id,
            'keyword' => 'bakso',
            'target_type' => 'category',
            'is_active' => true,
        ]);
    }

    public function test_idempotent_does_not_duplicate_contribution(): void
    {
        $transaction = $this->createTransaction();
        $category = $this->user->categories()->first();

        $keywords = [
            [
                'keyword' => 'bakso',
                'target_type' => 'category',
                'target_id' => $category->id,
                'target_name' => $category->category_name,
            ],
        ];

        $event = new TransactionPosted($transaction, TransactionSource::TELEGRAM, $keywords);

        $listener = app(LearnFromTransaction::class);
        $listener->handle($event);
        $listener->handle($event);

        $count = UserAiMemoryContribution::where('transaction_id', $transaction->id)
            ->where('keyword', 'bakso')
            ->where('target_type', 'category')
            ->count();

        self::assertSame(1, $count);
    }

    public function test_separate_category_and_wallet_keywords(): void
    {
        $transaction = $this->createTransaction();
        $category = $this->user->categories()->first();
        $wallet = $this->user->wallets()->where('group_type', '!=', 'System')->first();

        $keywords = [
            [
                'keyword' => 'bakso',
                'target_type' => 'category',
                'target_id' => $category->id,
                'target_name' => $category->category_name,
            ],
            [
                'keyword' => 'cash',
                'target_type' => 'wallet',
                'target_id' => $wallet->id,
                'target_name' => $wallet->name,
            ],
        ];

        $event = new TransactionPosted($transaction, TransactionSource::WEB_CHAT, $keywords);

        $listener = app(LearnFromTransaction::class);
        $listener->handle($event);

        self::assertDatabaseHas('user_ai_memories', [
            'user_id' => $this->user->id,
            'keyword_pattern' => 'bakso',
            'target_type' => 'category',
        ]);

        self::assertDatabaseHas('user_ai_memories', [
            'user_id' => $this->user->id,
            'keyword_pattern' => 'cash',
            'target_type' => 'wallet',
        ]);

        self::assertSame(2, UserAiMemoryContribution::where('transaction_id', $transaction->id)->count());
    }

    public function test_short_keyword_is_skipped(): void
    {
        $transaction = $this->createTransaction();

        $keywords = [
            ['keyword' => 'a', 'target_type' => 'category', 'target_id' => 1, 'target_name' => 'X'],
        ];

        $event = new TransactionPosted($transaction, TransactionSource::TELEGRAM, $keywords);

        $listener = app(LearnFromTransaction::class);
        $listener->handle($event);

        self::assertDatabaseCount('user_ai_memory_contributions', 0);
    }

    public function test_invalid_target_type_is_skipped(): void
    {
        $transaction = $this->createTransaction();

        $keywords = [
            ['keyword' => 'bakso', 'target_type' => 'invalid', 'target_id' => 1, 'target_name' => 'X'],
        ];

        $event = new TransactionPosted($transaction, TransactionSource::TELEGRAM, $keywords);

        $listener = app(LearnFromTransaction::class);
        $listener->handle($event);

        self::assertDatabaseCount('user_ai_memory_contributions', 0);
    }

    private function createTransaction(): TransactionLog
    {
        $wallet = $this->user->wallets()->where('group_type', '!=', 'System')->firstOrFail();
        $category = $this->user->categories()->firstOrFail();
        $type = $category->type;

        return TransactionLog::create([
            'user_id' => $this->user->id,
            'reference_number' => 'TRX-'.strtoupper(Str::random(10)),
            'date' => now()->toDateString(),
            'type_id' => $type->id,
            'category_id' => $category->id,
            'source_wallet_id' => $wallet->id,
            'destination_wallet_id' => $wallet->id,
            'amount' => 20000,
            'balance_before' => 0,
            'balance_after' => 0,
            'subject' => 'bakso',
            'notes' => 'test',
            'is_cleared' => true,
        ]);
    }
}
