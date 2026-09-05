<?php

use App\Jobs\CheckLoanRemindersJob;
use App\Models\User;
use App\Services\Budgeting\AIBudgetService;
use App\Services\Push\PushGate;
use App\Services\Push\PushPayloadBuilder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mengagregasi data hari sebelumnya setiap jam 00:30
Schedule::command('ai:aggregate-metrics')->dailyAt('00:30')->withoutOverlapping();

// Prune AI Memories (dari sprint sebelumnya)
Schedule::command('ai:prune-memories')->dailyAt('01:00')->withoutOverlapping();

// Generate anggaran otomatis setiap tanggal 1 untuk user yang mengaktifkan auto-budget
Artisan::command('budget:auto-generate', function () {
    $users = User::where('auto_budget_enabled', true)->get();
    $service = app(AIBudgetService::class);
    $now = now();

    $ok = 0;
    $failed = 0;

    foreach ($users as $user) {
        try {
            $service->generate($user, $now->month, $now->year);
            PushGate::dispatch(
                $user,
                PushPayloadBuilder::budgetCreated($user, $now->month, $now->year)
            );
            $ok++;
        } catch (Throwable $e) {
            $failed++;
            PushGate::dispatch(
                $user,
                PushPayloadBuilder::budgetGenerationFailed($user, $now->month, $now->year)
            );
            Log::warning("Auto-budget gagal untuk user #{$user->id}: {$e->getMessage()}");
        }
    }

    $this->info("Auto-budget: {$ok} berhasil, {$failed} gagal.");
})->purpose('Generate anggaran otomatis setiap tanggal 1');

Schedule::command('budget:auto-generate')->monthlyOn(1, '06:00')->withoutOverlapping();

// Pengingat jatuh tempo hutang/piutang (D-1 dan hari H) setiap pukul 07:00
Artisan::command('loan:send-reminders', function () {
    $date = now()->toDateString();
    $users = User::where('push_notifications', true)->get();

    $dispatched = 0;

    foreach ($users as $user) {
        CheckLoanRemindersJob::dispatch($user->id, $date);
        $dispatched++;
    }

    $this->info("loan:send-reminders: {$dispatched} user dijadwalkan (tanggal {$date}).");
})->purpose('Kirim pengingat jatuh tempo hutang/piutang (D-1 dan hari H)');

Schedule::command('loan:send-reminders')->dailyAt('07:00')->withoutOverlapping();

// ── Prune activity logs 7 hari (Privacy > Activity Logs) setiap 03:00 ──
Schedule::command('activity:prune --days=7')->dailyAt('03:00')->withoutOverlapping();
