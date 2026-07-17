<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\Settings\AiSettingsController;
use App\Http\Controllers\AiAnalyticsController;
use App\Http\Controllers\GoogleController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\PruneAiMemoriesCommand;
use App\Console\Commands\AggregateAiMetricsCommand;
use Inertia\Inertia;

// Google Auth
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::middleware(['auth'])->group(function () {
    // Dashboard & Main Analytics
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('verified')->name('dashboard');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/settings', function (\Illuminate\Http\Request $request) {
        return Inertia::render('Settings/Index', [
            'allowNegativeBalance' => $request->user()->allow_negative_balance,
        ]);
    })->name('settings.index');
    Route::patch('/settings/transaction-logic', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate(['allow_negative_balance' => ['required', 'boolean']]);
        $request->user()->update($validated);

        return back()->with('success', 'Logika transaksi diperbarui.');
    })->name('settings.transaction-logic.update');

    // AI Settings & Analytics (Gabungan)
    Route::prefix('settings/ai')->name('settings.ai.')->group(function () {
        Route::get('/', [AiSettingsController::class, 'index'])->name('index');
        Route::patch('/', [AiSettingsController::class, 'store'])->name('store');
        Route::post('/test', [AiSettingsController::class, 'testConnection'])->name('test');
        
        // API untuk Dashboard Vue (settings.ai.api.*)
        Route::get('/api/dashboard', [AiAnalyticsController::class, 'dashboard'])->name('api.dashboard');
        Route::get('/api/feedback', [AiAnalyticsController::class, 'feedback'])->name('api.feedback');
    });

    // Alias route untuk AiAnalytics.vue full-page (/settings/ai/analytics)
    Route::prefix('api/ai/analytics')->name('api.ai.analytics.')->group(function () {
        Route::get('/dashboard', [AiAnalyticsController::class, 'dashboard'])->name('dashboard');
        Route::get('/feedback', [AiAnalyticsController::class, 'feedback'])->name('feedback');
    });

    // Resources CRUD
    Route::patch('wallets/{wallet}/set-pin', [WalletController::class, 'setPin'])->name('wallets.set-pin');
    Route::resource('wallets', WalletController::class);
    Route::resource('categories', CategoryController::class);
    Route::patch('transactions/{transaction}/confirm', [TransactionController::class, 'confirm'])->name('transactions.confirm');
    Route::resource('transactions', TransactionController::class);
    Route::get('/loans/{type}', [LoanController::class, 'index'])->name('loans.index');
});

// Scheduling
Schedule::command(PruneAiMemoriesCommand::class)->dailyAt('02:00')->withoutOverlapping();
Schedule::command(AggregateAiMetricsCommand::class)->dailyAt('00:10')->withoutOverlapping();

require __DIR__ . '/auth.php';
