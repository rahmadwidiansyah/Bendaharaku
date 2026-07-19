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
use App\Http\Controllers\WebChatController;
use App\Http\Controllers\ChatBotProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\PruneAiMemoriesCommand;
use App\Console\Commands\AggregateAiMetricsCommand;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

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

    // Simpan preferensi locale user ke DB agar chat (Telegram & Web) bisa baca
    Route::patch('/settings/locale', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate(['locale' => ['required', 'string', 'in:id,en,auto']]);
        // 'auto' = null di DB (ikuti platform/default)
        $request->user()->update([
            'locale' => $validated['locale'] === 'auto' ? null : $validated['locale'],
        ]);
        return response()->json(['success' => true]);
    })->name('settings.locale.update');

    // ── SETTINGS PAGES - NEW HIERARCHY ────────────────────────────────
    // Account
    Route::prefix('settings/account')->name('settings.account.')->group(function () {
        Route::get('/profile', fn() => Inertia::render('Settings/Account/Profile'))->name('profile');
        Route::get('/security', fn() => Inertia::render('Settings/Account/Security'))->name('security');
        Route::get('/sessions', fn() => Inertia::render('Settings/Account/Sessions'))->name('sessions');
        Route::get('/preferences', fn() => Inertia::render('Settings/Account/Preferences'))->name('preferences');
    });

    // Application
    Route::prefix('settings/application')->name('settings.application.')->group(function () {
        Route::get('/appearance', fn() => Inertia::render('Settings/Application/Appearance'))->name('appearance');
        Route::get('/language', fn() => Inertia::render('Settings/Application/Language'))->name('language');
        Route::get('/notifications', fn() => Inertia::render('Settings/Application/Notifications'))->name('notifications');
    });

    // Finance
    Route::prefix('settings/finance')->name('settings.finance.')->group(function () {
        Route::get('/defaults', fn() => Inertia::render('Settings/Finance/Defaults'))->name('defaults');
        Route::get('/categories', fn() => Inertia::render('Settings/Finance/Categories'))->name('categories');
        Route::get('/wallets', fn() => Inertia::render('Settings/Finance/Wallets'))->name('wallets');
        Route::get('/budget', fn() => Inertia::render('Settings/Finance/Budget'))->name('budget');
    });

    // Privacy & Data
    Route::prefix('settings/privacy')->name('settings.privacy.')->group(function () {
        Route::get('/settings', fn() => Inertia::render('Settings/Privacy/Settings'))->name('settings');
        Route::get('/data', fn() => Inertia::render('Settings/Privacy/Data'))->name('data');
        Route::get('/danger', fn() => Inertia::render('Settings/Privacy/Danger'))->name('danger');
    });

    // System
    Route::prefix('settings/system')->name('settings.system.')->group(function () {
        Route::get('/about', fn() => Inertia::render('Settings/System/About'))->name('about');
        Route::get('/diagnostics', fn() => Inertia::render('Settings/System/Diagnostics'))->name('diagnostics');
    });

    // AI Settings - Restructured Hierarchy
    Route::prefix('settings/ai')->name('settings.ai.')->group(function () {
        // New hierarchical pages (Phase 3)
        Route::get('/models', fn() => Inertia::render('Settings/AI/Models'))->name('models');
        Route::get('/bot', fn() => Inertia::render('Settings/AI/Bot'))->name('bot');
        Route::get('/memory', fn() => Inertia::render('Settings/AI/Memory'))->name('memory');
        Route::get('/integrations', fn() => Inertia::render('Settings/AI/Integration'))->name('integrations');
        Route::get('/advanced', fn() => Inertia::render('Settings/AI/Advanced'))->name('advanced');
        
        // Legacy endpoints (for backward compatibility)
        Route::get('/', [AiSettingsController::class, 'index'])->name('index');
        Route::patch('/', [AiSettingsController::class, 'store'])->name('store');
        Route::post('/test', [AiSettingsController::class, 'testConnection'])->name('test');
        Route::get('/integration', fn() => Inertia::render('Settings/AI/Integration'))->name('integration');
        
        // API untuk Dashboard (not in settings, but for reference)
        Route::get('/api/dashboard', [AiAnalyticsController::class, 'dashboard'])->name('api.dashboard');
        Route::get('/api/feedback', [AiAnalyticsController::class, 'feedback'])->name('api.feedback');
    });

    // Alias route untuk AiAnalytics.vue full-page (/settings/ai/analytics)
    Route::prefix('api/ai/analytics')->name('api.ai.analytics.')->group(function () {
        Route::get('/dashboard', [AiAnalyticsController::class, 'dashboard'])->name('dashboard');
        Route::get('/feedback', [AiAnalyticsController::class, 'feedback'])->name('feedback');
    });

    // Recent settings changes (for Settings Home widget)
    Route::get('/settings/recent-changes', function (\Illuminate\Http\Request $request) {
        $userId = $request->user()->id;
        $rows = DB::table('user_settings_changes')
            ->where('user_id', $userId)
            ->orderByDesc('changed_at')
            ->limit(10)
            ->get();

        return response()->json($rows);
    })->name('settings.recent_changes');

    // ── Chat ──────────────────────────────────────────────────────
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/',          [WebChatController::class, 'index'])->name('index');
        Route::post('/message',  [WebChatController::class, 'sendMessage'])->name('message');
        Route::get('/history',   [WebChatController::class, 'history'])->name('history');
        Route::get('/commands',  [WebChatController::class, 'commands'])->name('commands');
        Route::get('/wallets',   [WebChatController::class, 'wallets'])->name('wallets');
        Route::get('/transaction/{id}/status', [WebChatController::class, 'transactionStatus'])->name('transaction.status');
        Route::patch('/transaction/{id}/confirm', [WebChatController::class, 'confirmTransaction'])->name('transaction.confirm');
        Route::patch('/transaction/{id}/wallet', [WebChatController::class, 'assignWallet'])->name('transaction.assign-wallet');
        Route::delete('/transaction/{id}/cancel', [WebChatController::class, 'cancelTransaction'])->name('transaction.cancel');
    });

    // ── Chat Bot Profile settings ─────────────────────────────────
    Route::prefix('settings/chat')->name('settings.chat.')->group(function () {
        Route::get('/bot-profile',    [ChatBotProfileController::class, 'show'])->name('bot-profile');
        Route::patch('/bot-profile',  [ChatBotProfileController::class, 'update'])->name('bot-profile.update');
        Route::delete('/bot-avatar',  [ChatBotProfileController::class, 'destroyAvatar'])->name('bot-avatar.destroy');
    });

    // ── Redirects for backward compatibility ───────────────────────
    Route::redirect('/settings/chat/bot-profile', '/settings/ai/bot', 301);
    Route::redirect('/settings/ai', '/settings/ai/models', 301);

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
