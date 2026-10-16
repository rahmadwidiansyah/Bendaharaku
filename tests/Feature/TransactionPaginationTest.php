<?php

namespace Tests\Feature;

use App\Models\TransactionDraft;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\Chat\DraftConfirmationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransactionPaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $cashId;

    private int $sysId;

    private int $expCatId;

    private int $expTypeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
        $this->user->update(['allow_negative_balance' => true]);
        $this->cashId = $this->user->wallets()->where('name', 'Dompet Cash')->value('id')
            ?? $this->user->wallets()->where('group_type', 'Liquid')->orderBy('id')->value('id');
        $this->sysId = $this->user->wallets()->where('group_type', 'System')->orderBy('id')->value('id');
        $this->expTypeId = TransactionType::where('name', 'Expense')->value('id');
        $this->expCatId = $this->user->categories()->where('type_id', $this->expTypeId)->orderBy('id')->value('id');
    }

    private function makeLog(string $date, string $createdAt, int $amount = 50000, string $notes = 'Bayar makan'): TransactionLog
    {
        $log = TransactionLog::create([
            'user_id' => $this->user->id,
            'reference_number' => 'TRX-'.strtoupper(Str::random(10)),
            'date' => $date,
            'type_id' => $this->expTypeId,
            'category_id' => $this->expCatId,
            'source_wallet_id' => $this->cashId,
            'destination_wallet_id' => $this->sysId,
            'amount' => $amount,
            'balance_before' => 0,
            'balance_after' => 0,
            'subject' => '-',
            'notes' => $notes,
            'is_cleared' => true,
        ]);
        // created_at tidak fillable → tulis langsung (tabrakan menit lintas sumber disengaja).
        TransactionLog::where('id', $log->id)->update(['created_at' => $createdAt, 'updated_at' => $createdAt]);

        return $log->fresh();
    }

    private function makeDraft(string $createdAt, string $payloadDate, string $notes = 'bayar makan'): TransactionDraft
    {
        $draft = TransactionDraft::create([
            'user_id' => $this->user->id,
            'ai_provider' => 'test',
            'ai_model' => 'test',
            'draft_type' => 'single',
            'status' => 'pending',
            'ai_confidence' => 0.5,
            'original_text' => $notes,
            'expires_at' => now()->addDay(),
            'payload' => [
                'type_key' => 'expense', 'amount' => 25000, 'notes' => $notes,
                'subject' => '-', 'category_name' => 'Belanja', 'date' => $payloadDate,
            ],
        ]);
        // created_at tidak fillable → tulis langsung.
        TransactionDraft::where('id', $draft->id)->update(['created_at' => $createdAt, 'updated_at' => $createdAt]);

        return $draft->fresh();
    }

    /** Tabrakan menit lintas sumber: seluruh halaman = full-load, deterministik, tanpa duplikat. */
    public function test_pages_concatenate_to_full_deterministic_set()
    {
        // 45 log + 10 draft, semuanya dalam menit yang sama di 3 tanggal berbeda.
        foreach (['2026-08-01', '2026-08-02', '2026-08-03'] as $d) {
            for ($i = 0; $i < 15; $i++) {
                $this->makeLog($d, "$d 12:00:".str_pad((string) ($i % 60), 2, '0', STR_PAD_LEFT));
            }
        }
        for ($i = 0; $i < 10; $i++) {
            $d = ['2026-08-01', '2026-08-02', '2026-08-03'][$i % 3];
            $this->makeDraft("$d 12:00:30", $d);
        }

        $walk = function () {
            $rows = [];
            $cursor = null;
            do {
                $res = $this->actingAs($this->user)->getJson(route('transactions.page', array_filter([
                    'start_date' => '2026-08-01', 'end_date' => '2026-08-03',
                    'cursor' => $cursor, 'limit' => 20,
                ])));
                $res->assertOk();
                foreach ($res->json('data') as $row) {
                    $rows[] = ($row['is_draft'] ?? false ? 'd' : 't').':'.$row['id'];
                }
                $cursor = $res->json('next_cursor');
            } while ($res->json('has_more'));

            return [$rows, $cursor];
        };

        [$first, $endCursor] = $walk();
        [$second] = $walk();

        $this->assertCount(55, $first);
        $this->assertCount(55, array_unique($first), 'Duplikat antar-halaman.');
        $this->assertEquals($first, $second, 'Urutan harus deterministik antar pemuatan.');
        $this->assertNull($endCursor);
    }

    public function test_filters_search_range_consistent_per_page()
    {
        $this->makeLog('2026-08-01', '2026-08-01 10:00:00', 50000, 'Bayar makan siang');
        $this->makeLog('2026-08-05', '2026-08-05 10:00:00', 70000, 'Beli bensin');
        $this->makeDraft('2026-08-02 10:00:00', '2026-08-02', 'bayar makan malam');

        $res = $this->actingAs($this->user)->getJson(route('transactions.page', [
            'start_date' => '2026-08-01', 'end_date' => '2026-08-03',
            'search' => 'makan', 'type' => 'expense', 'limit' => 50,
        ]))->assertOk();

        $this->assertGreaterThanOrEqual(2, count($res->json('data')));
        foreach ($res->json('data') as $row) {
            $hay = strtolower(json_encode($row));
            $this->assertStringContainsString('makan', $hay);
        }
        // Rentang di luar data → kosong, has_more false.
        $empty = $this->actingAs($this->user)->getJson(route('transactions.page', [
            'start_date' => '2026-07-01', 'end_date' => '2026-07-02', 'limit' => 50,
        ]))->assertOk();
        $this->assertEquals([], $empty->json('data'));
        $this->assertFalse($empty->json('has_more'));
    }

    public function test_exact_page_boundary_and_single_day()
    {
        for ($i = 0; $i < 40; $i++) {
            $this->makeLog('2026-08-10', '2026-08-10 09:'.str_pad((string) ($i % 60), 2, '0', STR_PAD_LEFT).':00');
        }

        $p1 = $this->actingAs($this->user)->getJson(route('transactions.page', [
            'start_date' => '2026-08-10', 'end_date' => '2026-08-10', 'limit' => 20,
        ]))->assertOk();
        $this->assertCount(20, $p1->json('data'));
        $this->assertTrue($p1->json('has_more'));

        $p2 = $this->actingAs($this->user)->getJson(route('transactions.page', [
            'start_date' => '2026-08-10', 'end_date' => '2026-08-10', 'limit' => 20,
            'cursor' => $p1->json('next_cursor'),
        ]))->assertOk();
        $this->assertCount(20, $p2->json('data'));
        $this->assertFalse($p2->json('has_more'));

        $ids1 = collect($p1->json('data'))->map(fn ($r) => ($r['is_draft'] ?? false ? 'd' : 't').':'.$r['id']);
        $ids2 = collect($p2->json('data'))->map(fn ($r) => ($r['is_draft'] ?? false ? 'd' : 't').':'.$r['id']);
        $this->assertEmpty($ids1->intersect($ids2));
    }

    public function test_draft_lifecycle_visible_on_reload()
    {
        $draft = $this->makeDraft('2026-08-02 10:00:00', '2026-08-02');

        $before = $this->actingAs($this->user)->getJson(route('transactions.page', [
            'start_date' => '2026-08-01', 'end_date' => '2026-08-03', 'limit' => 50,
        ]))->assertOk();
        $this->assertContains('d:'.$draft->id, collect($before->json('data'))->map(fn ($r) => ($r['is_draft'] ?? false ? 'd' : 't').':'.$r['id']));

        app(DraftConfirmationService::class)->cancel($draft->fresh(), $this->user);

        $after = $this->actingAs($this->user)->getJson(route('transactions.page', [
            'start_date' => '2026-08-01', 'end_date' => '2026-08-03', 'limit' => 50,
        ]))->assertOk();
        $this->assertNotContains('d:'.$draft->id, collect($after->json('data'))->map(fn ($r) => ($r['is_draft'] ?? false ? 'd' : 't').':'.$r['id']));
    }

    public function test_expired_pending_draft_hidden_from_list()
    {
        // Keputusan produk 1A: draft kedaluwarsa disembunyikan dari daftar
        // (konsisten dengan isPending() yang menolak konfirmasi).
        $draft = $this->makeDraft('2026-08-02 10:00:00', '2026-08-02');
        $draft->update(['expires_at' => now()->subDay()]);

        $res = $this->actingAs($this->user)->getJson(route('transactions.page', [
            'start_date' => '2026-08-01', 'end_date' => '2026-08-03', 'limit' => 50,
        ]))->assertOk();
        $this->assertNotContains('d:'.$draft->id, collect($res->json('data'))->map(fn ($r) => ($r['is_draft'] ?? false ? 'd' : 't').':'.$r['id']));
    }

    public function test_unexpired_pending_draft_still_listed()
    {
        $draft = $this->makeDraft('2026-08-02 10:00:00', '2026-08-02');
        $draft->update(['expires_at' => now()->addDay()]);

        $res = $this->actingAs($this->user)->getJson(route('transactions.page', [
            'start_date' => '2026-08-01', 'end_date' => '2026-08-03', 'limit' => 50,
        ]))->assertOk();
        $this->assertContains('d:'.$draft->id, collect($res->json('data'))->map(fn ($r) => ($r['is_draft'] ?? false ? 'd' : 't').':'.$r['id']));
    }

    public function test_cursor_rejected_for_other_filter_and_user_and_malformed()
    {
        $this->makeLog('2026-08-01', '2026-08-01 10:00:00', 50000, 'Bayar makan');
        $this->makeLog('2026-08-05', '2026-08-05 10:00:00', 70000, 'Beli bensin');

        $p1 = $this->actingAs($this->user)->getJson(route('transactions.page', [
            'start_date' => '2026-08-01', 'end_date' => '2026-08-10', 'limit' => 1,
        ]))->assertOk();
        $cursor = $p1->json('next_cursor');
        $this->assertNotNull($cursor);

        // Cursor + filter berbeda → filter kini yang berlaku.
        $other = $this->actingAs($this->user)->getJson(route('transactions.page', [
            'start_date' => '2026-08-05', 'end_date' => '2026-08-05', 'limit' => 50, 'cursor' => $cursor,
        ]))->assertOk();
        foreach ($other->json('data') as $row) {
            $this->assertEquals('2026-08-05', substr($row['raw_date'], 0, 10));
        }

        // Cursor + pengguna berbeda → hanya data pemilik.
        $user2 = User::factory()->create();
        $mine = $this->actingAs($user2)->getJson(route('transactions.page', [
            'start_date' => '2026-08-01', 'end_date' => '2026-08-10', 'limit' => 50, 'cursor' => $cursor,
        ]))->assertOk();
        $this->assertEquals([], $mine->json('data'));

        // Cursor rusak → 422, bukan 500.
        $this->actingAs($this->user)->getJson(route('transactions.page', [
            'start_date' => '2026-08-01', 'end_date' => '2026-08-10', 'cursor' => '!!!',
        ]))->assertStatus(422);
    }

    public function test_aggregates_match_full_rows_and_contract()
    {
        $this->makeLog('2026-08-01', '2026-08-01 10:00:00', 100000, 'Bayar makan');
        $this->makeLog('2026-08-01', '2026-08-01 11:00:00', 50000, 'Beli bensin');
        $this->makeDraft('2026-08-02 10:00:00', '2026-08-02', 'bayar makan');

        $res = $this->actingAs($this->user)->getJson(route('transactions.page', [
            'start_date' => '2026-08-01', 'end_date' => '2026-08-03', 'limit' => 50,
        ]))->assertOk();

        $totals = $res->json('dailyTotals');
        $this->assertEquals(150000, $totals['2026-08-01']['expense']);
        $this->assertEquals(25000, $totals['2026-08-02']['expense']);
        $this->assertEquals(['2026-08-01', '2026-08-02'], $res->json('calendarMarkers'));

        // Kontrak field: kunci pasti per jenis + relasi proyeksi kecil.
        $logKeys = ['id', 'amount', 'notes', 'subject', 'is_cleared', 'reference_number', 'date', 'raw_date', 'time', 'type', 'category', 'source_wallet', 'destination_wallet', 'due_date', 'due_date_type', 'due_date_interval'];
        $draftKeys = ['id', 'is_draft', 'draft_id', 'amount', 'notes', 'subject', 'is_cleared', 'reference_number', 'date', 'raw_date', 'time', 'type', 'category', 'source_wallet', 'destination_wallet', 'due_date', 'due_date_type', 'due_date_interval'];
        foreach ($res->json('data') as $row) {
            if ($row['is_draft'] ?? false) {
                $this->assertEqualsCanonicalizing($draftKeys, array_keys($row));
            } else {
                $this->assertEqualsCanonicalizing($logKeys, array_keys($row));
                $this->assertEqualsCanonicalizing(['id', 'name'], array_keys($row['type']));
            }
        }
    }

    public function test_limit_capped()
    {
        $this->makeLog('2026-08-01', '2026-08-01 10:00:00');
        $res = $this->actingAs($this->user)->getJson(route('transactions.page', [
            'start_date' => '2026-08-01', 'end_date' => '2026-08-03', 'limit' => 500,
        ]))->assertOk();
        $this->assertLessThanOrEqual(100, count($res->json('data')));
    }
}
